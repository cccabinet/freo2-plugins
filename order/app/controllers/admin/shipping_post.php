<?php

import('plugins/order/app/services/order_shipping.php');

// フォワードを確認
if (forward() === null) {
    error('不正なアクセスです。');
}

// 投稿データを確認
if (empty($_SESSION['post'])) {
    // リダイレクト
    redirect('/admin/shipping_form');
}

// トランザクションを開始
db_transaction();

if (empty($_SESSION['post']['order_shipping']['id'])) {
    // 発送記録を登録
    $resource = service_order_shipping_insert([
        'values' => [
            'record_id'     => $_SESSION['post']['order_shipping']['record_id'],
            'delivery_id'   => $_SESSION['post']['order_shipping']['delivery_id'],
            'delivery_cost' => $_SESSION['post']['order_shipping']['delivery_cost'],
            'shipping_date' => $_SESSION['post']['order_shipping']['shipping_date'],
            'status'        => $_SESSION['post']['order_shipping']['status'],
            'email'         => $_SESSION['post']['order_shipping']['email'],
            'name_01'       => $_SESSION['post']['order_shipping']['name_01'],
            'name_02'       => $_SESSION['post']['order_shipping']['name_02'],
            'kana_01'       => $_SESSION['post']['order_shipping']['kana_01'],
            'kana_02'       => $_SESSION['post']['order_shipping']['kana_02'],
            'zipcode'       => $_SESSION['post']['order_shipping']['zipcode'],
            'prefecture'    => $_SESSION['post']['order_shipping']['prefecture'],
            'address_01'    => $_SESSION['post']['order_shipping']['address_01'],
            'address_02'    => $_SESSION['post']['order_shipping']['address_02'],
            'telephone'     => $_SESSION['post']['order_shipping']['telephone'],
            'memo'          => $_SESSION['post']['order_shipping']['memo'],
        ],
    ], [
        'items' => $_SESSION['post']['order_shipping_items'] ?? [],
    ]);
    if (!$resource) {
        error('データを登録できません。');
    }
} else {
    // 発送記録を編集
    $resource = service_order_shipping_update([
        'set'   => [
            'delivery_id'   => $_SESSION['post']['order_shipping']['delivery_id'],
            'delivery_cost' => $_SESSION['post']['order_shipping']['delivery_cost'],
            'shipping_date' => $_SESSION['post']['order_shipping']['shipping_date'],
            'status'        => $_SESSION['post']['order_shipping']['status'],
            'email'         => $_SESSION['post']['order_shipping']['email'],
            'name_01'       => $_SESSION['post']['order_shipping']['name_01'],
            'name_02'       => $_SESSION['post']['order_shipping']['name_02'],
            'kana_01'       => $_SESSION['post']['order_shipping']['kana_01'],
            'kana_02'       => $_SESSION['post']['order_shipping']['kana_02'],
            'zipcode'       => $_SESSION['post']['order_shipping']['zipcode'],
            'prefecture'    => $_SESSION['post']['order_shipping']['prefecture'],
            'address_01'    => $_SESSION['post']['order_shipping']['address_01'],
            'address_02'    => $_SESSION['post']['order_shipping']['address_02'],
            'telephone'     => $_SESSION['post']['order_shipping']['telephone'],
            'memo'          => $_SESSION['post']['order_shipping']['memo'],
        ],
        'where' => [
            'id = :id',
            [
                'id' => $_SESSION['post']['order_shipping']['id'],
            ],
        ],
    ], [
        'id'        => intval($_SESSION['post']['order_shipping']['id']),
        'update'    => $_SESSION['update']['order_shipping'],
        'record_id' => $_SESSION['post']['order_shipping']['record_id'],
        'items'     => $_SESSION['post']['order_shipping_items'] ?? [],
    ]);
    if (!$resource) {
        error('データを編集できません。');
    }
}

// トランザクションを終了
db_commit();

// 注文IDを取得
$record_id = $_SESSION['post']['order_shipping']['record_id'];

// 投稿セッションを初期化
unset($_SESSION['post']);
unset($_SESSION['update']);

// リダイレクト
redirect('/admin/shipping?record_id=' . $record_id . '&ok=post');
