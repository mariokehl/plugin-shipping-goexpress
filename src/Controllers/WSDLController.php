<?php

namespace GoExpress\Controllers;

use Plenty\Modules\Plugin\Storage\Contracts\StorageRepositoryContract;
use Plenty\Plugin\Controller;
use Plenty\Plugin\Http\Request;
use Plenty\Plugin\Http\Response;

/**
 * Class WSDLController
 */
class WSDLController extends Controller
{

    /**
	 * @var Request
	 */
	private $request;

	/**
	 * @var Response
	 */
	private $response;

    /**
     * @var StorageRepositoryContract $storageRepository
     */
    private $storageRepository;

	/**
	 * ShipmentController constructor.
     *
	 * @param Request $request
     * @param Response $response
	 * @param StorageRepositoryContract $storageRepository
     */
	public function __construct(Request $request,
								Response $response,
								StorageRepositoryContract $storageRepository)
	{
        $this->request = $request;
        $this->response = $response;
        $this->storageRepository = $storageRepository;
    }

    /**
     * @param Request $request
     * @return string
     */
    public function provideDemoWsdl(Request $request)
    {
        $objs = $this->storageRepository->listObjects('GoExpress', '2021');

        return json_encode($objs->toArray());
    }

}