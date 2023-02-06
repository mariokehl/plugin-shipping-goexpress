<?php

namespace GoExpress\Controllers;

use Plenty\Modules\Cloud\Storage\Models\StorageObject;
use Plenty\Modules\Order\Contracts\OrderRepositoryContract;
use Plenty\Modules\Order\Shipping\Contracts\ParcelServicePresetRepositoryContract;
use Plenty\Modules\Order\Shipping\Information\Contracts\ShippingInformationRepositoryContract;
use Plenty\Modules\Order\Shipping\Package\Contracts\OrderShippingPackageRepositoryContract;
use Plenty\Modules\Order\Shipping\ParcelService\Models\ParcelServicePreset;
use Plenty\Modules\Plugin\Storage\Contracts\StorageRepositoryContract;
use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;
use GoExpress\API\GOWebService;
use GoExpress\API\SendungsDaten;
use GoExpress\API\PDFLabelAnfrage;
use GoExpress\Factory\GoExpressFactory;
use Plenty\Modules\Order\Models\Order;
use Plenty\Plugin\Log\Loggable;
use Plenty\Plugin\Log\Reportable;

/**
 * Class ShippingController
 */
class ShippingController extends Controller
{
	use Loggable;
	use Reportable;

	/**
	 * @var OrderRepositoryContract $orderRepository
	 */
	private $orderRepository;

	/**
	 * @var OrderShippingPackageRepositoryContract $orderShippingPackage
	 */
	private $orderShippingPackage;

	/**
	 * @var ShippingInformationRepositoryContract $shippingInformationRepositoryContract
	 */
	private $shippingInformationRepositoryContract;

	/**
	 * @var StorageRepositoryContract $storageRepository
	 */
	private $storageRepository;

	/**
	 * @var array
	 */
	private $createOrderResult = [];
	
	/**
	 * @var GoExpressFactory $factory
	 */
	private $factory;

	/**
	 * @var GOWebService $webservice
	 */
	private $webservice;

	/**
	 * Plugin key
	 */
	const PLUGIN_KEY = 'GoExpress';

	/**
	 * ShipmentController constructor.
	 *
	 * @param OrderRepositoryContract $orderRepository
	 * @param OrderShippingPackageRepositoryContract $orderShippingPackage
	 * @param StorageRepositoryContract $storageRepository
	 * @param ShippingInformationRepositoryContract $shippingInformationRepositoryContract
	 * @param GoExpressFactory $factory
	 */
	public function __construct(
		OrderRepositoryContract $orderRepository,
		OrderShippingPackageRepositoryContract $orderShippingPackage,
		StorageRepositoryContract $storageRepository,
		ShippingInformationRepositoryContract $shippingInformationRepositoryContract,
		GoExpressFactory $factory
	) {
		$this->orderRepository = $orderRepository;
		$this->orderShippingPackage = $orderShippingPackage;
		$this->storageRepository = $storageRepository;

		$this->shippingInformationRepositoryContract = $shippingInformationRepositoryContract;

		$this->factory = $factory;
	}

