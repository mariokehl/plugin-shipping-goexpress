<?php
namespace GoExpress\Providers;

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
            'GoExpress',
            [
                'de' => 'GO! Express Webservice',
                'en' => 'GO! Express Webservice'
            ],
            [
                'GoExpress\\Controllers\\ShippingController@registerShipments',
                'GoExpress\\Controllers\\ShippingController@getLabels',
                'GoExpress\\Controllers\\ShippingController@deleteShipments',
            ]
        );
    }
}
