<?php
require(dirname(__FILE__).'/../config/config.inc.php');
require_once('./inc/PSWebServiceLibrary.php');

$webService = PrestaShopWebservice::getWebservice();

$xml = $webService->get(array('url' => PS_SHOP_PATH . '/api/customers/?schema=synopsis'));

$customer = array();

$customer['email'] = Tools::getValue('c_email');

$customerExists = $webService->get(array('url' => PS_SHOP_PATH . '/api/customers/?filter[email]='.$customer['email'].'&limit=1'));

$id['country'] = '165'; // france

if (property_exists($customerExists, 'customers') &&
    property_exists($customerExists->customers, 'customer')) {
    $id['customer'] = $customerExists->customers->customer['id'];

    $addressExists = $webService->get(array('url' => PS_SHOP_PATH . '/api/addresses/?filter[id_customer]='.$id['customer'].'&limit=1'));
    if (property_exists($addressExists, 'addresses') &&
        property_exists($addressExists->addresses, 'address')) {
        $id['address'] = $addressExists->addresses->address['id'];
    }

} else {
    $customer['firstname'] = Tools::getValue('fname');
    $customer['lastname'] = Tools::getValue('lname');
    $customer['address1'] = Tools::getValue('c_address');
    $customer['city'] = Tools::getValue('c_city');
    $customer['postcode'] = Tools::getValue('c_postcode');
    $customer['phone'] = Tools::getValue('c_phone');

    $xml->customer->firstname = $customer['firstname'];
    $xml->customer->lastname = $customer['lastname'];
    $xml->customer->email = $customer['email'];
    $xml->customer->newsletter = '1';
    $xml->customer->optin = '1';
    $xml->customer->active = '1';

    $opt = array('resource' => 'customers');
    $opt['postXml'] = $xml->asXML();
    $xml = $webService->add($opt);

    // ID of created customer

    $id['customer'] = $xml->customer->id;

    // CREATE Address


    $xml = $webService->get(array('url' => PS_SHOP_PATH . '/api/addresses?schema=synopsis'));
    $xml->address->id_customer = $id['customer'];
    $xml->address->firstname = $customer['firstname'];
    $xml->address->lastname = $customer['lastname'];
    $xml->address->address1 = $customer['address1'];
    $xml->address->city = $customer['city'];
    $xml->address->postcode = $customer['postcode'];
    $xml->address->phone_mobile = $customer['phone'];
    $xml->address->id_country = $id['country'];
    $xml->address->alias = '-';

    $opt = array('resource' => 'addresses');
    $opt['postXml'] = $xml->asXML();
    $xml = $webService->add($opt);

    // ID of created address

    $id['address'] = $xml->address->id;
}

$output = json_encode($id);
echo $output;
