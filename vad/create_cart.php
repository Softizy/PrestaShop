<?php
require(dirname(__FILE__).'/../config/config.inc.php');
require_once('./inc/PSWebServiceLibrary.php');

$webService = PrestaShopWebservice::getWebservice();

$xml = $webService->get(array('url' => PS_SHOP_PATH . '/api/customers/?schema=synopsis'));

$customer = array();

$id_customer = Tools::getValue('id_customer');
$id_address = Tools::getValue('id_address');

$customer = $webService->get(array('url' => PS_SHOP_PATH . '/api/customers/'.$id_customer));
$address = $webService->get(array('url' => PS_SHOP_PATH . '/api/addresses/'.$id_address));

$id['country'] = '165'; // france

if (property_exists($customer, 'customer') &&
    property_exists($address, 'address')) {
    $id['customer'] = $customer->customer['id'];
    $id['address'] = $address->address['id'];
} else {
    echo "You need to specify id_customer & id_address";
    exit;
}

$id['lang'] = Tools::getValue('id_lang', 1);
$id['currency'] = Tools::getValue('id_currency', 1);
$id['carrier'] = Tools::getValue('id_carrier', 3);

// CREATE Cart
$xml = $webService->get(array('url' => PS_SHOP_PATH . '/api/carts?schema=blank'));

$xml->cart->id_customer = $id_customer;
$xml->cart->id_address_delivery = $id_address;
$xml->cart->id_address_invoice = $id_address;
$xml->cart->id_currency = $id['currency'];
$xml->cart->id_lang = $id['lang'];
$xml->cart->id_carrier = $id['carrier'];

$opt = array('resource' => 'carts');
$opt['postXml'] = $xml->asXML();

$xml = $webService->add($opt);

// ID of created cart
$id['cart'] = $xml->cart->id;

$output = json_encode($xml);
echo $output;
exit;
