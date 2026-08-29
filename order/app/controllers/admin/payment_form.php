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
        'order_payment' => model('normalize_order_payments', [
            'id'      => isset($_POST['id'])      ? $_POST['id']      : '',
            'enabled' => isset($_POST['enabled']) ? $_POST['enabled'] : '',
            'name'    => isset($_POST['name'])    ? $_POST['name']    : '',
            'text'    => isset($_POST['text'])    ? $_POST['text']    : '',
            'fee'     => isset($_POST['fee'])     ? $_POST['fee']     : '',
            'memo'    => isset($_POST['memo'])    ? $_POST['memo']    : '',
        ]),
    ];

    // 入力データを検証＆登録
    $warnings = model('validate_order_payments', $post['order_payment']);
    if (isset($_POST['_type']) && $_POST['_type'] === 'json') {
        if (empty($warnings)) {
            ok();
        } else {
            warning($warnings);
        }
    } else {
        if (empty($warnings)) {
            $_SESSION['post']['order_payment'] = $post['order_payment'];

            // フォワード
            forward('plugins/order/app/controllers/admin/payment_post.php');
        } else {
            $_view['order_payment'] = $post['order_payment'];

            $_view['warnings'] = $warnings;
        }
    }
} else {
    // 初期データを取得
    if (empty($_GET['id'])) {
        $_view['order_payment'] = model('default_order_payments');
    } else {
        $order_payments = model('select_order_payments', [
            'where' => [
                'id = :id',
                [
                    'id' => $_GET['id'],
                ],
            ],
        ]);
        if (empty($order_payments)) {
            warning('編集データが見つかりません。');
        } else {
            $_view['order_payment'] = $order_payments[0];
        }
    }

    // 投稿セッションを初期化
    unset($_SESSION['post']);

    // 編集開始日時を記録
    if (!empty($_GET['id'])) {
        $_SESSION['update']['order_payment'] = localdate('Y-m-d H:i:s');
    }
}

// タイトル
if (empty($_GET['id'])) {
    $_view['title'] = '支払方法登録';
} else {
    $_view['title'] = '支払方法編集';
}
