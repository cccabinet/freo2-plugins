<?php

import('plugins/mcp/app/services/mcp_token.php');

// ワンタイムトークン
if (!token('check')) {
    error('不正な操作が検出されました。送信内容を確認して再度実行してください。');
}

// アクセス元
if (empty($_SERVER['HTTP_REFERER']) || !preg_match('/^' . preg_quote($GLOBALS['config']['http_url'], '/') . '/', $_SERVER['HTTP_REFERER'])) {
    error('不正なアクセスです。');
}

if (!empty($_POST['id'])) {
    // 操作できるトークンか確認（管理者以外は、自分のトークンだけ）
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

    // トランザクションを開始
    db_transaction();

    // MCPトークンを削除
    $resource = service_mcp_token_delete([
        'where' => [
            'mcp_tokens.id = :id',
            [
                'id' => $mcp_tokens[0]['id'],
            ],
        ],
    ]);
    if (!$resource) {
        error('データを削除できません。');
    }

    // トランザクションを終了
    db_commit();

    // リダイレクト
    redirect('/auth/mcp_token?ok=delete');
} else {
    // リダイレクト
    redirect('/auth/mcp_token?warning=delete');
}
