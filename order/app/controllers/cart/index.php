<?php

// カートのセッションを確認
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [
        'delivery' => [],
        'download' => [],
    ];
}

// 規格を取得
$order_spec_sets = [];
if (!empty($_SESSION['cart']['delivery'])) {
    $order_specs = model('select_order_specs', [
        'where' => 'id IN(' . implode(',', array_map('db_escape', array_column($_SESSION['cart']['delivery'], 'order_spec_id'))) . ') AND enabled = 1',
    ]);
    foreach ($order_specs as $order_spec) {
        $order_spec_sets[$order_spec['id']] = $order_spec;
    }
}
if (!empty($_SESSION['cart']['download'])) {
    $order_specs = model('select_order_specs', [
        'where' => 'id IN(' . implode(',', array_map('db_escape', array_column($_SESSION['cart']['download'], 'order_spec_id'))) . ') AND enabled = 1',
    ]);
    foreach ($order_specs as $order_spec) {
        $order_spec_sets[$order_spec['id']] = $order_spec;
    }
}
$_view['order_spec_sets'] = $order_spec_sets;

if (!empty($order_spec_sets)) {
    // エントリーを取得
    $entries = service_entry_select_published('catalog', [
        'where' => 'entries.id IN(' . implode(',', array_map('db_escape', array_column($order_spec_sets, 'entry_id'))) . ')',
    ]);
    $_view['entry_sets'] = [];
    foreach ($entries as $entry) {
        $_view['entry_sets'][$entry['id']] = $entry;
    }
}

// タイトル
$_view['title'] = $GLOBALS['plugin']['order']['setting']['heading_cart'];
