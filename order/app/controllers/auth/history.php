<?php

// ページを取得
if (isset($_GET['page'])) {
    $_GET['page'] = intval($_GET['page']);
} else {
    $_GET['page'] = 1;
}

// 注文記録を取得
$_view['order_records'] = model('select_order_records', [
    'where' => [
        'user_id = :user_id',
        [
            'user_id' => $_SESSION['auth']['user']['id'],
        ],
    ],
    'order_by' => 'id DESC',
    'limit'    => [
        ':offset, :limit',
        [
            'offset' => $GLOBALS['plugin']['order']['setting']['number_limit_history'] * ($_GET['page'] - 1),
            'limit'  => $GLOBALS['plugin']['order']['setting']['number_limit_history'],
        ],
    ],
]);

// 総数を取得
$order_record_count = model('select_order_records', [
    'select' => 'COUNT(*) AS count',
    'where'  => [
        'user_id = :user_id',
        [
            'user_id' => $_SESSION['auth']['user']['id'],
        ],
    ],
]);
$_view['history_count'] = $order_record_count[0]['count'];
$_view['history_page']  = ceil($order_record_count[0]['count'] / $GLOBALS['plugin']['order']['setting']['number_limit_history']);
$_view['history_width'] = $GLOBALS['plugin']['order']['setting']['number_width_history'];

$_view['order_payment_sets']       = [];
$_view['order_delivery_sets']      = [];
$_view['order_record_item_totals'] = [];

if (!empty($_view['order_records'])) {
    // 支払方法を取得
    $order_payments = model('select_order_payments', [
        'where' => 'id IN(' . implode(',', array_map('db_escape', array_unique(array_column($_view['order_records'], 'payment_id')))) . ')',
    ]);
    foreach ($order_payments as $order_payment) {
        $_view['order_payment_sets'][$order_payment['id']] = $order_payment;
    }

    // 配送方法を取得
    $order_deliveries = model('select_order_deliveries', [
        'where' => 'id IN(' . implode(',', array_map('db_escape', array_unique(array_column($_view['order_records'], 'delivery_id')))) . ')',
    ]);
    foreach ($order_deliveries as $order_delivery) {
        $_view['order_delivery_sets'][$order_delivery['id']] = $order_delivery;
    }

    // 注文明細の合計金額を取得
    $order_record_items = model('select_order_record_items', [
        'where' => 'record_id IN(' . implode(',', array_map('db_escape', array_column($_view['order_records'], 'id'))) . ')',
    ]);
    foreach ($order_record_items as $order_record_item) {
        if (!isset($_view['order_record_item_totals'][$order_record_item['record_id']])) {
            $_view['order_record_item_totals'][$order_record_item['record_id']] = 0;
        }
        $_view['order_record_item_totals'][$order_record_item['record_id']] += $order_record_item['selling_price'] * $order_record_item['quantity'];
    }
}

// タイトル
$_view['title'] = '注文履歴';
