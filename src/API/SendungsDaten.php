<?php

namespace GoExpress\API;

class SendungsDaten
{
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
     * @var string $Zustellhinweise
     * @access public
     */
    public $Zustellhinweise = null;

    /**
     * @var SendungsPosition $SendungsPosition
     * @access public
     */
    public $SendungsPosition = null;

    /**
     * @param integer $Versender
     * @param Empfaenger $Empfaenger
     * @param Abholadresse $Abholadresse
     * @param Abholdatum $Abholdatum
     * @param SendungsPosition $SendungsPosition
     * @param string $Kundenreferenz
     * @param string $Zustellhinweise
     * @access public
     */
    public function __construct(
        $Versender,
        $Empfaenger,
        $Abholadresse,
        $Abholdatum,
        $SendungsPosition,
        $Kundenreferenz,
        $Zustellhinweise = ''
    ) {
        $this->Versender = $Versender;
        $this->Status = Status::freigegeben;
        $this->Kundenreferenz = substr($Kundenreferenz, 0, 40);
        $this->Abholadresse = $Abholadresse;
        $this->Empfaenger = $Empfaenger;
        if (trim($Empfaenger->Land) === 'DE') {
            $this->Service = Service::Overnight;
        } else {
            $this->Service = Service::International;
        }
        $this->Abholdatum = $Abholdatum;
        $this->unfrei = KzUnfrei::frei;
        $this->Selbstanlieferung = KzSelbstanlieferung::Abholung;
        $this->Selbstabholung = KzSelbstabholung::Zustellung;
        $this->Zustellhinweise = substr($Zustellhinweise, 0, 128);
        $this->SendungsPosition = $SendungsPosition;
    }

    /**
     * Set the value of Versender
     */
    public function setVersender($Versender): self
    {
        $this->Versender = $Versender;

        return $this;
    }
}
