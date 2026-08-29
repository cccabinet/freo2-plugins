<?php

// 投稿データを確認
if (empty($_SESSION['item']) || empty($_SESSION['post']['order_record'])) {
    // リダイレクト
    redirect('/');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ワンタイムトークン
    if (!token('check')) {
        error('不正なアクセスです。');
    }

    // フォワード
    forward('plugins/order/app/controllers/order/post.php');
} else {
    // 入力データを復元
    $_view['order_record'] = $_SESSION['post']['order_record'];
}

// 支払方法を取得
$_view['order_payment'] = [];
if (!empty($_view['order_record']['payment_id'])) {
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
}

// 配送方法を取得
$_view['order_delivery'] = [];
if (!empty($_view['order_record']['delivery_id'])) {
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
}

if (!empty($_SESSION['item'])) {
    // 規格を取得
    $order_specs = model('select_order_specs', [
        'where' => 'id IN(' . implode(',', array_map('db_escape', array_column($_SESSION['item'], 'order_spec_id'))) . ') AND enabled = 1',
    ]);
    $_view['order_spec_sets'] = [];
    foreach ($order_specs as $order_spec) {
        $_view['order_spec_sets'][$order_spec['id']] = $order_spec;
    }

    // エントリーを取得
    $entries = service_entry_select_published('catalog', [
        'where' => 'entries.id IN(' . implode(',', array_map('db_escape', array_column($_view['order_spec_sets'], 'entry_id'))) . ')',
    ]);
    $_view['entry_sets'] = [];
    foreach ($entries as $entry) {
        $_view['entry_sets'][$entry['id']] = $entry;
    }
}

// タイトル
$_view['title'] = $GLOBALS['plugin']['order']['setting']['heading_order'];
