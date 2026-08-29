<?php

// ワンタイムトークン
if (!token('check')) {
    error('不正な操作が検出されました。送信内容を確認して再度実行してください。');
}

// アクセス元
if (empty($_SERVER['HTTP_REFERER']) || !preg_match('/^' . preg_quote($GLOBALS['config']['http_url'], '/') . '/', $_SERVER['HTTP_REFERER'])) {
    error('不正なアクセスです。');
}

// 入力内容を確認
if (empty($_POST['provide']) || !in_array($_POST['provide'], ['delivery', 'download'], true)) {
    error('不正なアクセスです。');
}
if (empty($_POST['order_spec_id'])) {
    error('規格が指定されていません。');
}

// カートのセッションを確認
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [
        'delivery' => [],
        'download' => [],
    ];
}

// カートから削除
foreach ($_SESSION['cart'][$_POST['provide']] as $key => $value) {
    if ($value['order_spec_id'] == $_POST['order_spec_id']) {
        unset($_SESSION['cart'][$_POST['provide']][$key]);

        break;
    }
}

// リダイレクト
redirect('/cart/?ok=remove');
