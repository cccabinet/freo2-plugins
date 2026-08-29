<?php

import('plugins/order/app/services/order_record.php');

// フォワードを確認
if (forward() === null) {
    error('不正なアクセスです。');
}

// 投稿データを確認
if (empty($_SESSION['post'])) {
    // リダイレクト
    redirect('/admin/order_form');
}

// トランザクションを開始
db_transaction();

if (empty($_SESSION['post']['order_record']['id'])) {
    // 注文記録を登録
    $resource = service_order_record_insert([
        'values' => [
            'provide'       => $_SESSION['post']['order_record']['provide'],
            'payment_id'    => $_SESSION['post']['order_record']['payment_id'],
            'payment_fee'   => $_SESSION['post']['order_record']['payment_fee'],
            'delivery_id'   => $_SESSION['post']['order_record']['delivery_id'],
            'delivery_cost' => $_SESSION['post']['order_record']['delivery_cost'],
            'discount'      => $_SESSION['post']['order_record']['discount'],
            'shipping_date' => $_SESSION['post']['order_record']['shipping_date'],
            'status'        => $_SESSION['post']['order_record']['status'],
            'user_id'       => null,
            'email'         => $_SESSION['post']['order_record']['email'],
            'name_01'       => $_SESSION['post']['order_record']['name_01'],
            'name_02'       => $_SESSION['post']['order_record']['name_02'],
            'kana_01'       => $_SESSION['post']['order_record']['kana_01'],
            'kana_02'       => $_SESSION['post']['order_record']['kana_02'],
            'zipcode'       => $_SESSION['post']['order_record']['zipcode'],
            'prefecture'    => $_SESSION['post']['order_record']['prefecture'],
            'address_01'    => $_SESSION['post']['order_record']['address_01'],
            'address_02'    => $_SESSION['post']['order_record']['address_02'],
            'telephone'     => $_SESSION['post']['order_record']['telephone'],
            'message'       => $_SESSION['post']['order_record']['message'],
            'memo'          => $_SESSION['post']['order_record']['memo'],
        ],
    ], [
        'items' => $_SESSION['post']['order_record_items'] ?? [],
    ]);
    if (!$resource) {
        error('データを登録できません。');
    }
} else {
    // 注文記録を編集
    $resource = service_order_record_update([
        'set'   => [
            'provide'       => $_SESSION['post']['order_record']['provide'],
            'payment_id'    => $_SESSION['post']['order_record']['payment_id'],
            'payment_fee'   => $_SESSION['post']['order_record']['payment_fee'],
            'delivery_id'   => $_SESSION['post']['order_record']['delivery_id'],
            'delivery_cost' => $_SESSION['post']['order_record']['delivery_cost'],
            'discount'      => $_SESSION['post']['order_record']['discount'],
            'shipping_date' => $_SESSION['post']['order_record']['shipping_date'],
            'status'        => $_SESSION['post']['order_record']['status'],
            'email'         => $_SESSION['post']['order_record']['email'],
            'name_01'       => $_SESSION['post']['order_record']['name_01'],
            'name_02'       => $_SESSION['post']['order_record']['name_02'],
            'kana_01'       => $_SESSION['post']['order_record']['kana_01'],
            'kana_02'       => $_SESSION['post']['order_record']['kana_02'],
            'zipcode'       => $_SESSION['post']['order_record']['zipcode'],
            'prefecture'    => $_SESSION['post']['order_record']['prefecture'],
            'address_01'    => $_SESSION['post']['order_record']['address_01'],
            'address_02'    => $_SESSION['post']['order_record']['address_02'],
            'telephone'     => $_SESSION['post']['order_record']['telephone'],
            'message'       => $_SESSION['post']['order_record']['message'],
            'memo'          => $_SESSION['post']['order_record']['memo'],
        ],
        'where' => [
            'id = :id',
            [
                'id' => $_SESSION['post']['order_record']['id'],
            ],
        ],
    ], [
        'id'     => intval($_SESSION['post']['order_record']['id']),
        'update' => $_SESSION['update']['order_record'],
        // 'items' は指定しない（注文明細はこの画面では変更しない）
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
redirect('/admin/order?ok=post');
