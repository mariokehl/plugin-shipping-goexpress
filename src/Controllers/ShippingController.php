<?php

namespace GoExpress\Controllers;

use Plenty\Modules\Account\Address\Contracts\AddressRepositoryContract;
use Plenty\Modules\Account\Address\Models\Address;
use Plenty\Modules\Cloud\Storage\Models\StorageObject;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Modules\Order\Shipping\Contracts\ParcelServicePresetRepositoryContract;
use Plenty\Modules\Order\Shipping\Information\Contracts\ShippingInformationRepositoryContract;
use Plenty\Modules\Order\Shipping\Package\Contracts\OrderShippingPackageRepositoryContract;
use Plenty\Modules\Order\Shipping\PackageType\Contracts\ShippingPackageTypeRepositoryContract;
use Plenty\Modules\Order\Shipping\ParcelService\Models\ParcelServicePreset;
use Plenty\Modules\Plugin\Storage\Contracts\StorageRepositoryContract;
use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\ConfigRepository;
use GoExpress\API\GOWebService;
use GoExpress\API\Abholadresse;
use GoExpress\API\Abholdatum;
use GoExpress\API\Empfaenger;
use GoExpress\API\SendungsDaten;
use GoExpress\API\SendungsPosition;
use Plenty\Plugin\Log\Loggable;

/**
 * Class ShippingController
 */
class ShippingController extends Controller
{

	use Loggable;

	/**
	 * @var Request
	 */
	private $request;

    /**
     * @var GOWebService $webservice
     */
    private $webservice;

	/**
	 * @var OrderRepositoryContract $orderRepository
	 */
	private $orderRepository;

	/**
	 * @var AddressRepositoryContract $addressRepository
	 */
	private $addressRepository;

	/**
	 * @var OrderShippingPackageRepositoryContract $orderShippingPackage
	 */
	private $orderShippingPackage;

	/**
	 * @var ShippingInformationRepositoryContract
	 */
	private $shippingInformationRepositoryContract;

	/**
	 * @var StorageRepositoryContract $storageRepository
	 */
	private $storageRepository;

	/**
	 * @var ShippingPackageTypeRepositoryContract
	 */
	private $shippingPackageTypeRepositoryContract;

    /**
     * @var  array
     */
    private $createOrderResult = [];

    /**
     * @var ConfigRepository
     */
    private $config;

	/**
	 * ShipmentController constructor.
     *
	 * @param Request $request
	 * @param OrderRepositoryContract $orderRepository
	 * @param AddressRepositoryContract $addressRepositoryContract
	 * @param OrderShippingPackageRepositoryContract $orderShippingPackage
	 * @param StorageRepositoryContract $storageRepository
	 * @param ShippingInformationRepositoryContract $shippingInformationRepositoryContract
	 * @param ShippingPackageTypeRepositoryContract $shippingPackageTypeRepositoryContract
     * @param ConfigRepository $config
     */
	public function __construct(Request $request,
								OrderRepositoryContract $orderRepository,
								AddressRepositoryContract $addressRepositoryContract,
								OrderShippingPackageRepositoryContract $orderShippingPackage,
								StorageRepositoryContract $storageRepository,
								ShippingInformationRepositoryContract $shippingInformationRepositoryContract,
								ShippingPackageTypeRepositoryContract $shippingPackageTypeRepositoryContract,
                                ConfigRepository $config)
	{
		$this->request = $request;
		$this->orderRepository = $orderRepository;
		$this->addressRepository = $addressRepositoryContract;
		$this->orderShippingPackage = $orderShippingPackage;
		$this->storageRepository = $storageRepository;

		$this->shippingInformationRepositoryContract = $shippingInformationRepositoryContract;
		$this->shippingPackageTypeRepositoryContract = $shippingPackageTypeRepositoryContract;

		$this->config = $config;

		// Get credentials by UI config
		$partnerCredentialsUser = $this->config->get('GoExpress.username');
        $partnerCredentialsPass = $this->config->get('GoExpress.password');

		$this->webservice = pluginApp(GOWebService::class, [
			[
				'login' => $partnerCredentialsUser,
				'password' => $partnerCredentialsPass
			],
			$this->config->get('GoExpress.mode')
		]);
	}


