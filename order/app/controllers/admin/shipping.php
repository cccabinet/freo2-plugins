<?php

import('plugins/order/app/services/order_shipping.php');

// 対象を確認
if (empty($_GET['record_id'])) {
    error('不正なアクセスです。');
}

// 注文記録を取得
$order_records = model('select_order_records', [
    'where' => [
        'id = :id',
        [
            'id' => $_GET['record_id'],
        ],
    ],
]);
if (empty($order_records)) {
    warning('対象の注文が見つかりません。');
} else {
    $_view['order_record'] = $order_records[0];
}

// 注文明細を取得
$_view['order_record_items'] = model('select_order_record_items', [
    'where' => [
        'record_id = :record_id',
        [
            'record_id' => $_GET['record_id'],
        ],
    ],
    'order_by' => 'id',
]);

// 規格を取得
$_view['order_spec_sets'] = [];
if (!empty($_view['order_record_items'])) {
    $order_specs = model('select_order_specs', [
        'where' => 'order_specs.id IN(' . implode(',', array_map('db_escape', array_column($_view['order_record_items'], 'spec_id'))) . ')',
    ], [
        'associate' => true,
    ]);
    foreach ($order_specs as $order_spec) {
        $_view['order_spec_sets'][$order_spec['id']] = $order_spec;
    }
}

// 発送済み数を取得
$_view['shipped_quantities'] = service_order_shipping_shipped_quantities($_GET['record_id'], array_column($_view['order_record_items'], 'id'));

// 発送記録を取得
$_view['order_shippings'] = model('select_order_shippings', [
    'where' => [
        'record_id = :record_id',
        [
            'record_id' => $_GET['record_id'],
        ],
    ],
    'order_by' => 'id',
]);

// 配送方法を取得
$_view['order_delivery_sets'] = [];
if (!empty($_view['order_shippings'])) {
    $order_deliveries = model('select_order_deliveries', [
        'where' => 'id IN(' . implode(',', array_map('db_escape', array_unique(array_column($_view['order_shippings'], 'delivery_id')))) . ')',
    ]);
    foreach ($order_deliveries as $order_delivery) {
        $_view['order_delivery_sets'][$order_delivery['id']] = $order_delivery;
    }
}

// 実際にかかった送料の合計を集計
$_view['shipping_delivery_cost_total'] = 0;
foreach ($_view['order_shippings'] as $order_shipping) {
    $_view['shipping_delivery_cost_total'] += $order_shipping['delivery_cost'] ?? 0;
}

// タイトル
$_view['title'] = '発送管理';
