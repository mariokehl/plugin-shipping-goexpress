<?php
namespace GoExpress\Providers;

use Plenty\Plugin\RouteServiceProvider;
use Plenty\Plugin\Routing\Router;

/**
 * Class GoExpressRouteServiceProvider
 * @package GoExpress\Providers
 */
class GoExpressRouteServiceProvider extends RouteServiceProvider
{
    /**
     * @param Router $router
     */
    public function map(Router $router)
    {
        $router->get('goex/wsdl/demo', 'GoExpress\Controllers\WSDLController@provideDemoWsdl');
  	}

}