	/**
	 * Registers shipment(s)
	 *
	 * @param Request $request
	 * @param array $orderIds
	 * @return string
	 */
	public function registerShipments(Request $request, $orderIds)
	{
		$orderIds = $this->getOrderIds($request, $orderIds);
		$orderIds = $this->getOpenOrderIds($orderIds);
		$shipmentDate = date('Y-m-d');

		foreach($orderIds as $orderId)
		{
			$order = $this->orderRepository->findOrderById($orderId);

            // gathering required data for registering the shipment

            /** @var Address $address */
            $address = $order->deliveryAddress;

            $receiverFirstName     = $address->firstName;
            $receiverLastName      = $address->lastName;
			$receiverName		   = $receiverFirstName.' '.$receiverLastName;
            $receiverStreet        = $address->street;
            $receiverNo            = $address->houseNumber;
			$receiverCountry       = $address->country->isoCode2;
            $receiverPostalCode    = $address->postalCode;
            $receiverTown          = $address->town;
			$receiverEmail 		   = $address->email;

			$receiverAddress = pluginApp(Empfaenger::class, [
				$receiverName,
				$receiverStreet,
				$receiverNo,
				$receiverCountry,
				$receiverPostalCode,
				$receiverTown,
				$receiverEmail
			]);

            // reads sender data from plugin config. this is going to be changed in the future to retrieve data from backend ui settings
            $senderName           = $this->config->get('GoExpress.senderName', 'Fleischerei Schäfer OHG');
            $senderStreet         = $this->config->get('GoExpress.senderStreet', 'Hersfelder Str.');
            $senderNo             = $this->config->get('GoExpress.senderNo', '20');
			$senderCountry        = $this->config->get('GoExpress.senderCountry', 'DE');
            $senderPostalCode     = $this->config->get('GoExpress.senderPostalCode', '36272');
            $senderTown           = $this->config->get('GoExpress.senderTown', 'Niederaula');

			$senderAddress = pluginApp(Abholadresse::class, [
				$senderName,
				$senderStreet,
				$senderNo,
				$senderCountry,
				$senderPostalCode,
				$senderTown
			]);

			$pickupDate = pluginApp(Abholdatum::class, [date('d.m.Y')]);

            // gets order shipping packages from current order
            $packages = $this->orderShippingPackage->listOrderShippingPackages($order->id);

            // iterating through packages
            foreach ($packages as $package)
            {
                // weight (kg)
				$weight = 0.2; // Fallback minimum weight
				if ($package->weight) {
					$weight = $package->weight / 1000;
				}

                // determine packageType
                $packageType = $this->shippingPackageTypeRepositoryContract->findShippingPackageTypeById($package->packageId);

                // package dimensions
                list($length, $width, $height) = $this->getPackageDimensions($packageType);

				$content = 'Lebensmittel';
				$internalId = $orderId . '_' . $package->id; // Kind of reference number
				$reference1 = strlen($order->deliveryAddress->email) ? $order->deliveryAddress->email : $order->deliveryAddress->phone;
				$reference1 = substr($reference1, 0, 35);
				$reference2 = substr('Auftragsnummer: ' . $orderId, 0, 35);


				$parcelData = pluginApp(SendungsPosition::class, [
					1,
					$weight,
					$content
				]);

                try
                {

					$shipmentData = pluginApp(SendungsDaten::class, [
						$receiverAddress,
						$senderAddress,
						$pickupDate,
						$parcelData,
						$reference2
					]);

					$this->getLogger('ShippingController_registerShipments')->debug('GoExpress::webservice.SendungsDaten', ['shipmentData' => json_encode($shipmentData)]);
					$shipment = $this->webservice->GOWebService_SendungsErstellung($shipmentData);
					$this->getLogger('ShippingController_registerShipments')->debug('GoExpress::webservice.SendungsErstellung', ['shipment' => json_encode($shipment)]);

                    // shipping service providers API should be used here
                    $response = [
                        'labelUrl' => 'https://developers.plentymarkets.com/layout/plugins/production/plentypluginshowcase/images/landingpage/why-plugin-2.svg',
                        'shipmentNumber' => '1111112222223333',
                        'sequenceNumber' => 1,
                        'status' => 'shipment sucessfully registered'
                    ];

                    // handles the response
                    $shipmentItems = $this->handleAfterRegisterShipment($response['labelUrl'], $response['shipmentNumber'], $package->id);

                    // adds result
                    $this->createOrderResult[$orderId] = $this->buildResultArray(
                        true,
                        $this->getStatusMessage($response),
                        false,
                        $shipmentItems);

                    // saves shipping information
                    $this->saveShippingInformation($orderId, $shipmentDate, $shipmentItems);


                }
                catch(\SoapFault $soapFault)
                {
					$this->getLogger('ShippingController_registerShipments')->critical('GoExpress::webservice.SOAPerr', ['soapFault' => $soapFault->getMessage()]);
                }

            }

		}

		// return all results to service
		return $this->createOrderResult;
	}