	/**
	 * Registers shipment(s)
	 *
	 * @param Request $request
	 * @param array $orderIds
	 * @internal see GoExpressServiceProvider
	 * @return string
	 */
	public function registerShipments(Request $request, $orderIds)
	{
		$this->report(__METHOD__, 'GoExpress::Webservice.ClientID', null, ['GoExpressWS' => GOWebService::CLIENT_ID]);

		$orderIds = $this->getOrderIds($request, $orderIds);
		$orderIds = $this->getOpenOrderIds($orderIds);
		$shipmentDate = date('Y-m-d');

		// Initialize the webservice
		$this->webservice = $this->factory->getWebserviceInstance();

		// Initialize the factory
		$this->factory->init();

		foreach ($orderIds as $orderId) {
			$this->report(__METHOD__, 'GoExpress::Plenty.Order', null, ['orderId' => $orderId]);
			/** @var Order $order */
			$order = $this->orderRepository->findOrderById($orderId);
			$this->getLogger(__METHOD__)->addReference('orderId', $orderId)->debug('GoExpress::Plenty.Order', ['order' => $order]);

			// warehouse specific registering
			if ($this->factory->isWarehouseSenderEnabled()) {
				$this->webservice = $this->factory->getWebserviceInstanceForWarehouse($order->client, $order->warehouseSenderId);
				$this->factory->overwriteAbholadresseFromWarehouse($order->warehouseSender);
			}

			// gathering required data for registering the shipment
			$this->factory->setEmpfaenger($order->deliveryAddress, $order->billingAddress);
			if ($rc = $this->factory->validateEmpfaenger()) {
				$this->createOrderResult[$orderId] = $this->buildResultArray(
					false,
					$rc['error_msg'],
					false,
					[]
				);
				continue;
			}

			// gets order shipping packages from current order
			$packages = $this->orderShippingPackage->listOrderShippingPackages($orderId);
			$this->factory->setSendungsPosition($packages);

			// customer reference
			$this->factory->setKundenreferenz($orderId);

			// delivery notice
			$this->factory->setZustellhinweise($order->comments);

			try {
				/**
				 * Register shipment
				 * 
				 * @var SendungsDaten $shipmentData
				 */
				$shipmentData = $this->factory->getSendungsDaten();
				$this->getLogger(__METHOD__)->debug('GoExpress::Webservice.SendungsDaten', ['shipmentData' => json_encode($shipmentData)]);
				$shipment = $this->webservice->GOWebService_SendungsErstellung($shipmentData);
				$this->getLogger(__METHOD__)->debug('GoExpress::Webservice.SendungsErstellung', ['shipment' => json_encode($shipment)]);

				$shipmentItems = [];
				if (isset($shipment->Sendung)) {
					// request labels
					$labelData = pluginApp(PDFLabelAnfrage::class, [
						$shipment->Sendung->SendungsnummerAX4
					]);

					$this->getLogger(__METHOD__)->debug('GoExpress::Webservice.PDFLabelAnfrage', ['labelData' => json_encode($labelData)]);
					$labels = $this->webservice->GOWebService_PDFLabel($labelData);
					$this->getLogger(__METHOD__)->debug('GoExpress::Webservice.PDFs', ['labels' => count($labels->Sendung)]);

					// handles the response
					$shipmentItems = $this->handleAfterRegisterShipment($labels, $packages[0]->id);

					// adds result
					$this->createOrderResult[$orderId] = $this->buildResultArray(
						true,
						explode("\n", $this->webservice->__getLastResponseHeaders())[0],
						false,
						$shipmentItems
					);

					// saves shipping information
					$this->saveShippingInformation($orderId, $shipmentDate, $shipmentItems);
				} else {
					$this->createOrderResult[$orderId] = $this->buildResultArray(
						false,
						explode("\n", $this->webservice->__getLastResponseHeaders())[0],
						false,
						$shipmentItems
					);
				}
			} catch (\SoapFault $soapFault) {
				$this->getLogger(__METHOD__)->critical('GoExpress::Webservice.SOAPerr', ['soapFault' => json_encode($soapFault)]);
				$this->handleSoapFault($soapFault);
			}
		}

		// return all results to service
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
		return $this->storageRepository->uploadObject(self::PLUGIN_KEY, $key, $label);
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

		if ($parcelServicePreset) {
			return $parcelServicePreset;
		} else {
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
		$transactionIds = [];
		foreach ($shipmentItems as $shipmentItem) {
			$transactionIds[] = $shipmentItem['shipmentNumber'];
		}

		$shipmentAt = date(\DateTime::W3C, strtotime($shipmentDate));
		$registrationAt = date(\DateTime::W3C);

		$data = [
			'orderId' => $orderId,
			'transactionId' => implode(',', $transactionIds),
			'shippingServiceProvider' => self::PLUGIN_KEY,
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
		$openOrderIds = [];
		foreach ($orderIds as $orderId) {
			$shippingInformation = $this->shippingInformationRepositoryContract->getShippingInformationByOrderId($orderId);
			if ($shippingInformation->shippingStatus == null || $shippingInformation->shippingStatus == 'open') {
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
		return [
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
	 * @param mixed $orderIds
	 * @return array
	 */
	private function getOrderIds(Request $request, $orderIds)
	{
		if (is_numeric($orderIds)) {
			$orderIds = [$orderIds];
		} else if (!is_array($orderIds)) {
			$orderIds = $request->get('orderIds');
		}
		return $orderIds;
	}

	/**
	 * Returns the package dimensions by package type
	 *
	 * @param $packageType
	 * @deprecated since v0.1.2
	 * @return array
	 */
	private function getPackageDimensions($packageType): array
	{
		if ($packageType->length > 0 && $packageType->width > 0 && $packageType->height > 0) {
			$length = $packageType->length;
			$width = $packageType->width;
			$height = $packageType->height;
		} else {
			$length = null;
			$width = null;
			$height = null;
		}
		return [$length, $width, $height];
	}

	/**
	 * Retrieve labels from S3 storage
	 * 
	 * @param Request $request
	 * @param array $orderIds
	 * @internal see GoExpressServiceProvider
	 * @return array
	 */
	public function getLabels(Request $request, $orderIds)
	{
		$orderIds = $this->getOrderIds($request, $orderIds);
		$labels = [];
		foreach ($orderIds as $orderId) {
			$results = $this->orderShippingPackage->listOrderShippingPackages($orderId);
			/** @var OrderShippingPackage $result */
			foreach ($results as $result) {
				if (!strlen($result->labelPath)) {
					continue;
				}
				$labelKey = explode('/', $result->labelPath)[1];
				$this->getLogger(__METHOD__)->debug('GoExpress::Webservice.S3Storage', ['labelKey' => $labelKey]);

				if ($this->storageRepository->doesObjectExist(self::PLUGIN_KEY, $labelKey)) {
					$storageObject = $this->storageRepository->getObject(self::PLUGIN_KEY, $labelKey);
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
		$shipmentData = array_shift($response->Sendung);

		if ($this->factory->getPDFLabelFormat() === 'RouterlabelZebra') {
			$PDFLabel = $shipmentData->PDFs->RouterlabelZebra;
		} else {
			$PDFLabel = $shipmentData->PDFs->Routerlabel;
		}

		$shipmentItems = [];

		if (strlen($shipmentData->Frachtbriefnummer) > 0 && isset($PDFLabel)) {
			$shipmentNumber = $shipmentData->Frachtbriefnummer;
			$this->getLogger(__METHOD__)->debug('GoExpress::Webservice.S3Storage', ['length' => strlen($PDFLabel)]);
			$storageObject = $this->saveLabelToS3(
				$PDFLabel,
				$packageId . '.pdf'
			);
			$this->getLogger(__METHOD__)->debug('GoExpress::Webservice.S3Storage', ['storageObject' => json_encode($storageObject)]);

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
