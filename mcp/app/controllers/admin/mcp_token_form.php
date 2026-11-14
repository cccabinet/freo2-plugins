<?php

import('plugins/mcp/app/services/mcp_token.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ワンタイムトークン
    if (!token('check')) {
        error('不正な操作が検出されました。送信内容を確認して再度実行してください。');
    }

    // アクセス元
    if (empty($_SERVER['HTTP_REFERER']) || !preg_match('/^' . preg_quote($GLOBALS['config']['http_url'], '/') . '/', $_SERVER['HTTP_REFERER'])) {
        error('不正なアクセスです。');
    }

    // 編集するトークン（管理者以外は、自分のトークンだけ）
    $current = null;
    if (!empty($_POST['id'])) {
        $mcp_tokens = model('select_mcp_tokens', [
            'where' => [
                'id = :id AND ' . service_mcp_token_where(),
                [
                    'id' => $_POST['id'],
                ],
            ],
        ]);
        if (empty($mcp_tokens)) {
            error('不正なアクセスです。');
        }
        $current = $mcp_tokens[0];
    }

    // 入力データを整理（範囲は発行するときだけ受け取る。変えたいときは発行し直す）
    $input = [
        'name'   => isset($_POST['name'])   ? $_POST['name']   : '',
        'expire' => isset($_POST['expire']) ? $_POST['expire'] : '',
    ];
    if ($current === null) {
        $input['scope'] = isset($_POST['scope']) ? $_POST['scope'] : '';
    }
    $post = [
        'mcp_token' => model('normalize_mcp_tokens', $input),
    ];

    // 入力データを検証
    $warnings = model('validate_mcp_tokens', $post['mcp_token']);

    // ほかの人のトークンは、名前だけを編集できる（有効期限は今の値のままにする）
    if ($current !== null && !service_mcp_token_own($current)) {
        $post['mcp_token']['expire'] = $current['expire'];
    }

    // 期限切れのトークンでも、有効期限を変えずに名前だけを直せるようにする
    if ($current !== null && isset($warnings['expire']) && $post['mcp_token']['expire'] === $current['expire']) {
        unset($warnings['expire']);
    }

    // 表示用（編集のときは、ID・発行したユーザー・範囲を今の値にする）
    if ($current !== null) {
        $post['mcp_token']['id']      = $current['id'];
        $post['mcp_token']['user_id'] = $current['user_id'];
        $post['mcp_token']['scope']   = $current['scope'];
    }

    // 入力データを登録
    if (isset($_POST['_type']) && $_POST['_type'] === 'json') {
        if (empty($warnings)) {
            ok();
        } else {
            warning($warnings);
        }
    } else {
        if (empty($warnings)) {
            $_SESSION['post']['mcp_token'] = $post['mcp_token'];

            // フォワード
            forward('plugins/mcp/app/controllers/admin/mcp_token_post.php');
        } else {
            $_view['mcp_token'] = $post['mcp_token'];

            $_view['warnings'] = $warnings;
        }
    }
} else {
    // 初期データを取得
    if (empty($_GET['id'])) {
        $_view['mcp_token'] = model('default_mcp_tokens');
    } else {
        $mcp_tokens = model('select_mcp_tokens', [
            'where' => [
                'id = :id AND ' . service_mcp_token_where(),
                [
                    'id' => $_GET['id'],
                ],
            ],
        ]);
        if (empty($mcp_tokens)) {
            warning('編集データが見つかりません。');
        } else {
            $_view['mcp_token'] = $mcp_tokens[0];
        }

        // 編集開始日時を記録
        $_SESSION['update']['mcp_token'] = localdate('Y-m-d H:i:s');
    }

    // 投稿セッションを初期化
    unset($_SESSION['post']);
}

// 有効期限を編集できるか（発行するときと、自分のトークンだけ）
$_view['mcp_token_expire_editable'] = empty($_view['mcp_token']['id']) || service_mcp_token_own($_view['mcp_token']);

// タイトル
if (empty($_view['mcp_token']['id'])) {
    $_view['title'] = 'MCPトークン発行';
} else {
    $_view['title'] = 'MCPトークン編集';
}
