<?php

import('plugins/mcp/app/services/mcp_token.php');

// フォワードを確認
if (forward() === null) {
    error('不正なアクセスです。');
}

// 投稿データを確認
if (empty($_SESSION['post'])) {
    // リダイレクト
    redirect('/auth/mcp_token_form');
}

if (empty($_SESSION['post']['mcp_token']['id'])) {
    // トークンを作成（保存するのはハッシュだけ）
    $token = service_mcp_token_create();

    // トランザクションを開始
    db_transaction();

    // MCPトークンを登録（発行したユーザーのトークンにする）
    $resource = service_mcp_token_insert([
        'values' => [
            'user_id' => $_SESSION['auth']['user']['id'],
            'name'    => $_SESSION['post']['mcp_token']['name'],
            'token'   => service_mcp_token_hash($token),
            'scope'   => $_SESSION['post']['mcp_token']['scope'],
            'expire'  => $_SESSION['post']['mcp_token']['expire'],
        ],
    ]);
    if (!$resource) {
        error('データを登録できません。');
    }

    // トランザクションを終了
    db_commit();

    // 発行したトークンを、一覧で1回だけ表示する
    $_SESSION['mcp_token_issued'] = $token;

    $ok = 'post';
} else {
    // トランザクションを開始
    db_transaction();

    // MCPトークンを編集（名前と有効期限だけ。ほかの人のトークンの有効期限は、入力画面で今の値にしてある）
    $resource = service_mcp_token_update([
        'set'   => [
            'name'   => $_SESSION['post']['mcp_token']['name'],
            'expire' => $_SESSION['post']['mcp_token']['expire'],
        ],
        'where' => [
            'id = :id AND ' . service_mcp_token_where(),
            [
                'id' => $_SESSION['post']['mcp_token']['id'],
            ],
        ],
    ], [
        'id'     => intval($_SESSION['post']['mcp_token']['id']),
        'update' => isset($_SESSION['update']['mcp_token']) ? $_SESSION['update']['mcp_token'] : null,
    ]);
    if (!$resource) {
        error('データを編集できません。');
    }

    // トランザクションを終了
    db_commit();

    $ok = 'edit';
}

// 投稿セッションを初期化
unset($_SESSION['post']);
unset($_SESSION['update']);

// リダイレクト
redirect('/auth/mcp_token?ok=' . $ok);
