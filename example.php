<?php

require 'vendor/autoload.php';

use Onetoweb\Parcelpro\Client;

$userId = '';
$apiKey = 'apiKey';

$client = new Client($userId, $apiKey);

// validate apikey
$result = $client->validateApikey();

// create account
// https://login.parcelpro.nl/api/docs/#operation--account-aanmaken
$account = $client->createAccount([
    'Email' => 'info@example.com',
    'Naam' => 'Naam',
    'Contactpersoon' => "Contactpersoon",
    'Telefoonnummer' => 'Telefoonnummer',
    'Straat' => 'Straat',
    'Nummer' => '1A',
    'Postcode' => '1111AA',
    'Plaats' => 'Plaats',
    'Land' => 'NL',
]);

// check if account exists
$result = $client->accountExists($account['Email']);

// get shimpment types
$shipmentTypes = $client->getShipmentTypes();
$shipmentType = $shipmentTypes[0];

// get pickup points
// https://login.parcelpro.nl/api/docs/#operation--uitreiklocaties
$pickupPoints = $client->getPickupPoints([
    'Postcode' => 'Postcode',
    'Nummer' => 'Nummer',
    'Straat' => 'Straat',
]);

// create shipment
// https://login.parcelpro.nl/api/docs/#operation--nieuwe-zending
$shipment = $client->createShipment([
    'Carrier' => $shipmentType['CarrierNaam'],
    'Type' => $shipmentType['Type'],
    'Naam' => 'Naam',
    'Straat' => 'Straat',
    'Nummer' => '1A',
    'Postcode' => '1111AA',
    'Plaats' => 'Plaats',
    'Land' => 'NL',
    'Email' => 'info@example.com',
    'AantalPakketten' => 1,
    'Gewicht' => 3.14,
    'Opmerking' => 'test shipment',
]);

// get shipment label url
$labelUrl = $client->getLabelUrl($shipment['Id']);

// get shipment label content
$labelContents = $client->getLabelContents($shipment['Id']);

// save shipment label
$filename = '/path/to/file.pdf';
$client->saveLabel($shipment['Id'], $filename);

// get shipments
// https://login.parcelpro.nl/api/docs/#operation--zendingen
$shipments = $client->getShipments([
    'ZendingId' => $shipment['Id']
]);

// print label
// https://login.parcelpro.nl/api/docs/#operation--label-afdrukken
$client->printLabel($shipment['Id']);

// create trigger
// https://login.parcelpro.nl/api/docs/#operation--status-terugmelding
$trigger = $client->createTrigger('http://www.example.com/trigger.php', 'afgedrukt', 'ZendingId=?id&Status=Shipped&Referentie=?referentie');

