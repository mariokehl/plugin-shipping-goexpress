<?php

namespace GoExpress\API;

class Abholdatum {

    /**
     * @var string $Datum
     * @access public
     */
    public $Datum = null;

    /**
     * @var string $UhrzeitVon
     * @access public
     */
    public $UhrzeitVon = null;

    /**
     * @param string $Datum
     * @param string $UhrzeitVon
     * @access public
     */
    public function __construct($Datum, $UhrzeitVon)
    {
      $this->Datum = $Datum;
      $this->UhrzeitVon = $UhrzeitVon;
    }

}