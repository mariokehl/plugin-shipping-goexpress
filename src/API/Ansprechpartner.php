<?php

namespace GoExpress\API;

class Ansprechpartner
{
    /**
     * @var Telefon $Telefon
     * @access public
     */
    public $Telefon = null;

    /**
     * @param Telefon $Telefon
     * @access public
     */
    public function __construct($Telefon)
    {
        $this->Telefon = $Telefon;
    }
}
