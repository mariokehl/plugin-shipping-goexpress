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
     * @var string $Benutzername
     * @access public
     */
    public $Benutzername = null;

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
        $this->Versender = Versender::_default;
        $this->Status = Status::neu;
        $this->Kundenreferenz = $Kundenreferenz;
        $this->Abholadresse = $Abholadresse;
        $this->Empfaenger = $Empfaenger;
        $this->Service = Service::neu;
        $this->Abholdatum = $Abholdatum;
        $this->unfrei = KzUnfrei::frei;
        $this->Selbstanlieferung = KzSelbstanlieferung::Abholung;
        $this->Selbstabholung = KzSelbstabholung::Zustellung;
        $this->SendungsPosition = $SendungsPosition;
    }

}