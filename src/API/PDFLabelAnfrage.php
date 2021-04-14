<?php

namespace GoExpress\API;

class PDFLabelAnfrage {

    /**
     * @var string $Seitengroesse
     * @access public
     */
    public $Seitengroesse = null;

    /**
     * @var integer $SendungsnummerAX4
     * @access public
     */
    public $SendungsnummerAX4 = null;

    /**
     * @param integer $SendungsnummerAX4
     * @access public
     */
    public function __construct($SendungsnummerAX4)
    {
        $this->Seitengroesse = Seitengroesse::A6;
        $this->SendungsnummerAX4 = $SendungsnummerAX4;
    }

}