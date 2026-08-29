<?php

// 注文記録を取得
$order_records = model('select_order_records', [
    'where' => [
        'id = :id AND user_id = :user_id',
        [
            'id'      => $_GET['id'],
            'user_id' => $_SESSION['auth']['user']['id'],
        ],
    ],
]);
if (empty($order_records)) {
    warning('表示データが見つかりません。');
} else {
    $_view['order_record'] = $order_records[0];
}

if (!empty($_view['order_record'])) {
    // 支払方法を取得
    $_view['order_payment'] = [];
    $order_payments = model('select_order_payments', [
        'where' => [
            'id = :id',
            [
                'id' => $_view['order_record']['payment_id'],
            ],
        ],
    ]);
    if (!empty($order_payments)) {
        $_view['order_payment'] = $order_payments[0];
    }

    // 配送方法を取得
    $_view['order_delivery'] = [];
    $order_deliveries = model('select_order_deliveries', [
        'where' => [
            'id = :id',
            [
                'id' => $_view['order_record']['delivery_id'],
            ],
        ],
    ]);
    if (!empty($order_deliveries)) {
        $_view['order_delivery'] = $order_deliveries[0];
    }

    // 注文明細を取得
    $_view['order_record_items'] = model('select_order_record_items', [
        'where' => [
            'record_id = :record_id',
            [
                'record_id' => $_view['order_record']['id'],
            ],
        ],
        'order_by' => 'id',
    ]);
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
}

// タイトル
$_view['title'] = '注文詳細';
