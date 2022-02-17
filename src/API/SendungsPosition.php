<?php

namespace GoExpress\API;

class SendungsPosition
{
  /**
   * @var integer $AnzahlPackstuecke
   * @access public
   */
  public $AnzahlPackstuecke = null;

  /**
   * @var float $Gewicht
   * @access public
   */
  public $Gewicht = null;

  /**
   * @var string $Inhalt
   * @access public
   */
  public $Inhalt = null;

  /**
   * @param integer $AnzahlPackstuecke
   * @param float $Gewicht
   * @param string $Inhalt
   * @access public
   */
  public function __construct($AnzahlPackstuecke, $Gewicht, $Inhalt)
  {
    $this->AnzahlPackstuecke = $AnzahlPackstuecke;
    $this->Gewicht = $Gewicht;
    $this->Inhalt = $Inhalt;
  }
}
