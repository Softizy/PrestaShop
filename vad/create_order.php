<?php
require(dirname(__FILE__).'/../config/config.inc.php');
require_once('./inc/PSWebServiceLibrary.php');

$webService = PrestaShopWebservice::getWebservice();

$xml = $webService->get(array('url' => PS_SHOP_PATH . '/api/customers/?schema=synopsis'));

if (!empty(Tools::getValue('id_cart'))) {
    $id_cart = Tools::getValue('id_cart');
    // get Cart
    $cart = $webService->get(array('url' => PS_SHOP_PATH . '/api/carts/'.$id_cart))->cart;
} else {
    echo "You need to specify an id_cart";
    exit;
}

$xml = $webService->get(array('url' => PS_SHOP_PATH . '/api/orders?schema=blank'));

$xml->order->id_customer = $cart->id_customer;
$xml->order->id_address_delivery = $cart->id_address_delivery;
$xml->order->id_address_invoice = $cart->id_address_invoice;
$xml->order->id_cart = $id_cart;
$xml->order->id_currency = $cart->id_currency;
$xml->order->id_lang = $cart->id_lang;
$xml->order->id_carrier = $cart->id_lang;
$xml->order->current_state = 3;
$xml->order->valid = 0;
$xml->order->payment = 'Cash on delivery';
$xml->order->module = Tools::getValue('payment_module');
$xml->order->conversion_rate = '1';

$total_price = 0;
unset($xml->order->associations->order_rows->order_row);
foreach($cart->associations->cart_rows->cart_row as $key => $cart_row) {
    $xml->order->associations->order_rows->addChild('order_row');
    $new_order_row = $xml->order->associations->order_rows->order_row[$xml->order->associations->order_rows->order_row->count()-1];
    $new_order_row->product_id = $cart_row->id_product;
    if (isset($cart_row->id_product_attribute)) {
        $new_order_row->product_attribute_id = $cart_row->id_product_attribute;
    }
    $new_order_row->product_quantity = $cart_row->quantity;

    $price = Product::getPriceStatic((int)$new_order_row->product_id,
        $usetax = true,
        $id_product_attribute = null,
        $decimals = 6,
        $divisor = null,
        $only_reduc = false,
        $usereduc = true,
        $quantity = 1,
        $force_associated_tax = false,
        $id_customer = null,
        $id_cart = $id_cart
    );
    $name = Product::getProductName($new_order_row->product_id);
    $total_price += $price * $new_order_row->product_quantity;
}

$xml->order->total_paid = $total_price;
$xml->order->total_paid_tax_incl = $total_price;
$xml->order->total_paid_tax_excl = $total_price;
$xml->order->total_paid_real = '0';
$xml->order->total_products = $total_price;
$xml->order->total_products_wt = $total_price;

$opt = array('resource' => 'orders');
$opt['postXml'] = $xml->asXML();

$xml_order = $webService->add($opt);

$id['order'] = $xml->order->id;
$id['secure_key'] = $xml->order->secure_key;

$xml = $webService->get(array('url' => PS_SHOP_PATH . '/api/order_histories?schema=blank'));

$xml->order_history->id_order = $id['order'];
$xml->order_history->id_order_state = '3';
$opt = array('resource' => 'order_histories');
$opt['postXml'] = $xml->asXML();
$xml = $webService->add($opt);

$output = json_encode($xml_order);
echo $output;
