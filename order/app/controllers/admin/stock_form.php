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
        'order_stock' => model('normalize_order_stocks', [
            'id'         => isset($_POST['id'])         ? $_POST['id']         : '',
            'code'       => isset($_POST['code'])       ? $_POST['code']       : '',
            'name'       => isset($_POST['name'])       ? $_POST['name']       : '',
            'text'       => isset($_POST['text'])       ? $_POST['text']       : '',
            'kind'       => isset($_POST['kind'])       ? $_POST['kind']       : '',
            'download'   => isset($_POST['download'])   ? $_POST['download']   : '',
            'quantity'   => isset($_POST['quantity'])   ? $_POST['quantity']   : '',
            'cost_price' => isset($_POST['cost_price']) ? $_POST['cost_price'] : '',
            'memo'       => isset($_POST['memo'])       ? $_POST['memo']       : '',
        ]),
    ];

    // 入力データを検証＆登録
    $warnings = model('validate_order_stocks', $post['order_stock']);
    if (isset($_POST['_type']) && $_POST['_type'] === 'json') {
        if (empty($warnings)) {
            ok();
        } else {
            warning($warnings);
        }
    } else {
        if (empty($warnings)) {
            $_SESSION['post']['order_stock'] = $post['order_stock'];

            // フォワード
            forward('plugins/order/app/controllers/admin/stock_post.php');
        } else {
            $_view['order_stock'] = $post['order_stock'];

            $_view['warnings'] = $warnings;
        }
    }
} else {
    // 初期データを取得
    if (empty($_GET['id'])) {
        $_view['order_stock'] = model('default_order_stocks');
    } else {
        $order_stocks = model('select_order_stocks', [
            'where' => [
                'id = :id',
                [
                    'id' => $_GET['id'],
                ],
            ],
        ]);
        if (empty($order_stocks)) {
            warning('編集データが見つかりません。');
        } else {
            $_view['order_stock'] = $order_stocks[0];
        }
    }

    // 投稿セッションを初期化
    unset($_SESSION['post']);

    // 編集開始日時を記録
    if (!empty($_GET['id'])) {
        $_SESSION['update']['order_stock'] = localdate('Y-m-d H:i:s');
    }
}

// タイトル
if (empty($_GET['id'])) {
    $_view['title'] = '在庫登録';
} else {
    $_view['title'] = '在庫編集';
}
