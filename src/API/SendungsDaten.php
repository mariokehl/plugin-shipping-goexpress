<?php

namespace GoExpress\API;

class SendungsDaten {

    /**
     * @var integer $Versender
     * @access public
     */
    public $Versender = null;

    /**
     * @var string $Status
     * @access public
     */
    public $Status = null;

    /**
     * @var string $Kundenreferenz
     * @access public
     */
    public $Kundenreferenz = null;    

    /**
     * @var Abholadresse $Abholadresse
     * @access public
     */
    public $Abholadresse = null;

    /**
     * @var Empfaenger $Empfaenger
     * @access public
     */
    public $Empfaenger = null;

    /**
     * @var string $Service
     * @access public
     */
    public $Service = null;

    /**
     * @var Abholdatum $Abholdatum
     * @access public
     */
    public $Abholdatum = null;

    /**
     * @var string $unfrei
     * @access public
     */
    public $unfrei = null;

    /**
     * @var string $Selbstanlieferung
     * @access public
     */
    public $Selbstanlieferung = null;

    /**
     * @var string $Selbstabholung
     * @access public
     */
    public $Selbstabholung = null;

    /**
     * @var SendungsPosition $SendungsPosition
     * @access public
     */
    public $SendungsPosition = null;

    /**
     * @param Empfaenger $Empfaenger
     * @param Abholadresse $Abholadresse
     * @param Abholdatum $Abholdatum
     * @param SendungsPosition $SendungsPosition
     * @param string $Kundenreferenz
     * @access public
     */
    public function __construct($Empfaenger, $Abholadresse, $Abholdatum, $SendungsPosition, $Kundenreferenz)
    {
        $this->Versender = 405644;
        $this->Status = 1; // 1 = neu, 3 = freigegeben, 20 = Storno
        $this->Kundenreferenz = $Kundenreferenz;
        $this->Abholadresse = $Abholadresse;
        $this->Empfaenger = $Empfaenger;
        $this->Service = 0; // 0 = Overnight, 1 = Overnight Letter, 2 = International, 3 = International Letter, 4 = Overnight Basis
        $this->Abholdatum = $Abholdatum;
        $this->unfrei = 0; // 0 = Frei, 1 = unfrei
        $this->Selbstanlieferung = 0; // 0 = Abholung, 1 = Selbstanlieferung
        $this->Selbstabholung = 0; // 0 = Zustellung, 1 = Selbstabholung
        $this->SendungsPosition = $SendungsPosition;
    }

}