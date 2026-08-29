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
        'order_address' => model('normalize_order_addresses', [
            'id'         => isset($_POST['id'])         ? $_POST['id']         : '',
            'name_01'    => isset($_POST['name_01'])    ? $_POST['name_01']    : '',
            'name_02'    => isset($_POST['name_02'])    ? $_POST['name_02']    : '',
            'kana_01'    => isset($_POST['kana_01'])    ? $_POST['kana_01']    : '',
            'kana_02'    => isset($_POST['kana_02'])    ? $_POST['kana_02']    : '',
            'zipcode'    => isset($_POST['zipcode'])    ? $_POST['zipcode']    : '',
            'prefecture' => isset($_POST['prefecture']) ? $_POST['prefecture'] : '',
            'address_01' => isset($_POST['address_01']) ? $_POST['address_01'] : '',
            'address_02' => isset($_POST['address_02']) ? $_POST['address_02'] : '',
            'telephone'  => isset($_POST['telephone'])  ? $_POST['telephone']  : '',
        ]),
    ];

    // 入力データを検証＆登録
    $warnings = model('validate_order_addresses', $post['order_address']);
    if (isset($_POST['_type']) && $_POST['_type'] === 'json') {
        if (empty($warnings)) {
            ok();
        } else {
            warning($warnings);
        }
    } else {
        if (empty($warnings)) {
            $_SESSION['post']['order_address'] = $post['order_address'];

            // フォワード
            forward('plugins/order/app/controllers/auth/address_post.php');
        } else {
            $_view['order_address'] = $post['order_address'];

            $_view['warnings'] = $warnings;
        }
    }
} else {
    // 初期データを取得
    if (empty($_GET['id'])) {
        $_view['order_address'] = model('default_order_addresses');
    } else {
        $order_addresses = model('select_order_addresses', [
            'where' => [
                'id = :id AND user_id = :user_id',
                [
                    'id'      => $_GET['id'],
                    'user_id' => $_SESSION['auth']['user']['id'],
                ],
            ],
        ]);
        if (empty($order_addresses)) {
            warning('編集データが見つかりません。');
        } else {
            $_view['order_address'] = $order_addresses[0];
        }
    }

    // 投稿セッションを初期化
    unset($_SESSION['post']);

    // 編集開始日時を記録
    if (!empty($_GET['id'])) {
        $_SESSION['update']['order_address'] = localdate('Y-m-d H:i:s');
    }
}

// タイトル
if (empty($_GET['id'])) {
    $_view['title'] = '住所登録';
} else {
    $_view['title'] = '住所編集';
}
