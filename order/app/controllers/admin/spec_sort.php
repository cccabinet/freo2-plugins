<?php

import('plugins/order/app/services/order_spec.php');

// ワンタイムトークン
if (!token('check')) {
    error('不正な操作が検出されました。送信内容を確認して再度実行してください。');
}

// アクセス元
if (empty($_SERVER['HTTP_REFERER']) || !preg_match('/^' . preg_quote($GLOBALS['config']['http_url'], '/') . '/', $_SERVER['HTTP_REFERER'])) {
    error('不正なアクセスです。');
}

// トランザクションを開始
db_transaction();

// 並び順を更新
service_order_spec_sort($_POST['sort']);

// トランザクションを終了
db_commit();

if (isset($_POST['_type']) && $_POST['_type'] == 'json') {
    ok();

    exit;
} else {
    // リダイレクト
    redirect('/admin/spec?entry_id=' . $_POST['entry_id'] . '&ok=sort');
}
