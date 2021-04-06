<?php

namespace GoExpress\API;

class Abholdatum {

    /**
     * @var string $Datum
     * @access public
     */
    public $Datum = null;

    /**
     * @param string $Datum
     * @access public
     */
    public function __construct($Datum)
    {
      $this->Datum = $Datum;
    }

}