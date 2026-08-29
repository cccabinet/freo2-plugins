<?php

// 規格を取得
$_view['order_specs'] = model('select_order_specs', [
    'where' => [
        'entry_id = :entry_id',
        [
            'entry_id' => $_GET['entry_id'],
        ],
    ],
    'order_by' => 'sort, id',
]);

// 在庫数が0の規格があるか確認（有効な規格のみが対象）
$_view['order_spec_out_of_stocks'] = [];
$enabled_spec_ids = array_column(array_filter($_view['order_specs'], function ($order_spec) {
    return $order_spec['enabled'] == 1;
}), 'id');
if (!empty($enabled_spec_ids)) {
    $order_products = model('select_order_products', [
        'where' => 'spec_id IN(' . implode(',', array_map('db_escape', $enabled_spec_ids)) . ')',
    ], [
        'associate' => true,
    ]);
    foreach ($order_products as $order_product) {
        if ($order_product['order_stock_quantity'] !== null && $order_product['order_stock_quantity'] <= 0) {
            $_view['order_spec_out_of_stocks'][$order_product['spec_id']] = true;
        }
    }
}

// タイトル
$_view['title'] = '規格管理';
