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
use Plenty\Modules\Account\Address\Models\AddressOption;
use Plenty\Modules\Comment\Models\Comment;
use Plenty\Modules\Order\Property\Contracts\OrderPropertyRepositoryContract;
use Plenty\Modules\Order\Property\Models\OrderPropertyType;
use Plenty\Modules\Order\Shipping\Countries\Contracts\CountryRepositoryContract;
use Plenty\Modules\Order\Shipping\Countries\Models\Country;
use Plenty\Modules\Order\Shipping\PackageType\Contracts\ShippingPackageTypeRepositoryContract;
use Plenty\Modules\System\Models\Webstore;
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
    const MINIMUM_FALLBACK_WEIGHT = 200;

    /**
     * Webservice constants
     */
    const WEBSERVICE_DATE_FORMAT = 'd.m.Y';
    const WEBSERVICE_TIME_FORMAT = 'H:i:s';

    /**
     * Markers
     */
    const MARKER_WAREHOUSE_PHONE = '$Lager[Telefon]';
    const MARKER_WAREHOUSE_EMAIL = '$Lager[E-Mail]';

    /**
     * Error codes
     */
    const ERROR_MISSING_HOUSENUMBER = 1668696676;
    const ERROR_INVALID_LENGTH_ZIP = 1668696704;

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
     * @var CountryRepositoryContract $countryRepositoryContract
     */
    private $countryRepositoryContract;

    /**
     * GoExpressFactory constructor.
     *
     * @param OrderPropertyRepositoryContract $orderPropertyRepositoryContract
     * @param ShippingPackageTypeRepositoryContract $shippingPackageTypeRepositoryContract
     * @param CountryRepositoryContract $countryRepositoryContract
     * @param ConfigRepository $config
     */
    public function __construct(
        OrderPropertyRepositoryContract $orderPropertyRepositoryContract,
        ShippingPackageTypeRepositoryContract $shippingPackageTypeRepositoryContract,
        CountryRepositoryContract $countryRepositoryContract,
        ConfigRepository $config
    ) {
        $this->orderPropertyRepositoryContract = $orderPropertyRepositoryContract;
        $this->shippingPackageTypeRepositoryContract = $shippingPackageTypeRepositoryContract;
        $this->countryRepositoryContract = $countryRepositoryContract;
        $this->config = $config;
    }

    /**
     * AX4 Versender ID
     *
     * @var integer
     */
    private $VersenderId = 0;

    /**
     * Firmenname des Versenders (in Abholadresse)
     *
     * @var string
     */
    private $Versender = '';

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
     * @var array
     */
    private $markers = [];

    /**
     * Returns new instance of GO! web service
     *
     * @param string $username
     * @param string $password
     * @return GOWebService
     */
    public function getWebserviceInstance($username = '', $password = ''): GOWebService
    {
        // Get credentials by UI config
        $partnerCredentialsUser = !empty($username) ? $username : $this->config->get('GoExpress.global.username');
        $partnerCredentialsPass = !empty($password) ? $password : $this->config->get('GoExpress.global.password');

        $this->getLogger(__METHOD__)->debug('GoExpress::Webservice.WSinit', [
            'mode' => $this->config->get('GoExpress.global.mode'),
            'wsdl' => [
                $this->config->get('GoExpress.global.container.webserviceDemoUri'),
                $this->config->get('GoExpress.global.container.webserviceFinalUri')
            ],
            'username' => $partnerCredentialsUser,
            'password' => implode('', array_fill(0, strlen($partnerCredentialsPass), '*'))
        ]);

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
     * @param Webstore $webstore
     * @param integer $warehouseSenderId
     * @return GOWebService
     */
    public function getWebserviceInstanceForWarehouse($webstore, $warehouseSenderId): GOWebService
    {
        $warehouseConfig = json_decode($this->config->get('GoExpress.advanced.warehouseSenderConfig'), true);
        if (!is_array($warehouseConfig)) {
            $this->getLogger(__METHOD__)->error('GoExpress::Plenty.WarehouseConfigInvalid');
        }

        $targetKey = '';
        $clientOverwriteKey = "client:{$webstore->id},{$warehouseSenderId}";
        $hasOverwrite = array_key_exists($clientOverwriteKey, $warehouseConfig);

        if ($hasOverwrite) $targetKey = $clientOverwriteKey;
        else $targetKey = $warehouseSenderId;

        if (array_key_exists($targetKey, $warehouseConfig)) {

            $this->setVersender($warehouseConfig[$targetKey]['sender']['company_name']);
            $this->setVersenderId($warehouseConfig[$targetKey]['sender']['ax4_id']);

            $this->getLogger(__METHOD__)->debug('GoExpress::Plenty.Warehouse', [
                'client' => $webstore,
                'warehouseSenderId' => $warehouseSenderId,
                'warehouseConfig' => $this->getVersender() . '|' . $this->getVersenderId()
            ]);

            return $this->getWebserviceInstance(
                $warehouseConfig[$targetKey]['auth']['user'],
                $warehouseConfig[$targetKey]['auth']['pass']
            );
        } else {
            $this->getLogger(__METHOD__)->warning('GoExpress::Plenty.WarehouseConfigNotFound', [
                'targetKey' => $targetKey
            ]);
        }

        return $this->getWebserviceInstance();
    }

    /**
     * Returns the XML tag to use for PDF labels.
     *
     * @return string
     */
    public function getPDFLabelFormat(): string
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
        $this->setVersenderId(intval($this->config->get('GoExpress.sender.senderId', '')));
        $this->setVersender($this->config->get('GoExpress.sender.senderName', ''));
        $this->setAbholadresse($this->initAbholadresse());
        $this->setAbholdatum($this->initAbholdatum());
        $this->setAbholhinweise($this->config->get('GoExpress.shipping.pickupNotice', ''));
        $this->setZustelldatum($this->initZustelldatum());
    }

    /**
     * @return boolean
     */
    public function isWarehouseSenderEnabled(): bool
    {
        $enableWarehouseSender = $this->config->get('GoExpress.advanced.enableWarehouseSender');
        if ($enableWarehouseSender === 'true') {
            return true;
        }

        return false;
    }

    /**
     * @return integer
     */
    public function getVersenderId(): int
    {
        return $this->VersenderId;
    }

    /**
     * @param integer $VersenderId
     * @return self
     */
    public function setVersenderId($VersenderId): self
    {
        $this->VersenderId = $VersenderId;

        return $this;
    }

    /**
     * @return string
     */
    public function getVersender(): string
    {
        return $this->Versender;
    }

    /**
     * @param string $Versender
     * @return self
     */
    public function setVersender($Versender): self
    {
        $this->Versender = $Versender;

        return $this;
    }

    /**
     * @return Abholadresse
     */
    private function initAbholadresse(): Abholadresse
    {
        $senderStreet = $this->config->get('GoExpress.sender.senderStreet', '');
        $senderNo = $this->config->get('GoExpress.sender.senderNo', '');
        $senderCountry = $this->config->get('GoExpress.sender.senderCountry', '');
        $senderPostalCode = $this->config->get('GoExpress.sender.senderPostalCode', '');
        $senderTown = $this->config->get('GoExpress.sender.senderTown', '');

        /** @var Abholadresse $instance */
        $instance = pluginApp(Abholadresse::class, [
            $this->getVersender(),
            $senderStreet,
            $senderNo,
            $senderCountry,
            $senderPostalCode,
            $senderTown
        ]);

        return $instance;
    }

    /**
     * @param Abholadresse $Abholadresse
     * @return self
     */
    private function setAbholadresse($Abholadresse): self
    {
        $this->Abholadresse = $Abholadresse;

        return $this;
    }

    /**
     * @param mixed $warehouseSender
     * @return self
     */
    public function overwriteAbholadresseFromWarehouse($warehouseSender): self
    {
        $this->getLogger(__METHOD__)->debug('GoExpress::Plenty.WarehouseAddresses', ['warehouseSender' => $warehouseSender]);

        /** @var Country $warehouseSenderCountry */
        $warehouseSenderCountry = $this->countryRepositoryContract->getCountryById($warehouseSender->address->countryId);

        /** @var Abholadresse $instance */
        $instance = pluginApp(Abholadresse::class, [
            $this->getVersender(),
            $warehouseSender->address->address1,
            $warehouseSender->address->address2,
            $warehouseSenderCountry->isoCode2,
            $warehouseSender->address->postalCode,
            $warehouseSender->address->town
        ]);

        $this->setAbholadresse($instance);

        foreach ($warehouseSender->address->options as $addressOption) {
            switch ($addressOption->typeId) {
                case AddressOption::TYPE_TELEPHONE:
                    $this->markers[self::MARKER_WAREHOUSE_PHONE] = $addressOption->value;
                    break;
                case AddressOption::TYPE_EMAIL:
                    $this->markers[self::MARKER_WAREHOUSE_EMAIL] = $addressOption->value;
                    break;
            }
        }

        $this->setAbholhinweise($this->getAbholhinweise());

        return $this;
    }

    /**
     * @return Abholdatum
     */
    private function initAbholdatum(): Abholdatum
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
            $latestPickup->modify('-' . intval($pickupLeadTime) . ' minutes');
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
     * @param Abholdatum $Abholdatum
     * @return self
     */
    private function setAbholdatum($Abholdatum): self
    {
        $this->Abholdatum = $Abholdatum;

        return $this;
    }

    /**
     * @return string
     */
    private function getAbholhinweise(): string
    {
        return $this->Abholhinweise;
    }

    /**
     * @param string $Abholhinweise
     * @return self
     */
    private function setAbholhinweise($Abholhinweise): self
    {
        $this->Abholhinweise = $this->replaceMarkers($Abholhinweise);

        return $this;
    }

    /**
     * @param Address $deliveryAddress
     * @param Address $billingAddress
     * @return self
     */
    public function setEmpfaenger($deliveryAddress, $billingAddress): self
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

            // Also transfer the telephone number in the Abteilung field (visible on the shipping label)
            $enablePhoneNumberOnShippingLabel = $this->config->get('GoExpress.shipping.enablePhoneNumberOnShippingLabel');
            if ($enablePhoneNumberOnShippingLabel === 'true') {
                $this->Empfaenger->setAbteilung($phoneNumber);
            }
        }

        return $this;
    }

    /**
     * @return null|array
     */
    public function validateEmpfaenger()
    {
        $validateDeliveryAddress = $this->config->get('GoExpress.advanced.validateDeliveryAddress');
        if (is_null($validateDeliveryAddress) || $validateDeliveryAddress === 'false') return false;

        if (!strlen($this->Empfaenger->Hausnummer)) {
            return [
                'error_code' => self::ERROR_MISSING_HOUSENUMBER,
                'error_msg' => 'Hausnummer fehlt, Lieferadresse korrigieren!'
            ];
        }

        if (($this->Empfaenger->Land == 'DE' && strlen($this->Empfaenger->Postleitzahl) != 5) ||
            ($this->Empfaenger->Land == 'AT' && strlen($this->Empfaenger->Postleitzahl) != 4)
        ) {
            return [
                'error_code' => self::ERROR_INVALID_LENGTH_ZIP,
                'error_msg' => 'PLZ ' . $this->Empfaenger->Postleitzahl . ' hat falsche Länge für Lieferland ' . $this->Empfaenger->Land . ', Lieferadresse korrigieren!'
            ];
        }

        return null;
    }

    /**
     * @param array $packages
     * @return self
     */
    public function setSendungsPosition($packages): self
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
            $packageWeights ? $packageWeights : $this->getMinimumFallbackWeight(),
            $firstPackageName
        ]);

        $this->SendungsPosition = $parcelData;

        return $this;
    }

    /**
     * @return float
     */
    private function getMinimumFallbackWeight(): float
    {
        $grams = $this->config->get('GoExpress.shipping.minimumWeight', self::MINIMUM_FALLBACK_WEIGHT);
        $grams = intval($grams);
        if ($grams > 0) {
            $kilograms = sprintf('%.2f', $grams / 1000);
        } else {
            $kilograms = '0.2';
        }

        return floatval($kilograms);
    }

    /**
     * @param integer $orderId
     * @return self
     */
    public function setKundenreferenz($orderId): self
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

        return $this;
    }

    /**
     * Overwrite default delivery notice if necessary (either per package or order comment must contain @goexpress)
     * 
     * @param array $comments
     * @return self
     */
    public function setZustellhinweise($comments): self
    {
        /**
         * 1. Default delivery notice
         */
        $deliveryNotice = $this->config->get('GoExpress.shipping.deliveryNotice', '');

        /**
         * 2. Delivery instructions depending on the package content
         */
        if ($packageOverwrite = $this->getPaketZustellhinweis()) {
            $deliveryNotice = $packageOverwrite;
        }

        /**
         * 3. Individual delivery information per shipment
         * 
         * @var Comment $comment
         */
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

        $this->Zustellhinweise = $this->replaceMarkers($deliveryNotice);

        return $this;
    }

    /**
     * Delivery notice per package instead of shipment level
     *
     * @return mixed
     */
    private function getPaketZustellhinweis()
    {
        if ($this->SendungsPosition && $this->SendungsPosition instanceof SendungsPosition) {
            $content = $this->SendungsPosition->Inhalt;
            $mapping = [];
            $lines = preg_split("/\r\n|\n|\r/", $this->config->get('GoExpress.shipping.packageDeliveryNotice'));
            foreach ($lines as $val) {
                $tmp = explode('=', $val);
                $mapping[$tmp[0]] = $tmp[1];
            }
            $packageNames = array_keys($mapping);
            foreach ($packageNames as $pkg) {
                if (strpos($content, $pkg) !== false) {
                    return $mapping[$pkg];
                }
            }
            return '';
        } else {
            return false;
        }
    }

    /**
     * @return mixed
     */
    public function initZustelldatum()
    {
        $enableSaturdayDelivery = $this->config->get('GoExpress.shipping.enableSaturdayDelivery');
        $this->getLogger(__METHOD__)->info('GoExpress::Webservice.Zustelldatum', ['enableSaturdayDelivery' => $enableSaturdayDelivery]);

        if (is_null($enableSaturdayDelivery) || $enableSaturdayDelivery === 'false') return;

        $this->getLogger(__METHOD__)->info('GoExpress::Config.ShippingEnableSaturdayDelivery');

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
     * @param mixed $Zustelldatum
     * @return self
     */
    private function setZustelldatum($Zustelldatum): self
    {
        $this->Zustelldatum = $Zustelldatum;

        return $this;
    }

    /**
     * @return SendungsDaten
     */
    public function getSendungsDaten(): SendungsDaten
    {
        /** @var SendungsDaten $instance */
        $instance = pluginApp(SendungsDaten::class, [
            $this->VersenderId,
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

    /**
     * @param string $subject
     * @return string
     */
    private function replaceMarkers($subject): string
    {
        foreach ($this->markers as $key => $value) {
            $subject = str_replace($key, $value, $subject);
        }

        return $subject;
    }
}
