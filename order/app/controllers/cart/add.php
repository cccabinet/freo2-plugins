<?php

// ワンタイムトークン
if (!token('check')) {
    error('不正な操作が検出されました。送信内容を確認して再度実行してください。');
}

// アクセス元
if (empty($_SERVER['HTTP_REFERER']) || !preg_match('/^' . preg_quote($GLOBALS['config']['http_url'], '/') . '/', $_SERVER['HTTP_REFERER'])) {
    error('不正なアクセスです。');
}

// 規格を確認
if (empty($_POST['order_spec_id'])) {
    error('規格が指定されていません。');
}
$order_specs = model('select_order_specs', [
    'select' => 'provide',
    'where'  => [
        'id = :id AND enabled = 1',
        [
            'id' => $_POST['order_spec_id'],
        ],
    ],
]);
if (empty($order_specs)) {
    error('規格が見つかりません。');
}
$provide = $order_specs[0]['provide'];

// 対面販売は管理画面からのみ受注登録できる
if ($provide === 'direct') {
    error('この商品はカートに追加できません。');
}

// カートのセッションを確認
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [
        'delivery' => [],
        'download' => [],
    ];
}

// カートに追加
foreach ($_SESSION['cart'][$provide] as $key => $value) {
    if ($value['order_spec_id'] == $_POST['order_spec_id']) {
        $_SESSION['cart'][$provide][$key]['quantity'] += $_POST['quantity'];
        redirect('/cart/?ok=add');
    }
}
$_SESSION['cart'][$provide][] = [
    'order_spec_id' => $_POST['order_spec_id'],
    'quantity'      => $_POST['quantity'],
];

// リダイレクト
redirect('/cart/?ok=add');
