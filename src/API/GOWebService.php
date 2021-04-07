<?php

namespace GoExpress\API;

use Plenty\Plugin\Log\Loggable;

/*
include_once('SendungsDaten.php');
include_once('Abholadresse.php');
include_once('Abholdatum.php');
include_once('Empfaenger.php');
include_once('SendungsPosition.php');
*/

class GOWebService extends \SoapClient
{

    /**
     * @var array $classmap The defined classes
     * @access private
     */
    private static $classmap = [
        'SendungsDaten' => 'GoExpress\API\SendungsDaten',
        'Abholadresse' => 'GoExpress\API\Abholadresse',
        'Abholdatum' => 'GoExpress\API\Abholdatum',
        'Empfaenger' => 'GoExpress\API\Empfaenger',
        'SendungsPosition' => 'GoExpress\API\SendungsPosition'
    ];

    /**
     * @var string wsdlFile
     * @access private
     */
    private static $wsdlFiles = [
        'DEMO' => 'https://wsdemo.ax4.com/ws/4020/GOSchaefer/SendungsDaten',
        'FINAL' => 'https://webservice.ax4.com/ws/4020/GOSchaefer/SendungsDaten'
    ];

    /**
     * @param array $options A array of config values
     * @param string $mode The environment to use (DEMO|FINAL)
     * @access public
     */
    public function __construct(array $options = array(), $mode = 'DEMO')
    {
        $wsdl = self::$wsdlFiles[$mode].'?wsdl';

        foreach (self::$classmap as $key => $value) {
            if (!isset($options['classmap'][$key])) {
            $options['classmap'][$key] = $value;
            }
        }
      
        if (isset($options['features']) == false) {
            $options['features'] = SOAP_USE_XSI_ARRAY_TYPE;
        }

        $options['features'] = SOAP_SINGLE_ELEMENT_ARRAYS|SOAP_USE_XSI_ARRAY_TYPE;
        $options['trace'] = 1;

        parent::__construct($wsdl, $options);
    }

    /**
     * @param SendungsDaten $parameters
     * @access public
     */
    public function GOWebService_SendungsErstellung(SendungsDaten $parameters)
    {
        return $this->__soapCall('SendungsDaten', [$parameters]);
    }
}