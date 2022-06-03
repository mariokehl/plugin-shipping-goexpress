<?php

namespace GoExpress\API;

class Empfaenger
{
    /**
     * @var string $Firmenname1
     * @access public
     */
    public $Firmenname1 = null;

    /**
     * @var string $Firmenname2
     * @access public
     */
    public $Firmenname2 = null;

    /**
     * @var string $Abteilung
     * @access public
     */
    public $Abteilung = null;

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
     * @var string $Email
     * @access public
     */
    public $Email = null;

    /**
     * @var Ansprechpartner $Ansprechpartner
     * @access public
     */
    public $Ansprechpartner = null;

    /**
     * @param string $Firmenname1
     * @param string $Strasse1
     * @param string $Hausnummer
     * @param string $Land
     * @param string $Postleitzahl
     * @param string $Stadt
     * @param string $Email
     * @access public
     */
    public function __construct($Firmenname1, $Strasse1, $Hausnummer, $Land, $Postleitzahl, $Stadt, $Email, $Firmenname2 = '')
    {
        $this->Firmenname1 = substr($Firmenname1, 0, 60);
        $this->Firmenname2 = substr($Firmenname2, 0, 60);
        $this->Strasse1 = substr($Strasse1, 0, 35);
        $this->Hausnummer = substr($Hausnummer, 0, 10);
        $this->Land = substr($Land, 0, 13);
        $this->Postleitzahl = substr($Postleitzahl, 0, 9);
        $this->Stadt = substr($Stadt, 0, 30);
        $this->Email = substr($Email, 0, 100);
    }

    /**
     * Set the value of Ansprechpartner
     * 
     * @param string $Abteilung
     * @return self
     */
    public function setAnsprechpartner($Ansprechpartner): self
    {
        $this->Ansprechpartner = $Ansprechpartner;

        return $this;
    }

    /**
     * Set the optional value of Abteilung
     *
     * @param string $Abteilung
     * @return self
     */
    public function setAbteilung($Abteilung): self
    {
        $this->Abteilung = substr($Abteilung, 0, 40);

        return $this;
    }
}
