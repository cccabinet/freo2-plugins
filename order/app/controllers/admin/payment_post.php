<?php

import('plugins/order/app/services/order_payment.php');

// フォワードを確認
if (forward() === null) {
    error('不正なアクセスです。');
}

// 投稿データを確認
if (empty($_SESSION['post'])) {
    // リダイレクト
    redirect('/admin/payment_form');
}

// トランザクションを開始
db_transaction();

if (empty($_SESSION['post']['order_payment']['id'])) {
    // 支払方法を登録
    $resource = service_order_payment_insert([
        'values' => [
            'enabled' => $_SESSION['post']['order_payment']['enabled'],
            'name'    => $_SESSION['post']['order_payment']['name'],
            'text'    => $_SESSION['post']['order_payment']['text'],
            'fee'     => $_SESSION['post']['order_payment']['fee'],
            'memo'    => $_SESSION['post']['order_payment']['memo'],
        ],
    ]);
    if (!$resource) {
        error('データを登録できません。');
    }
} else {
    // 支払方法を編集
    $resource = service_order_payment_update([
        'set'   => [
            'enabled' => $_SESSION['post']['order_payment']['enabled'],
            'name'    => $_SESSION['post']['order_payment']['name'],
            'text'    => $_SESSION['post']['order_payment']['text'],
            'fee'     => $_SESSION['post']['order_payment']['fee'],
            'memo'    => $_SESSION['post']['order_payment']['memo'],
        ],
        'where' => [
            'id = :id',
            [
                'id' => $_SESSION['post']['order_payment']['id'],
            ],
        ],
    ], [
        'id'     => intval($_SESSION['post']['order_payment']['id']),
        'update' => $_SESSION['update']['order_payment'],
    ]);
    if (!$resource) {
        error('データを編集できません。');
    }
}

// トランザクションを終了
db_commit();

// 投稿セッションを初期化
unset($_SESSION['post']);
unset($_SESSION['update']);

// リダイレクト
redirect('/admin/payment?ok=post');
