<?php

import('plugins/order/app/services/order_address.php');

// フォワードを確認
if (forward() === null) {
    error('不正なアクセスです。');
}

// 投稿データを確認
if (empty($_SESSION['post'])) {
    // リダイレクト
    redirect('/auth/address_form');
}

// トランザクションを開始
db_transaction();

if (empty($_SESSION['post']['order_address']['id'])) {
    // 住所を登録
    $resource = service_order_address_insert([
        'values' => [
            'user_id'    => $_SESSION['auth']['user']['id'],
            'name_01'    => $_SESSION['post']['order_address']['name_01'],
            'name_02'    => $_SESSION['post']['order_address']['name_02'],
            'kana_01'    => $_SESSION['post']['order_address']['kana_01'],
            'kana_02'    => $_SESSION['post']['order_address']['kana_02'],
            'zipcode'    => $_SESSION['post']['order_address']['zipcode'],
            'prefecture' => $_SESSION['post']['order_address']['prefecture'],
            'address_01' => $_SESSION['post']['order_address']['address_01'],
            'address_02' => $_SESSION['post']['order_address']['address_02'],
            'telephone'  => $_SESSION['post']['order_address']['telephone'],
        ],
    ]);
    if (!$resource) {
        error('データを登録できません。');
    }
} else {
    // 住所を編集
    $resource = service_order_address_update([
        'set'   => [
            'name_01'    => $_SESSION['post']['order_address']['name_01'],
            'name_02'    => $_SESSION['post']['order_address']['name_02'],
            'kana_01'    => $_SESSION['post']['order_address']['kana_01'],
            'kana_02'    => $_SESSION['post']['order_address']['kana_02'],
            'zipcode'    => $_SESSION['post']['order_address']['zipcode'],
            'prefecture' => $_SESSION['post']['order_address']['prefecture'],
            'address_01' => $_SESSION['post']['order_address']['address_01'],
            'address_02' => $_SESSION['post']['order_address']['address_02'],
            'telephone'  => $_SESSION['post']['order_address']['telephone'],
        ],
        'where' => [
            'id = :id AND user_id = :user_id',
            [
                'id'      => $_SESSION['post']['order_address']['id'],
                'user_id' => $_SESSION['auth']['user']['id'],
            ],
        ],
    ], [
        'id'     => intval($_SESSION['post']['order_address']['id']),
        'update' => $_SESSION['update']['order_address'],
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
redirect('/auth/address?ok=post');
