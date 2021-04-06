<?php

namespace GoExpress\API;

class Abholadresse {

    /**
     * @var string $Firmenname1
     * @access public
     */
    public $Firmenname1 = null;

    /**
     * @var string $Strasse1
     * @access public
     */
    public $Strasse1 = null;

    /**
     * @var string $Hausnummer
     * @access public
     */
    public $Hausnummer = null;

    /**
     * @var string $Land
     * @access public
     */
    public $Land = null;

    /**
     * @var string $Postleitzahl
     * @access public
     */
    public $Postleitzahl = null;

    /**
     * @var string $Stadt
     * @access public
     */
    public $Stadt = null;

    /**
     * @param string $Firmenname1
     * @param string $Strasse1
     * @param string $Hausnummer
     * @param string $Land
     * @param string $Postleitzahl
     * @param string $Stadt
     * @access public
     */
    public function __construct($Firmenname1, $Strasse1, $Hausnummer, $Land, $Postleitzahl, $Stadt)
    {
        $this->Firmenname1    = $Firmenname1;
        $this->Strasse1       = $Strasse1;
        $this->Hausnummer     = $Hausnummer;
        $this->Land           = $Land;
        $this->Postleitzahl   = $Postleitzahl;
        $this->Stadt          = $Stadt;
    }

}