<?php

namespace GoExpress\API;

class Barcodes
{
    /**
     * @var string[] $BarcodeNr
     * @access public
     */
    public $BarcodeNr = null;

    /**
     * @param string[] $BarcodeNr
     * @access public
     */
    public function __construct($BarcodeNr)
    {
        $this->BarcodeNr = $BarcodeNr;
    }
}
