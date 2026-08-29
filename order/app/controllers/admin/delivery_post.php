<?php

import('plugins/order/app/services/order_delivery.php');

// フォワードを確認
if (forward() === null) {
    error('不正なアクセスです。');
}

// 投稿データを確認
if (empty($_SESSION['post'])) {
    // リダイレクト
    redirect('/admin/delivery_form');
}

// トランザクションを開始
db_transaction();

if (empty($_SESSION['post']['order_delivery']['id'])) {
    // 配送方法を登録
    $resource = service_order_delivery_insert([
        'values' => [
            'enabled'    => $_SESSION['post']['order_delivery']['enabled'],
            'name'       => $_SESSION['post']['order_delivery']['name'],
            'text'       => $_SESSION['post']['order_delivery']['text'],
            'cost'       => $_SESSION['post']['order_delivery']['cost'],
            'surcharge'  => $_SESSION['post']['order_delivery']['surcharge'],
            'calculate'  => $_SESSION['post']['order_delivery']['calculate'],
            'threshold'  => $_SESSION['post']['order_delivery']['threshold'],
            'discounted' => $_SESSION['post']['order_delivery']['discounted'],
            'memo'       => $_SESSION['post']['order_delivery']['memo'],
        ],
    ]);
    if (!$resource) {
        error('データを登録できません。');
    }
} else {
    // 配送方法を編集
    $resource = service_order_delivery_update([
        'set'   => [
            'enabled'    => $_SESSION['post']['order_delivery']['enabled'],
            'name'       => $_SESSION['post']['order_delivery']['name'],
            'text'       => $_SESSION['post']['order_delivery']['text'],
            'cost'       => $_SESSION['post']['order_delivery']['cost'],
            'surcharge'  => $_SESSION['post']['order_delivery']['surcharge'],
            'calculate'  => $_SESSION['post']['order_delivery']['calculate'],
            'threshold'  => $_SESSION['post']['order_delivery']['threshold'],
            'discounted' => $_SESSION['post']['order_delivery']['discounted'],
            'memo'       => $_SESSION['post']['order_delivery']['memo'],
        ],
        'where' => [
            'id = :id',
            [
                'id' => $_SESSION['post']['order_delivery']['id'],
            ],
        ],
    ], [
        'id'     => intval($_SESSION['post']['order_delivery']['id']),
        'update' => $_SESSION['update']['order_delivery'],
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
redirect('/admin/delivery?ok=post');
