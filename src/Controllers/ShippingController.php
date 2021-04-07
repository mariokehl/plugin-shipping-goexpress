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
use GoExpress\API\PDFLabelAnfrage;
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

		foreach ($orderIds as $orderId)
		{
			$order = $this->orderRepository->findOrderById($orderId);

            // gathering required data for registering the shipment

            /** @var Address $address */
            $address = $order->deliveryAddress;

			$receiverName1 = implode(' ', [$address->firstName, $address->lastName]);;
			$receiverName2 = null;
			if (strlen($address->companyName)) {
				$receiverName1 = $address->companyName;
				$receiverName2 = implode(' ', [$address->firstName, $address->lastName]);
			}
            $receiverStreet        = $address->street;
            $receiverNo            = $address->houseNumber;
			$receiverCountry       = $address->country->isoCode2;
            $receiverPostalCode    = $address->postalCode;
            $receiverTown          = $address->town;
			$receiverEmail 		   = $address->email;

			$receiverAddress = pluginApp(Empfaenger::class, [
				$receiverName1,
				$receiverName2,
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
				if ($package->weight)
				{
					$weight = $package->weight / 1000;
				}

                // determine packageType
                $packageType = $this->shippingPackageTypeRepositoryContract->findShippingPackageTypeById($package->packageId);

                // package dimensions
                //list($length, $width, $height) = $this->getPackageDimensions($packageType);

				$content = 'Lebensmittel';
				$internalId = $orderId.'_'.$package->id; // Kind of reference number
				$reference = substr('Auftragsnummer: '.$orderId, 0, 35);

				$parcelData = pluginApp(SendungsPosition::class, [
					1,
					$weight,
					$content
				]);

                try
                {
					// register shipment
					$shipmentData = pluginApp(SendungsDaten::class, [
						$receiverAddress,
						$senderAddress,
						$pickupDate,
						$parcelData,
						$reference
					]);

					$this->getLogger(__METHOD__)->debug('GoExpress::webservice.SendungsDaten', ['shipmentData' => json_encode($shipmentData)]);
					$shipment = $this->webservice->GOWebService_SendungsErstellung($shipmentData);
					$this->getLogger(__METHOD__)->debug('GoExpress::webservice.SendungsErstellung', ['shipment' => json_encode($shipment)]);

					$shipmentItems = [];
					if (isset($shipment->Sendung))
					{
						// request labels
						$labelData = pluginApp(PDFLabelAnfrage::class, [
							$shipment->Sendung->SendungsnummerAX4
						]);

						$this->getLogger(__METHOD__)->debug('GoExpress::webservice.PDFLabelAnfrage', ['labelData' => json_encode($labelData)]);
						$labels = $this->webservice->GOWebService_PDFLabel($labelData);
						$this->getLogger(__METHOD__)->debug('GoExpress::webservice.PDFs', ['labels' => count($labels->Sendung)]);

						// handles the response
						$shipmentItems = $this->handleAfterRegisterShipment($labels, $package->id);

						// adds result
						$this->createOrderResult[$orderId] = $this->buildResultArray(
							true,
							explode("\n", $this->webservice->__getLastResponseHeaders())[0],
							false,
							$shipmentItems
						);

						// saves shipping information
						$this->saveShippingInformation($orderId, $shipmentDate, $shipmentItems);
					}
					else
					{
						$this->createOrderResult[$orderId] = $this->buildResultArray(
                            false,
                            explode("\n", $this->webservice->__getLastResponseHeaders())[0],
                            false,
                            $shipmentItems
						);
					}

                }
                catch (\SoapFault $soapFault) {
					$this->getLogger(__METHOD__)->critical('GoExpress::webservice.SOAPerr', ['soapFault' => $soapFault->getMessage()]);
					$this->handleSoapFault($soapFault);
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
                            'shipment deleted',
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
     * Retrieves the label file from PDFs response and saves it in S3 storage
     *
	 * @param string $label
	 * @param string $key
	 * @return StorageObject
	 */
	private function saveLabelToS3($label, $key)
	{
		return $this->storageRepository->uploadObject('GoExpress', $key, $label);
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

		if ($parcelServicePreset)
		{
			return $parcelServicePreset;
		}
		else
		{
			return null;
		}
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
		$this->shippingInformationRepositoryContract->saveShippingInformation($data);
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
	 * Retrieve labels from S3 storage
	 * 
     * @param Request $request
     * @param array $orderIds
     * @return array
     */
    public function getLabels(Request $request, $orderIds)
    {
        $orderIds = $this->getOrderIds($request, $orderIds);
        $labels = array();
        foreach ($orderIds as $orderId)
        {
            $results = $this->orderShippingPackage->listOrderShippingPackages($orderId);
            /** @var OrderShippingPackage $result */
            foreach ($results as $result)
            {
				$labelKey = explode('/', $result->labelPath)[1];
				$this->getLogger(__METHOD__)->debug('GoExpress::webservice.S3Storage', ['labelKey' => $labelKey]);

                if ($this->storageRepository->doesObjectExist('GoExpress', $labelKey))
                {
                    $storageObject = $this->storageRepository->getObject('GoExpress', $labelKey);
                    $labels[] = $storageObject->body;
                }
            }
        }
        return $labels;
    }

	/**
     * Handling of response values, fires S3 storage and updates order shipping package
     *
	 * @param Sendung $response
	 * @param integer $packageId
	 * @return array
	 */
	private function handleAfterRegisterShipment($response, $packageId)
	{
		$shipmentItems = array();

		$shipmentData = array_shift($response->Sendung);

		if (strlen($shipmentData->Frachtbriefnummer) > 0 && isset($shipmentData->PDFs->Routerlabel))
		{
			$shipmentNumber = $shipmentData->Frachtbriefnummer;
			$this->getLogger(__METHOD__)->debug('GoExpress::webservice.S3Storage', ['length' => strlen($shipmentData->PDFs->Routerlabel)]);
			$storageObject = $this->saveLabelToS3(
                $shipmentData->PDFs->Routerlabel,
                $packageId.'.pdf'
			);
			$this->getLogger(__METHOD__)->debug('GoExpress::webservice.S3Storage', ['storageObject' => json_encode($storageObject)]);

            $shipmentItems[] = $this->buildShipmentItems(
                'path_to_pdf_in_S3',
                $shipmentNumber
			);

            $this->orderShippingPackage->updateOrderShippingPackage(
                $packageId,
                $this->buildPackageInfo($shipmentNumber, $storageObject->key)
            );
		}
		return $shipmentItems;
	}

    /**
     * @param $soapFault
     */
    private function handleSoapFault($soapFault)
    {
        echo $soapFault;
        echo $this->webservice->getLastRequest();
        echo $this->webservice->getLastRequestHeaders();
        echo $this->webservice->getLastResponse();
        echo $this->webservice->getLastResponseHeaders();
        exit;
    }

}
