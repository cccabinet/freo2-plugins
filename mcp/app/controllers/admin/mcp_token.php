<?php

import('plugins/mcp/app/services/mcp_token.php');

// MCP トークンを取得
$_view['mcp_tokens'] = model('select_mcp_tokens', [
    'where'    => service_mcp_token_where(),
    'order_by' => 'id DESC',
]);

// トークンを発行したユーザーの名前
$_view['users'] = [];
$user_ids = array_unique(array_map('intval', array_column($_view['mcp_tokens'], 'user_id')));
if (!empty($user_ids)) {
    $users = model('select_users', [
        'where' => 'id IN(' . implode(',', array_map('db_escape', $user_ids)) . ')',
    ]);
    foreach ($users as $user) {
        $_view['users'][$user['id']] = $user['name'] !== null && $user['name'] !== '' ? $user['name'] : $user['username'];
    }
}

// 発行したトークン（この画面で1回だけ表示する）
$_view['mcp_token_issued'] = null;
if (isset($_SESSION['mcp_token_issued'])) {
    $_view['mcp_token_issued'] = $_SESSION['mcp_token_issued'];

    unset($_SESSION['mcp_token_issued']);
}

// 受け口のURL
$_view['mcp_url'] = $GLOBALS['config']['http_url'] . '/mcp/';

// タイトル
$_view['title'] = 'MCPトークン管理';
