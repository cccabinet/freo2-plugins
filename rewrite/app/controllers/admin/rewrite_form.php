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
        'rewrite_rule' => model('normalize_rewrite_rules', [
            'id'       => isset($_POST['id'])       ? $_POST['id']       : '',
            'enabled'  => isset($_POST['enabled'])  ? $_POST['enabled']  : '',
            'name'     => isset($_POST['name'])     ? $_POST['name']     : '',
            'url'      => isset($_POST['url'])      ? $_POST['url']      : '',
            'rewrited' => isset($_POST['rewrited']) ? $_POST['rewrited'] : '',
            'type'     => isset($_POST['type'])     ? $_POST['type']     : '',
            'memo'     => isset($_POST['memo'])     ? $_POST['memo']     : '',
        ]),
    ];

    // 入力データを検証＆登録
    $warnings = model('validate_rewrite_rules', $post['rewrite_rule']);
    if (isset($_POST['_type']) && $_POST['_type'] === 'json') {
        if (empty($warnings)) {
            ok();
        } else {
            warning($warnings);
        }
    } else {
        if (empty($warnings)) {
            $_SESSION['post']['rewrite_rule'] = $post['rewrite_rule'];

            // フォワード
            forward('plugins/rewrite/app/controllers/admin/rewrite_post.php');
        } else {
            $_view['rewrite_rule'] = $post['rewrite_rule'];

            $_view['warnings'] = $warnings;
        }
    }
} else {
    // 初期データを取得
    if (empty($_GET['id'])) {
        $_view['rewrite_rule'] = model('default_rewrite_rules');
    } else {
        $rewrite_rules = model('select_rewrite_rules', [
            'where' => [
                'id = :id',
                [
                    'id' => $_GET['id'],
                ],
            ],
        ]);
        if (empty($rewrite_rules)) {
            warning('編集データが見つかりません。');
        } else {
            $_view['rewrite_rule'] = $rewrite_rules[0];
        }
    }

    // 投稿セッションを初期化
    unset($_SESSION['post']);

    // 編集開始日時を記録
    if (!empty($_GET['id'])) {
        $_SESSION['update']['rewrite_rule'] = localdate('Y-m-d H:i:s');
    }
}

// タイトル
if (empty($_GET['id'])) {
    $_view['title'] = 'リライト登録';
} else {
    $_view['title'] = 'リライト編集';
}
