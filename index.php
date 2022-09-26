<?php
require __DIR__ . '/vendor/autoload.php';

use Automattic\WooCommerce\Client;

$woocommerce = new Client(
    '#URL',
    'ck_',
    'cs_',
    [
        'wp_api' => true,
        'version' => 'wc/v3',
        'query_string_auth' => true,
    ]
);

$url = sprintf('https://sheets.googleapis.com/v4/spreadsheets/URL_GOOGLE_SHEETS');
$json = json_decode(file_get_contents($url));
$values = $json->values;
$keys = $values[0];
unset($values[0]);

$registros = array_map(function ($values) use ($keys) {
    return array_combine($keys, $values);
}, array_values($values));

$param_sku ='';
foreach ($registros as $item){
    $param_sku .= $item['SKU'] . ',';
}
$products = $woocommerce->get('products',array('per_page' => 100,'page' =>1, 'sku' => $param_sku));

$item_data = [];
foreach ($products as $product) {
    $sku = $product->sku;
    $search_item = array_filter($registros, function ($item) use ($sku) {
        return $item['SKU'] == $sku;
    });
    $search_item = reset($search_item);
    $item_data[] = [
        'product_id'        => $product->parent_id,
        'id'                => $product->id,
        'regular_price'     => $search_item['Precio'],
        'stock_quantity'    => $search_item['Cantidad'],
        'description'       => $search_item['Descripcion'],
        'sale_price'        => $search_item['Descuentos']
    ];
}
$result = [];

foreach ($item_data as $data) {
    $product_id = $data['product_id'];
    $id         = $data['id'];
    unset($data['product_id'], $data['id']);
    $result[] = $woocommerce->post("products/{$product_id}/variations/{$id}", $data);
}
$today = date("F j, Y, g:i a");

if (!$result) {
    echo ("Error to update the products  </br>");
} else {   
          print("</br> First List - The products have been update " . $today . " </br>");
header("Location: #URL");
}
