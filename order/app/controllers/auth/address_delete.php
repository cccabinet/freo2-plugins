<?php

import('plugins/order/app/services/order_address.php');

// ワンタイムトークン
if (!token('check')) {
    error('不正な操作が検出されました。送信内容を確認して再度実行してください。');
}

// アクセス元
if (empty($_SERVER['HTTP_REFERER']) || !preg_match('/^' . preg_quote($GLOBALS['config']['http_url'], '/') . '/', $_SERVER['HTTP_REFERER'])) {
    error('不正なアクセスです。');
}

if (!empty($_POST['id'])) {
    // トランザクションを開始
    db_transaction();

    // 住所を削除
    $resource = service_order_address_delete([
        'where' => [
            'id = :id AND user_id = :user_id',
            [
                'id'      => $_POST['id'],
                'user_id' => $_SESSION['auth']['user']['id'],
            ],
        ],
    ]);
    if (!$resource) {
        error('データを削除できません。');
    }

    // トランザクションを終了
    db_commit();

    // リダイレクト
    redirect('/auth/address?ok=delete');
} else {
    // リダイレクト
    redirect('/auth/address?warning=delete');
}
