<?php

namespace GoExpress\API;

class Sendung {

    /**
     * @var integer $SendungsnummerAX4
     * @access public
     */
    public $SendungsnummerAX4 = null;

    /**
     * @var integer $Frachtbriefnummer
     * @access public
     */
    public $Frachtbriefnummer = null;

    /**
     * @var string $Abholdatum
     * @access public
     */
    public $Abholdatum = null;

    /**
     * @var string $Zustelldatum
     * @access public
     */
    public $Zustelldatum = null;

    /**
     * @var Position[] $Position
     * @access public
     */
    public $Position = null;

    /**
     * @param integer $SendungsnummerAX4
     * @param integer $Frachtbriefnummer
     * @param string $Abholdatum
     * @param string $Zustelldatum
     * @param Position[] $Position
     * @access public
     */
    public function __construct($SendungsnummerAX4, $Frachtbriefnummer, $Abholdatum, $Zustelldatum, $Position)
    {
        $this->SendungsnummerAX4 = $SendungsnummerAX4;
        $this->Frachtbriefnummer = $Frachtbriefnummer;
        $this->Abholdatum = $Abholdatum;
        $this->Zustelldatum = $Zustelldatum;
        $this->Position = $Position;
    }

}