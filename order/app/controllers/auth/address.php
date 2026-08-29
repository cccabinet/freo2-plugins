<?php

// 住所を取得
$_view['order_addresses'] = model('select_order_addresses', [
    'where' => [
        'user_id = :user_id',
        [
            'user_id' => $_SESSION['auth']['user']['id'],
        ],
    ],
    'order_by' => 'id',
]);

// タイトル
$_view['title'] = '住所管理';
