<?php

namespace GoExpress\Providers;

use GoExpress\Helpers\ShippingServiceProvider;
use Plenty\Log\Services\ReferenceContainer;
use Plenty\Log\Exceptions\ReferenceTypeException;
use Plenty\Modules\Order\Shipping\ServiceProvider\Services\ShippingServiceProviderService;
use Plenty\Plugin\ServiceProvider;

/**
 * Class GoExpressServiceProvider
 * @package GoExpress\Providers
 */
class GoExpressServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     */
    public function register()
    {
        //$this->getApplication()->register(GoExpressRouteServiceProvider::class);
    }

    /**
     * @param ReferenceContainer $referenceContainer
     * @param ShippingServiceProviderService $shippingServiceProviderService
     * @return void
     */
    public function boot(
        ReferenceContainer $referenceContainer,
        ShippingServiceProviderService $shippingServiceProviderService
    ) {
        // Register reference types for logs
        try {
            $referenceContainer->add(['GoExpressWS' => 'GoExpressWS']);
        } catch (ReferenceTypeException $ex) {}

        // Register shipping provider
        $shippingServiceProviderService->registerShippingProvider(
            ShippingServiceProvider::PLUGIN_NAME,
            [
                'de' => ShippingServiceProvider::SHIPPING_SERVICE_PROVIDER_NAME,
                'en' => ShippingServiceProvider::SHIPPING_SERVICE_PROVIDER_NAME
            ],
            [
                'GoExpress\\Controllers\\ShippingController@registerShipments',
                'GoExpress\\Controllers\\ShippingController@getLabels'
            ]
        );
    }
}
