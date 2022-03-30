<?php

namespace GoExpress\Migrations;

use GoExpress\Helpers\ShippingServiceProvider;
use Plenty\Modules\Order\Shipping\ServiceProvider\Contracts\ShippingServiceProviderRepositoryContract;
use Plenty\Plugin\Log\Loggable;

/**
 * Class CreateShippingServiceProvider
 * @package ShippingServiceProviderTemplate\Migrations
 */
class CreateShippingServiceProvider
{
    use Loggable;

    /*
     * @var ShippingServiceProviderRepositoryContract $shippingServiceProviderRepository
     */
    private $shippingServiceProviderRepository;

    /**
     * @param ShippingServiceProviderRepositoryContract $shippingServiceProviderRepository
     */
    public function __construct(ShippingServiceProviderRepositoryContract $shippingServiceProviderRepository)
    {
        $this->shippingServiceProviderRepository = $shippingServiceProviderRepository;
    }

    /**
     * @return void
     */
    public function run()
    {
        try {
            $this->shippingServiceProviderRepository->saveShippingServiceProvider(
                ShippingServiceProvider::PLUGIN_NAME,
                ShippingServiceProvider::SHIPPING_SERVICE_PROVIDER_NAME
            );
        } catch (\Exception $e) {
            $this->getLogger(ShippingServiceProvider::PLUGIN_NAME)->critical('Could not save or update shipping service provider');
        }
    }
}