    /**
     * Cancels registered shipment(s)
     *
     * @param Request $request
     * @param array $orderIds
     * @return array
     */
    public function deleteShipments(Request $request, $orderIds)
    {
        $orderIds = $this->getOrderIds($request, $orderIds);
        foreach ($orderIds as $orderId)
        {
            $shippingInformation = $this->shippingInformationRepositoryContract->getShippingInformationByOrderId($orderId);

            if (isset($shippingInformation->additionalData) && is_array($shippingInformation->additionalData))
            {
                foreach ($shippingInformation->additionalData as $additionalData)
                {
                    try
                    {
                        $shipmentNumber = $additionalData['shipmentNumber'];

                        // use the shipping service provider's API here
                        $response = '';

                        $this->createOrderResult[$orderId] = $this->buildResultArray(
                            true,
                            $this->getStatusMessage($response),
                            false,
                            null);

                    }
                    catch(\SoapFault $soapFault)
                    {
                        // exception handling
                    }

                }

                // resets the shipping information of current order
                $this->shippingInformationRepositoryContract->resetShippingInformation($orderId);
            }


        }

        // return result array
        return $this->createOrderResult;
    }


	/**
     * Retrieves the label file from a given URL and saves it in S3 storage
     *
	 * @param $labelUrl
	 * @param $key
	 * @return StorageObject
	 */
	private function saveLabelToS3($labelUrl, $key)
	{
		$ch = curl_init();

		// Set URL to download
		curl_setopt($ch, CURLOPT_URL, $labelUrl);

		// Include header in result? (0 = yes, 1 = no)
		curl_setopt($ch, CURLOPT_HEADER, 0);

		// Should cURL return or print out the data? (true = return, false = print)
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		// Timeout in seconds
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);

		// Download the given URL, and return output
		$output = curl_exec($ch);

