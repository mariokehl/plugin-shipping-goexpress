<?php

namespace GoExpress\API;

class Position {

    /**
     * @var integer $PositionsNr
     * @access public
     */
    public $PositionsNr = null;

    /**
     * @var integer $AnzahlPackstuecke
     * @access public
     */
    public $AnzahlPackstuecke = null;

    /**
     * @var Barcodes[] $Barcodes
     * @access public
     */
    public $Barcodes = null;

    /**
     * @param integer $PositionsNr
     * @param integer $AnzahlPackstuecke
     * @param Barcodes[] $Barcodes
     * @access public
     */
    public function __construct($PositionsNr, $AnzahlPackstuecke, $Barcodes)
    {
        $this->PositionsNr = $PositionsNr;
        $this->AnzahlPackstuecke = $AnzahlPackstuecke;
        $this->Barcodes = $Barcodes;
    }

}