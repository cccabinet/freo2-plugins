<?php

import('plugins/order/app/services/order_stock.php');

// フォワードを確認
if (forward() === null) {
    error('不正なアクセスです。');
}

// 投稿データを確認
if (empty($_SESSION['post'])) {
    // リダイレクト
    redirect('/admin/stock_form');
}

// トランザクションを開始
db_transaction();

if (empty($_SESSION['post']['order_stock']['id'])) {
    // ルールを登録
    $resource = service_order_stock_insert([
        'values' => [
            'code'       => $_SESSION['post']['order_stock']['code'],
            'name'       => $_SESSION['post']['order_stock']['name'],
            'text'       => $_SESSION['post']['order_stock']['text'],
            'kind'       => $_SESSION['post']['order_stock']['kind'],
            'download'   => $_SESSION['post']['order_stock']['download'],
            'quantity'   => $_SESSION['post']['order_stock']['quantity'],
            'cost_price' => $_SESSION['post']['order_stock']['cost_price'],
            'memo'       => $_SESSION['post']['order_stock']['memo'],
        ],
    ]);
    if (!$resource) {
        error('データを登録できません。');
    }
} else {
    // ルールを編集
    $resource = service_order_stock_update([
        'set'   => [
            'code'       => $_SESSION['post']['order_stock']['code'],
            'name'       => $_SESSION['post']['order_stock']['name'],
            'text'       => $_SESSION['post']['order_stock']['text'],
            'kind'       => $_SESSION['post']['order_stock']['kind'],
            'download'   => $_SESSION['post']['order_stock']['download'],
            'quantity'   => $_SESSION['post']['order_stock']['quantity'],
            'cost_price' => $_SESSION['post']['order_stock']['cost_price'],
            'memo'       => $_SESSION['post']['order_stock']['memo'],
        ],
        'where' => [
            'id = :id',
            [
                'id' => $_SESSION['post']['order_stock']['id'],
            ],
        ],
    ], [
        'id'     => intval($_SESSION['post']['order_stock']['id']),
        'update' => $_SESSION['update']['order_stock'],
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
redirect('/admin/stock?ok=post');
