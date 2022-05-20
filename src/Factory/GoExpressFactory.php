<?php

namespace GoExpress\Factory;

use Carbon\Carbon;
use GoExpress\API\GOWebService;
use GoExpress\API\Abholadresse;
use GoExpress\API\Abholdatum;
use GoExpress\API\Empfaenger;
use GoExpress\API\Telefon;
use GoExpress\API\Ansprechpartner;
use GoExpress\API\SendungsPosition;
use GoExpress\API\SendungsDaten;
use GoExpress\API\Zustelldatum;
use Plenty\Modules\Account\Address\Models\Address;
use Plenty\Modules\Comment\Models\Comment;
use Plenty\Modules\Order\Property\Contracts\OrderPropertyRepositoryContract;
use Plenty\Modules\Order\Property\Models\OrderPropertyType;
use Plenty\Modules\Order\Shipping\PackageType\Contracts\ShippingPackageTypeRepositoryContract;
use Plenty\Plugin\ConfigRepository;
use Plenty\Plugin\Log\Loggable;

/**
 * Class GoExpressFactory
 */
class GoExpressFactory
{
    use Loggable;

    /**
     * Shipment constants
     */
    const DEFAULT_PACKAGE_NAME = 'Wareninhalt';
    const MINIMUM_FALLBACK_WEIGHT = 0.2; // TODO: Standardgewicht über Konfiguration einstellbar machen

    /**
     * Webservice constants
     */
    const WEBSERVICE_DATE_FORMAT = 'd.m.Y';
    const WEBSERVICE_TIME_FORMAT = 'H:i:s';

    /**
     * @var ConfigRepository $config
     */
    private $config;

    /**
     * @var OrderPropertyRepositoryContract $orderPropertyRepositoryContract
     */
    private $orderPropertyRepositoryContract;

    /**
     * @var ShippingPackageTypeRepositoryContract $shippingPackageTypeRepositoryContract
     */
    private $shippingPackageTypeRepositoryContract;

    /**
     * GoExpressFactory constructor.
     *
     * @param OrderPropertyRepositoryContract $orderPropertyRepositoryContract
     * @param ShippingPackageTypeRepositoryContract $shippingPackageTypeRepositoryContract
     * @param ConfigRepository $config
     */
    public function __construct(
        OrderPropertyRepositoryContract $orderPropertyRepositoryContract,
        ShippingPackageTypeRepositoryContract $shippingPackageTypeRepositoryContract,
        ConfigRepository $config
    ) {
        $this->orderPropertyRepositoryContract = $orderPropertyRepositoryContract;
        $this->shippingPackageTypeRepositoryContract = $shippingPackageTypeRepositoryContract;
        $this->config = $config;
    }

    /**
     * @var Abholadresse $Abholadresse
     */
    private $Abholadresse;

    /**
     * @var Abholdatum $Abholdatum
     */
    private $Abholdatum;

    /**
     * @var Empfaenger $Empfaenger
     */
    private $Empfaenger;

    /**
     * @var SendungsPosition $SendungsPosition
     */
    private $SendungsPosition;

    /**
     * @var string $Kundenreferenz
     */
    private $Kundenreferenz;

    /**
     * @var string $Abholhinweise
     */
    private $Abholhinweise;

    /**
     * @var string $Zustellhinweise
     */
    private $Zustellhinweise;

    /**
     * @var Zustelldatum|null $Zustelldatum
     */
    private $Zustelldatum;

    /**
     * Returns new instance of GO! web service
     *
     * @return GOWebService
     */
    public function getWebserviceInstance()
    {
        // Get credentials by UI config
        $partnerCredentialsUser = $this->config->get('GoExpress.global.username');
        $partnerCredentialsPass = $this->config->get('GoExpress.global.password');

        /** @var GOWebService $instance */
        $instance = pluginApp(GOWebService::class, [
            [
                'wsdl' => [
                    'DEMO' => $this->config->get('GoExpress.global.container.webserviceDemoUri'),
                    'FINAL' => $this->config->get('GoExpress.global.container.webserviceFinalUri')
                ],
                'login' => $partnerCredentialsUser,
                'password' => $partnerCredentialsPass
            ],
            $this->config->get('GoExpress.global.mode')
        ]);

        return $instance;
    }

    /**
     * Returns the XML tag to use for PDF labels.
     *
     * @return string
     */
    public function getPDFLabelFormat()
    {
        return $this->config->get('GoExpress.advanced.pdfLabelTag');
    }

    /**
     * Initialize the factory
     *
     * @return void
     */
    public function init()
    {
        $this->Abholadresse = $this->getAbholadresse();
        $this->Abholdatum = $this->getAbholdatum();
        $this->Abholhinweise = $this->getAbholhinweise();
        $this->Zustelldatum = $this->getZustelldatum();
    }

