<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ワンタイムトークン
    if (!token('check')) {
        error('不正な操作が検出されました。送信内容を確認して再度実行してください。');
    }

    // アクセス元
    if (empty($_SERVER['HTTP_REFERER']) || !preg_match('/^' . preg_quote($GLOBALS['config']['http_url'], '/') . '/', $_SERVER['HTTP_REFERER'])) {
        error('不正なアクセスです。');
    }

    // 入力データを整理
    $post = [
        'order_product' => model('normalize_order_products', [
            'id'       => isset($_POST['id'])       ? $_POST['id']       : '',
            'spec_id'  => isset($_POST['spec_id'])  ? $_POST['spec_id']  : '',
            'stock_id' => isset($_POST['stock_id']) ? $_POST['stock_id'] : '',
            'quantity' => isset($_POST['quantity']) ? $_POST['quantity'] : '',
            'memo'     => isset($_POST['memo'])     ? $_POST['memo']     : '',
        ]),
    ];

    // 入力データを検証＆登録
    $warnings = model('validate_order_products', $post['order_product']);
    if (isset($_POST['_type']) && $_POST['_type'] === 'json') {
        if (empty($warnings)) {
            ok();
        } else {
            warning($warnings);
        }
    } else {
        if (empty($warnings)) {
            $_SESSION['post']['order_product'] = $post['order_product'];
            $_SESSION['post']['order_product']['entry_id'] = $_POST['entry_id'];

            // フォワード
            forward('plugins/order/app/controllers/admin/product_post.php');
        } else {
            $_view['order_product'] = $post['order_product'];

            $_view['warnings'] = $warnings;
        }
    }
} else {
    // 初期データを取得
    if (empty($_GET['id'])) {
        $_view['order_product'] = model('default_order_products');
    } else {
        $order_products = model('select_order_products', [
            'where' => [
                'id = :id',
                [
                    'id' => $_GET['id'],
                ],
            ],
        ]);
        if (empty($order_products)) {
            warning('編集データが見つかりません。');
        } else {
            $_view['order_product'] = $order_products[0];
        }
    }

    // 投稿セッションを初期化
    unset($_SESSION['post']);

    // 編集開始日時を記録
    if (!empty($_GET['id'])) {
        $_SESSION['update']['order_product'] = localdate('Y-m-d H:i:s');
    }
}

// 規格の提供方法を取得
$order_specs = model('select_order_specs', [
    'where' => [
        'id = :id',
        [
            'id' => $_GET['spec_id'],
        ],
    ],
]);
if ($order_specs[0]['provide'] === 'download') {
    $kind = 'digital';
} else {
    $kind = 'analog';
}

// 在庫を取得
$_view['stocks'] = model('select_order_stocks', [
    'where' => [
        'kind = :kind',
        [
            'kind' => $kind,
        ],
    ],
    'order_by' => 'code, id',
]);

// タイトル
if (empty($_GET['id'])) {
    $_view['title'] = '製品登録';
} else {
    $_view['title'] = '製品編集';
}
