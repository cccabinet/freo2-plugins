<?php

// エントリーを取得
$_view['entries'] = model('select_entries', [
    'where'    => 'types.code = ' . db_escape('catalog'),
    'order_by' => 'entries.sort DESC, entries.code, entries.id',
], [
    'associate' => true,
]);

// カテゴリーを取得
$_view['categories'] = model('select_categories', [
    'where'    => 'types.code = ' .  db_escape('catalog'),
    'order_by' => 'categories.sort, categories.id',
], [
    'associate' => true,
]);

// 在庫数が0の規格を持つ商品があるか確認（公開中かつ有効な規格のみが対象）
$_view['entry_out_of_stocks'] = [];
$public_entry_ids = array_column(array_filter($_view['entries'], function ($entry) {
    return $entry['public'] !== 'none';
}), 'id');
if (!empty($public_entry_ids)) {
    $order_specs = model('select_order_specs', [
        'where' => 'entry_id IN(' . implode(',', array_map('db_escape', $public_entry_ids)) . ') AND enabled = 1',
    ]);
    if (!empty($order_specs)) {
        $order_spec_entry_sets = [];
        foreach ($order_specs as $order_spec) {
            $order_spec_entry_sets[$order_spec['id']] = $order_spec['entry_id'];
        }

        $order_products = model('select_order_products', [
            'where' => 'spec_id IN(' . implode(',', array_map('db_escape', array_column($order_specs, 'id'))) . ')',
        ], [
            'associate' => true,
        ]);
        foreach ($order_products as $order_product) {
            if ($order_product['order_stock_quantity'] !== null && $order_product['order_stock_quantity'] <= 0) {
                $_view['entry_out_of_stocks'][$order_spec_entry_sets[$order_product['spec_id']]] = true;
            }
        }
    }
}

// タイトル
$_view['title'] = '商品管理';
