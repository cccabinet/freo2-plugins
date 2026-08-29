<?php

// 製品を取得
$_view['order_products'] = model('select_order_products', [
    'where' => [
        'order_products.spec_id = :spec_id',
        [
            'spec_id' => $_GET['spec_id'],
        ],
    ],
    'order_by' => 'order_products.sort, order_products.id',
], [
    'associate' => true,
]);

// 在庫数が0の製品があるか確認
$_view['order_product_out_of_stocks'] = [];
foreach ($_view['order_products'] as $order_product) {
    if ($order_product['order_stock_quantity'] !== null && $order_product['order_stock_quantity'] <= 0) {
        $_view['order_product_out_of_stocks'][$order_product['id']] = true;
    }
}

// タイトル
$_view['title'] = '製品管理';
