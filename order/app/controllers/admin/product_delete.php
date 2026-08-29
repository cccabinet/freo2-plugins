<?php

import('plugins/order/app/services/order_product.php');

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

    // 製品を削除
    $resource = service_order_product_delete([
        'where' => [
            'order_products.id = :id',
            [
                'id' => $_POST['id'],
            ],
        ],
    ]);
    if (!$resource) {
        error('データを削除できません。');
    }

    // トランザクションを終了
    db_commit();

    // リダイレクト
    redirect('/admin/product?entry_id=' . $_POST['entry_id'] . '&spec_id=' . $_POST['spec_id'] . '&ok=delete');
} else {
    // リダイレクト
    redirect('/admin/product?entry_id=' . $_POST['entry_id'] . '&spec_id=' . $_POST['spec_id'] . '&warning=delete');
}