		// Close the cURL resource, and free system resources
		curl_close($ch);
		return $this->storageRepository->uploadObject('GoExpress', $key, $output);

	}

	/**
	 * Returns the parcel service preset for the given Id.
	 *
	 * @param int $parcelServicePresetId
	 * @return ParcelServicePreset
	 */
	private function getParcelServicePreset($parcelServicePresetId)
	{
		/** @var ParcelServicePresetRepositoryContract $parcelServicePresetRepository */
		$parcelServicePresetRepository = pluginApp(ParcelServicePresetRepositoryContract::class);

		$parcelServicePreset = $parcelServicePresetRepository->getPresetById($parcelServicePresetId);

		if($parcelServicePreset)
		{
			return $parcelServicePreset;
		}
		else
		{
			return null;
		}
	}

	/**
     * Returns a formatted status message
     *
	 * @param array $response
	 * @return string
	 */
	private function getStatusMessage($response)
	{
		return 'Code: '.$response['status']; // should contain error code and descriptive part
	}

    /**
     * Saves the shipping information
     *
     * @param $orderId
     * @param $shipmentDate
     * @param $shipmentItems
     */
	private function saveShippingInformation($orderId, $shipmentDate, $shipmentItems)
	{
		$transactionIds = array();
		foreach ($shipmentItems as $shipmentItem)
		{
			$transactionIds[] = $shipmentItem['shipmentNumber'];
			
		}

        $shipmentAt = date(\DateTime::W3C, strtotime($shipmentDate));
        $registrationAt = date(\DateTime::W3C);

		$data = [
			'orderId' => $orderId,
			'transactionId' => implode(',', $transactionIds),
			'shippingServiceProvider' => 'GoExpress',
			'shippingStatus' => 'registered',
			'shippingCosts' => 0.00,
			'additionalData' => $shipmentItems,
			'registrationAt' => $registrationAt,
			'shipmentAt' => $shipmentAt

		];
		$this->shippingInformationRepositoryContract->saveShippingInformation(
			$data);
	}

    /**
     * Returns all order ids with shipping status 'open'
     *
     * @param array $orderIds
     * @return array
     */
	private function getOpenOrderIds($orderIds)
	{
		
		$openOrderIds = array();
		foreach ($orderIds as $orderId)
		{
			$shippingInformation = $this->shippingInformationRepositoryContract->getShippingInformationByOrderId($orderId);
			if ($shippingInformation->shippingStatus == null || $shippingInformation->shippingStatus == 'open')
			{
				$openOrderIds[] = $orderId;
			}
		}
		return $openOrderIds;
	}


	/**
     * Returns an array in the structure demanded by plenty service
     *
	 * @param bool $success
	 * @param string $statusMessage
	 * @param bool $newShippingPackage
	 * @param array $shipmentItems
	 * @return array
	 */
	private function buildResultArray($success = false, $statusMessage = '', $newShippingPackage = false, $shipmentItems = [])
	{
		return [
			'success' => $success,
			'message' => $statusMessage,
			'newPackagenumber' => $newShippingPackage,
			'packages' => $shipmentItems,
		];
	}

	/**
     * Returns shipment array
     *
	 * @param string $labelUrl
	 * @param string $shipmentNumber
	 * @return array
	 */
	private function buildShipmentItems($labelUrl, $shipmentNumber)
	{
		return  [
			'labelUrl' => $labelUrl,
			'shipmentNumber' => $shipmentNumber,
		];
	}

	/**
     * Returns package info
     *
	 * @param string $packageNumber
	 * @param string $labelUrl
	 * @return array
	 */
	private function buildPackageInfo($packageNumber, $labelUrl)
	{
		return [
			'packageNumber' => $packageNumber,
			'label' => $labelUrl
		];
	}

	/**
     * Returns all order ids from request object
     *
	 * @param Request $request
	 * @param $orderIds
	 * @return array
	 */
	private function getOrderIds(Request $request, $orderIds)
	{
		if (is_numeric($orderIds))
		{
			$orderIds = array($orderIds);
		}
		else if (!is_array($orderIds))
		{
			$orderIds = $request->get('orderIds');
		}
		return $orderIds;
	}

	/**
     * Returns the package dimensions by package type
     *
	 * @param $packageType
	 * @return array
	 */
	private function getPackageDimensions($packageType): array
	{
		if ($packageType->length > 0 && $packageType->width > 0 && $packageType->height > 0)
		{
			$length = $packageType->length;
			$width = $packageType->width;
			$height = $packageType->height;
		}
		else
		{
			$length = null;
			$width = null;
			$height = null;
		}
		return array($length, $width, $height);
	}


	/**
     * Handling of response values, fires S3 storage and updates order shipping package
     *
	 * @param string $labelUrl
     * @param string $shipmentNumber
     * @param string $sequenceNumber
	 * @return array
	 */
	private function handleAfterRegisterShipment($labelUrl, $shipmentNumber, $sequenceNumber)
	{
		$shipmentItems = array();
		$storageObject = $this->saveLabelToS3(
			$labelUrl,
			$shipmentNumber . '.pdf');

		$shipmentItems[] = $this->buildShipmentItems(
			$labelUrl,
			$shipmentNumber);

		$this->orderShippingPackage->updateOrderShippingPackage(
			$sequenceNumber,
			$this->buildPackageInfo(
				$shipmentNumber,
				$storageObject->key));
		return $shipmentItems;
	}
}
