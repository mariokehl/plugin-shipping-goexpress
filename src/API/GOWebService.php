<?php

namespace GoExpress\API;

class GOWebService extends \SoapClient
{
    /**
     * @var array $classmap The defined classes
     * @access private
     */
    private static $classmap = [
        'Abholadresse' => 'GoExpress\API\Abholadresse',
        'Abholdatum' => 'GoExpress\API\Abholdatum',
        'Empfaenger' => 'GoExpress\API\Empfaenger',
        'SendungsDaten' => 'GoExpress\API\SendungsDaten',
        'SendungsPosition' => 'GoExpress\API\SendungsPosition',
        'Sendung' => 'GoExpress\API\Sendung',
        'Position' => 'GoExpress\API\Position',
        'Barcodes' => 'GoExpress\API\Barcodes',
        'PDFs' => 'GoExpress\API\PDFs'
    ];

    /**
     * @var array $wsdlFiles
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
        if (isset($options['wsdl']) === false) {
            $wsdl = self::$wsdlFiles[$mode] . '?wsdl';
        } else {
            $wsdl = $options['wsdl'][$mode] . '?wsdl';
        }

        foreach (self::$classmap as $key => $value) {
            if (!isset($options['classmap'][$key])) {
                $options['classmap'][$key] = $value;
            }
        }

        if (isset($options['features']) === false) {
            $options['features'] = SOAP_USE_XSI_ARRAY_TYPE;
        }

        $options['features'] = SOAP_SINGLE_ELEMENT_ARRAYS | SOAP_USE_XSI_ARRAY_TYPE;
        $options['trace'] = 1;

        parent::__construct($wsdl, $options);
    }

    /**
     * @param SendungsDaten $parameters
     * @access public
     * @return Sendung
     */
    public function GOWebService_SendungsErstellung(SendungsDaten $parameters)
    {
        return $this->__soapCall('SendungsDaten', [$parameters]);
    }

    /**
     * @param PDFLabelAnfrage $parameters
     * @access public
     * @return Sendung
     */
    public function GOWebService_PDFLabel($parameters)
    {
        return $this->__soapCall('PDFLabel', [$parameters]);
    }
}
