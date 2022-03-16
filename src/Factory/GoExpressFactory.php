<?php

namespace GoExpress\Factory;

use GoExpress\API\GOWebService;
use GoExpress\API\Abholadresse;
use GoExpress\API\Abholdatum;
use GoExpress\API\Empfaenger;
use GoExpress\API\Telefon;
use GoExpress\API\Ansprechpartner;
use GoExpress\API\SendungsPosition;
use GoExpress\API\SendungsDaten;
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
    const MINIMUM_FALLBACK_WEIGHT = 0.2;

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
     * @return Abholadresse
     */
    public function getAbholadresse()
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
     * @param Abholadresse $Abholadresse
     * @return void
     */
    public function setAbholadresse($Abholadresse)
    {
        $this->Abholadresse = $Abholadresse;
    }

    /**
     * WARNING: shipments can no longer be registered for the current day after 3 p.m.
     * If it is done anyway, it will result in a webservice error. Maybe this should be catched and the date adjusted accordingly!
     * 
     * @return Abholdatum
     */
    public function getAbholdatum()
    {
        /** @var Abholdatum $instance */
        $instance = pluginApp(Abholdatum::class, [
            date('d.m.Y'),
            $this->config->get('GoExpress.shipping.pickupTimeFrom', '15:30'),
            $this->config->get('GoExpress.shipping.pickupTimeTo', '18:30')
        ]);

        return $instance;
    }

    /**
     * @param Abholdatum $Abholdatum
     * @return void
     */
    public function setAbholdatum($Abholdatum)
    {
        $this->Abholdatum = $Abholdatum;
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
        $orderPropertyCollection = $this->orderPropertyRepositoryContract->findByOrderId($orderId, OrderPropertyType::EXTERNAL_ORDER_ID);
        $this->getLogger(__METHOD__)->debug('GoExpress::Plenty.OrderProperties', [
            'EXTERNAL_ORDER_ID' => json_encode($orderPropertyCollection)
        ]);
        if ($externalOrderId = $orderPropertyCollection->first()) {
            $this->Kundenreferenz = $externalOrderId->value;
        } else {
            $this->Kundenreferenz = $orderId;
        }
    }

    /**
     * @return void
     */
    public function setAbholhinweise()
    {
        $this->Abholhinweise = $this->config->get('GoExpress.shipping.pickupNotice', '');
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
            $this->Zustellhinweise
        ]);

        return $instance;
    }
}