    /**
     * @return Abholadresse
     */
    private function getAbholadresse()
    {
        $senderName = $this->config->get('GoExpress.sender.senderName', '');
        $senderStreet = $this->config->get('GoExpress.sender.senderStreet', '');
        $senderNo = $this->config->get('GoExpress.sender.senderNo', '');
        $senderCountry = $this->config->get('GoExpress.sender.senderCountry', '');
        $senderPostalCode = $this->config->get('GoExpress.sender.senderPostalCode', '');
        $senderTown = $this->config->get('GoExpress.sender.senderTown', '');

        /** @var Abholadresse $instance */
        $instance = pluginApp(Abholadresse::class, [
            $senderName,
            $senderStreet,
            $senderNo,
            $senderCountry,
            $senderPostalCode,
            $senderTown
        ]);

        return $instance;
    }

    /**
     * @return Abholdatum
     */
    private function getAbholdatum()
    {
        // Default pickup date is today, on weekends the next business day (monday)
        $now = Carbon::now()->isWeekday() ? Carbon::now() : Carbon::now()->startOfWeek()->addWeek();
        $this->getLogger(__METHOD__)->debug('GoExpress::Plenty.Carbon', ['now' => $now]);

        $pickupTimeFrom = $this->config->get('GoExpress.shipping.pickupTimeFrom', '15:30');
        $pickupTimeTo = $this->config->get('GoExpress.shipping.pickupTimeTo', '18:30');

        // Should shipments be moved to next working day if it is too late?
        if (Carbon::now()->isWeekday() && ($pickupLeadTime = $this->config->get('GoExpress.shipping.pickupLeadTime'))) {
            $latestPickup = Carbon::now();
            list($hour, $minute) = explode(':', $pickupTimeFrom);
            $latestPickup->setTime(intval($hour), intval($minute));
            $latestPickup->modify('-'. intval($pickupLeadTime) . ' minutes');
            if ($isTooLate = ($now > $latestPickup)) {
                // Finds the next weekday from a specific date (not including Saturday or Sunday; Holidays aren't considered!)
                $now->addWeekday();
            }
            $this->getLogger(__METHOD__)->debug('GoExpress::Webservice.Abholzeit', [
                'latestPickup' => $latestPickup->format(self::WEBSERVICE_DATE_FORMAT . ' ' . self::WEBSERVICE_TIME_FORMAT),
                'isTooLate' => $isTooLate
            ]);
        }

        $pickupDate = $now->format(self::WEBSERVICE_DATE_FORMAT);
        $this->getLogger(__METHOD__)->debug('GoExpress::Webservice.Abholdatum', ['pickupDate' => $pickupDate]);

        /** @var Abholdatum $instance */
        $instance = pluginApp(Abholdatum::class, [
            $pickupDate,
            $pickupTimeFrom,
            $pickupTimeTo
        ]);

        return $instance;
    }

    /**
     * @return string
     */
    private function getAbholhinweise()
    {
        // TODO: Telefonnummer aus Lager dem Abholhinweis voranstellen
        //...

        return $this->config->get('GoExpress.shipping.pickupNotice', '');
    }

    /**
     * @param Address $deliveryAddress
     * @param Address $billingAddress
     * @return void
     */
    public function setEmpfaenger($deliveryAddress, $billingAddress)
    {
        $receiverName1 = implode(' ', [$deliveryAddress->firstName, $deliveryAddress->lastName]);
        $receiverName2 = null;
        if (strlen($deliveryAddress->companyName)) {
            $receiverName2 = $receiverName1;
            $receiverName1 = $deliveryAddress->companyName;
        }
        $receiverStreet = $deliveryAddress->street;
        $receiverNo = $deliveryAddress->houseNumber;
        $receiverCountry = $deliveryAddress->country->isoCode2;
        $receiverPostalCode = $deliveryAddress->postalCode;
        $receiverTown = $deliveryAddress->town;
        $receiverEmail = $deliveryAddress->email;

        /** @var Empfaenger $receiverAddress */
        $receiverAddress = pluginApp(Empfaenger::class, [
            $receiverName1,
            $receiverStreet,
            $receiverNo,
            $receiverCountry,
            $receiverPostalCode,
            $receiverTown,
            $receiverEmail,
            $receiverName2
        ]);
        $this->Empfaenger = $receiverAddress;

        // fix phone number missing in delivery address
        if (strlen($deliveryAddress->phone)) {
            $phoneNumber = $deliveryAddress->phone;
        } else {
            $phoneNumber = $billingAddress->phone;
        }

        // set phone number if any exists 
        if (strlen($phoneNumber)) {
            /** @var Telefon $receiverPhone */
            $receiverPhone = pluginApp(Telefon::class);
            $receiverPhone->parseString($phoneNumber, $receiverCountry);

            /** @var Ansprechpartner $receiverContact */
            $receiverContact = pluginApp(Ansprechpartner::class, [$receiverPhone]);
            $this->Empfaenger->setAnsprechpartner($receiverContact);

            // TODO: Telefonnummer optional zusätzlich im Feld Strasse2 übertragen
            //...
        }
    }

