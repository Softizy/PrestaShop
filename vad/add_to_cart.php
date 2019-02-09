<?php
require(dirname(__FILE__).'/../config/config.inc.php');
require_once('./inc/PSWebServiceLibrary.php');

$webService = PrestaShopWebservice::getWebservice();

$xml = $webService->get(array('url' => PS_SHOP_PATH . '/api/customers/?schema=synopsis'));

$customer = array();

$id_customer = Tools::getValue('id_customer');
$id_address = Tools::getValue('id_address');


if (!empty(Tools::getValue('id_cart'))) {
    $id_cart = Tools::getValue('id_cart');
    // get Cart
    $xml = $webService->get(array('url' => PS_SHOP_PATH . '/api/carts/'.$id_cart));
} else {
    echo "You need to specify an id_cart";
    exit;
}

$id['country'] = '165'; // france
$id['lang'] = Tools::getValue('id_lang', 1);
$id['currency'] = Tools::getValue('id_currency', 1);
$id['carrier'] = Tools::getValue('id_carrier', 3);


$product['quantity'] = Tools::getValue('qty');
$product['id'] = Tools::getValue('id_product');

$update = false;
foreach($xml->cart->associations->cart_rows->cart_row as $key => $cart_row) {
    if ($cart_row->id_product == $product['id']) {
        $update = true;
        $cart_row->quantity += $product['quantity'];
        if ($cart_row->quantity <= 0) {
            $cart_row->quantity = 0;
        } else {
            $cart_row->id_address_delivery = $xml->cart->id_address_delivery;
            if (!empty(Tools::getValue('id_product_attribute'))) {
                $cart_row->id_product_attribute = Tools::getValue('id_product_attribute');
            }
        }
    }
}

if ($update == false) {
    $xml->cart->associations->cart_rows->addChild('cart_row');
    $new_cart_row = $xml->cart->associations->cart_rows->cart_row[$xml->cart->associations->cart_rows->cart_row->count()-1];
    $new_cart_row->id_product = $product['id'];
    $new_cart_row->quantity = $product['quantity'];
    $new_cart_row->id_address_delivery = $xml->cart->id_address_delivery;
    if (!empty(Tools::getValue('id_product_attribute'))) {
        $new_cart_row->id_product_attribute = Tools::getValue('id_product_attribute');
    }
}

$opt = array('resource' => 'carts');
$opt['id'] = $xml->id;
$opt['putXml'] = $xml->asXML();

$xml = $webService->edit($opt);

$output = json_encode($xml);
echo $output;
exit;
