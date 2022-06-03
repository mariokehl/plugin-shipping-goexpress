<?php

namespace GoExpress\Providers;

use GoExpress\Helpers\ShippingServiceProvider;
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

    public function boot(ShippingServiceProviderService $shippingServiceProviderService)
    {
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
