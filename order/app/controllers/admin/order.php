<?php

// ページを取得
if (isset($_GET['page'])) {
    $_GET['page'] = intval($_GET['page']);
} else {
    $_GET['page'] = 1;
}

// 注文記録を取得
$_view['order_records'] = model('select_order_records', [
    'order_by' => 'id DESC',
    'limit'    => [
        ':offset, :limit',
        [
            'offset' => $GLOBALS['plugin']['order']['setting']['number_limit_admin_order'] * ($_GET['page'] - 1),
            'limit'  => $GLOBALS['plugin']['order']['setting']['number_limit_admin_order'],
        ],
    ],
]);

// 総数を取得
$order_record_count = model('select_order_records', [
    'select' => 'COUNT(*) AS count',
]);
$_view['order_record_count'] = $order_record_count[0]['count'];
$_view['order_record_page']  = ceil($order_record_count[0]['count'] / $GLOBALS['plugin']['order']['setting']['number_limit_admin_order']);

$_view['order_payment_sets']       = [];
$_view['order_record_item_totals'] = [];
$_view['user_sets']                = [];

if (!empty($_view['order_records'])) {
    // 支払方法を取得
    $order_payments = model('select_order_payments', [
        'where' => 'id IN(' . implode(',', array_map('db_escape', array_unique(array_column($_view['order_records'], 'payment_id')))) . ')',
    ]);
    foreach ($order_payments as $order_payment) {
        $_view['order_payment_sets'][$order_payment['id']] = $order_payment;
    }

    // ユーザーを取得
    $user_ids = array_filter(array_unique(array_column($_view['order_records'], 'user_id')));
    if (!empty($user_ids)) {
        $users = model('select_users', [
            'where' => 'id IN(' . implode(',', array_map('db_escape', $user_ids)) . ')',
        ]);
        foreach ($users as $user) {
            $_view['user_sets'][$user['id']] = $user;
        }
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
$_view['title'] = '注文管理';
