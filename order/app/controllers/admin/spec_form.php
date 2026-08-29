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
        'order_spec' => model('normalize_order_specs', [
            'id'            => isset($_POST['id'])            ? $_POST['id']            : '',
            'entry_id'      => isset($_POST['entry_id'])      ? $_POST['entry_id']      : '',
            'code'          => isset($_POST['code'])          ? $_POST['code']          : '',
            'enabled'       => isset($_POST['enabled'])       ? $_POST['enabled']       : '',
            'name'          => isset($_POST['name'])          ? $_POST['name']          : '',
            'provide'       => isset($_POST['provide'])       ? $_POST['provide']       : '',
            'selling_price' => isset($_POST['selling_price']) ? $_POST['selling_price'] : '',
            'regular_price' => isset($_POST['regular_price']) ? $_POST['regular_price'] : '',
            'shipping_cost' => isset($_POST['shipping_cost']) ? $_POST['shipping_cost'] : '',
            'delivery_days' => isset($_POST['delivery_days']) ? $_POST['delivery_days'] : '',
            'sales_limit'   => isset($_POST['sales_limit'])   ? $_POST['sales_limit']   : '',
            'memo'          => isset($_POST['memo'])          ? $_POST['memo']          : '',
        ]),
    ];

    // 入力データを検証＆登録
    $warnings = model('validate_order_specs', $post['order_spec']);
    if (isset($_POST['_type']) && $_POST['_type'] === 'json') {
        if (empty($warnings)) {
            ok();
        } else {
            warning($warnings);
        }
    } else {
        if (empty($warnings)) {
            $_SESSION['post']['order_spec'] = $post['order_spec'];

            // フォワード
            forward('plugins/order/app/controllers/admin/spec_post.php');
        } else {
            $_view['order_spec'] = $post['order_spec'];

            $_view['warnings'] = $warnings;
        }
    }
} else {
    // 初期データを取得
    if (empty($_GET['id'])) {
        $_view['order_spec'] = model('default_order_specs');
    } else {
        $order_specs = model('select_order_specs', [
            'where' => [
                'id = :id',
                [
                    'id' => $_GET['id'],
                ],
            ],
        ]);
        if (empty($order_specs)) {
            warning('編集データが見つかりません。');
        } else {
            $_view['order_spec'] = $order_specs[0];
        }
    }

    // 投稿セッションを初期化
    unset($_SESSION['post']);

    // 編集開始日時を記録
    if (!empty($_GET['id'])) {
        $_SESSION['update']['order_spec'] = localdate('Y-m-d H:i:s');
    }
}

// タイトル
if (empty($_GET['id'])) {
    $_view['title'] = '規格登録';
} else {
    $_view['title'] = '規格編集';
}
