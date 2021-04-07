<?php

include_once('API/SendungsDaten.php');
include_once('API/Abholadresse.php');
include_once('API/Abholdatum.php');
include_once('API/Empfaenger.php');
include_once('API/SendungsPosition.php');

include_once('API/GOWebService.php');

$receiverAddress = new \GoExpress\API\Empfaenger(
    'Bureau Mario Kehl',
    'Lange Str.',
    '79',
    'DE',
    '34131',
    'Kassel',
    'bureau@mariokehl.com'
);

$senderAddress = new \GoExpress\API\Abholadresse(
    'Fleischerei Schäfer OHG',
    'Hersfelder Str.',
    '20',
    'DE',
    '36272',
    'Niederaula'
);

$pickupDate = new \GoExpress\API\Abholdatum(
    date('d.m.Y')
);

$parcelData = new \GoExpress\API\SendungsPosition(
    1,
    5.0,
    'Lebensmittel'
);

$shipment = new \GoExpress\API\SendungsDaten(
    $receiverAddress,
    $senderAddress,
    $pickupDate,
    $parcelData,
    ''
);

$webservice = new \GoExpress\API\GOWebService();

try {
    $response = $webservice->GOWebService_SendungsErstellung($shipment);
    echo "ERGEBIS:\n";
    var_dump($response);
} catch (\SoapFault $soapFault) {
    echo $soapFault;
    var_dump($webservice->__getLastRequest());
    var_dump($webservice->__getLastRequestHeaders());
    var_dump($webservice->__getLastResponse());
    var_dump($webservice->__getLastResponseHeaders());
}