    /**
     * @param array $packages
     * @return void
     */
    public function setSendungsPosition($packages)
    {
        // package sums
        $firstPackageName = self::DEFAULT_PACKAGE_NAME;
        $packageWeights = 0.0; // kilograms

        // iterating through packages
        $packageCount = 0;
        foreach ($packages as $key => $package) {
            if ($packageCount === 0) {
                $packageType = $this->shippingPackageTypeRepositoryContract->findShippingPackageTypeById($package->packageId);
                $firstPackageName = $packageType->name;
            }
            if ($package->weight) {
                $packageWeights += $package->weight / 1000;
            }
            $packageCount++;
        }

        /** @var SendungsPosition $instance */
        $parcelData = pluginApp(SendungsPosition::class, [
            $packageCount,
            $packageWeights ? $packageWeights : self::MINIMUM_FALLBACK_WEIGHT,
            $firstPackageName
        ]);
        $this->SendungsPosition = $parcelData;
    }

    /**
     * @param integer $orderId
     * @return void
     */
    public function setKundenreferenz($orderId)
    {
        // Default and fallback value
        $this->Kundenreferenz = $orderId;

        // Get the property
        $orderPropertyCollection = $this->orderPropertyRepositoryContract->findByOrderId($orderId, OrderPropertyType::EXTERNAL_ORDER_ID);
        $this->getLogger(__METHOD__)->debug('GoExpress::Plenty.OrderProperties', [
            'EXTERNAL_ORDER_ID' => json_encode($orderPropertyCollection)
        ]);

        // The selected transfer mode
        $mode = $this->config->get('GoExpress.shipping.customerReference', 'order_number');

        if (
            $orderPropertyCollection->first() &&
            in_array($mode, ['external_order_number', 'both_order_numbers'])
        ) {
            $externalOrderId = $orderPropertyCollection->first()->value;
            if ($mode === 'external_order_number') {
                $this->Kundenreferenz = $externalOrderId;
            } else {
                $this->Kundenreferenz = implode(' ', [$orderId, '/', $externalOrderId]);
            }
        }
    }

    /**
     * Overwrite default delivery notice if necessary (comment must contain @goexpress)
     * 
     * @param array $comments
     * @return void
     */
    public function setZustellhinweise($comments)
    {
        $deliveryNotice = $this->config->get('GoExpress.shipping.deliveryNotice', '');

        // TODO: Zustellhinweise in Abhängigkeit zum Inhalt setzen
        //...

        /** @var Comment $comment */
        foreach ($comments as $comment) {
            if (!$comment->userId || !stripos($comment->text, '@goexpress')) {
                continue;
            } else {
                $commentText = strip_tags($comment->text);
                $commentText = str_replace('@goexpress', '', $commentText);
                $commentText = trim($commentText);
                $commentText = substr($commentText, 0, 128);
                $deliveryNotice = $commentText;
                break;
            }
        }

        $this->Zustellhinweise = $deliveryNotice;
    }

    /**
     * @return mixed
     */
    public function getZustelldatum()
    {
        $enableSaturdayDelivery = $this->config->get('GoExpress.shipping.enableSaturdayDelivery');
        if ($enableSaturdayDelivery === 'false') return;

        // if it's too late to register shipment on friday, it has to be moved to next monday and skipped for saturday delivery
        $pickupDate = Carbon::createFromFormat(self::WEBSERVICE_DATE_FORMAT, $this->Abholdatum->Datum);

        // gets representation of the day of the week as string (1=monday..., 6=saturday, 7=sunday)
        $isoDayOfWeek = $pickupDate->format('N');
        $isFriday = ($isoDayOfWeek === '5');
        $nextDay = $pickupDate->modify('+1 day')->format(self::WEBSERVICE_DATE_FORMAT);

        $this->getLogger(__METHOD__)->debug('GoExpress::Webservice.Zustelldatum', [
            'pickup' => [
                'isoDayOfWeek' => $isoDayOfWeek,
                'isFriday' => $isFriday
            ],
            'delivery' => [
                'nextDay' => $nextDay
            ]
        ]);

        if ($isFriday) {
            /** @var Zustelldatum $instance */
            $instance = pluginApp(Zustelldatum::class, [$nextDay]);
        }

        return isset($instance) ? $instance : null;
    }

    /**
     * @return SendungsDaten
     */
    public function getSendungsDaten()
    {
        /** @var SendungsDaten $instance */
        $instance = pluginApp(SendungsDaten::class, [
            intval($this->config->get('GoExpress.sender.senderId', '')),
            $this->Empfaenger,
            $this->Abholadresse,
            $this->Abholdatum,
            $this->SendungsPosition,
            $this->Kundenreferenz,
            $this->Abholhinweise,
            $this->Zustellhinweise,
            $this->Zustelldatum
        ]);

        return $instance;
    }
}
