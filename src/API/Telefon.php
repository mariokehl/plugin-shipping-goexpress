<?php

namespace GoExpress\API;

class Telefon
{
    const DEFAULT_LAENDERPREFIX = '0049';

    const LAENDERPREFIX_MAPPING = [
        'DE' => '0049',
        'AT' => '0043'
    ];

    /**
     * @var string $LaenderPrefix
     * @access public
     */
    public $LaenderPrefix = null;

    /**
     * @var string $Ortsvorwahl
     * @access public
     */
    public $Ortsvorwahl = null;

    /**
     * @var string $Telefonnummer
     * @access public
     */
    public $Telefonnummer = null;

    /**
     * @param string $Telefon
     * @param string $Land
     * @return void
     */
    public function parseString($Telefon, $Land = 'DE')
    {
        // Remove whitespaces
        $Telefon = str_replace(' ', '', $Telefon);

        // Remove delimiters (-/)
        $Telefon = str_replace(['-', '/'], ['', ''], $Telefon);

        // LaenderPrefix
        if (substr($Telefon, 0, 1) === '+' || substr($Telefon, 0, 2) === '00') {
            $Telefon = str_replace('+', '00', $Telefon);
            $this->LaenderPrefix = substr($Telefon, 0, 4);
            $Telefon = str_replace($this->LaenderPrefix, '', $Telefon);
        } else {
            if (substr($Telefon, 0, 1) === '0') {
                $Telefon = str_replace('0', '', $Telefon);
            }
            if (array_key_exists($Land, self::LAENDERPREFIX_MAPPING)) {
                $this->LaenderPrefix = self::LAENDERPREFIX_MAPPING[$Land];
            } else {
                $this->LaenderPrefix = self::DEFAULT_LAENDERPREFIX;
            }
        }

        // Ortsvorwahl
        if ($Ortsvorwahl = substr($Telefon, 0, 3)) {
            $this->Ortsvorwahl = $Ortsvorwahl;
        } else {
            $this->Ortsvorwahl = str_pad($Ortsvorwahl, 7, '9', STR_PAD_LEFT);
        }

        // Telefonnummer
        if ($Telefonnummer = substr($Telefon, 3)) {
            $this->Telefonnummer = substr($Telefonnummer, 0, 10);
        } else {
            $this->Telefonnummer = str_pad($Telefonnummer, 10, '9', STR_PAD_LEFT);
        }
    }
}