<?php

import('app/services/log.php');
import('app/services/user.php');

/**
 * トークンのハッシュ
 *
 * @param string $token
 *
 * @return string
 */
function service_mcp_token_hash($token)
{
    return hash('sha256', $token);
}

/**
 * トークンの作成
 *
 * 平文のトークンを返す。保存するのはハッシュだけ
 *
 * @return string
 */
function service_mcp_token_create()
{
    return service_user_token();
}

/**
 * MCPトークンの登録
 *
 * @param array $queries
 * @param array $options
 *
 * @return resource
 */
function service_mcp_token_insert($queries, $options = [])
{
    // 操作ログの記録
    service_log_record(null, 'mcp_tokens', 'insert');

    // MCPトークンを登録
    $resource = model('insert_mcp_tokens', $queries, $options);
    if (!$resource) {
        error('データを登録できません。');
    }

    return $resource;
}

/**
 * MCPトークンの編集
 *
 * @param array $queries
 * @param array $options
 *
 * @return resource
 */
function service_mcp_token_update($queries, $options = [])
{
    $options = [
        'id'     => isset($options['id'])     ? $options['id']     : null,
        'update' => isset($options['update']) ? $options['update'] : null,
    ];

    // 最終編集日時を確認
    if (isset($options['id']) && isset($options['update']) && (!isset($queries['set']['modified']) || $queries['set']['modified'] !== false)) {
        $mcp_tokens = model('select_mcp_tokens', [
            'where' => [
                'id = :id AND modified > :update',
                [
                    'id'     => $options['id'],
                    'update' => $options['update'],
                ],
            ],
        ]);
        if (!empty($mcp_tokens)) {
            error('編集開始後にデータが更新されています。');
        }
    }

    // 操作ログの記録
    service_log_record(null, 'mcp_tokens', 'update');

    // MCPトークンを編集
    $resource = model('update_mcp_tokens', $queries, $options);
    if (!$resource) {
        error('データを編集できません。');
    }

    return $resource;
}

/**
 * MCPトークンの削除
 *
 * @param array $queries
 * @param array $options
 *
 * @return resource
 */
function service_mcp_token_delete($queries, $options = [])
{
    // 操作ログの記録
    service_log_record(null, 'mcp_tokens', 'delete');

    // MCPトークンを削除
    $resource = model('delete_mcp_tokens', $queries, $options);
    if (!$resource) {
        error('データを削除できません。');
    }

    return $resource;
}

/**
 * 操作できるMCPトークンの条件
 *
 * 管理者はすべて、それ以外は自分のトークンだけ
 *
 * @return string
 */
function service_mcp_token_where()
{
    if ($GLOBALS['authority']['power'] >= 3) {
        return 'TRUE';
    }

    return service_mcp_token_where_own();
}

/**
 * 自分のMCPトークンの条件
 *
 * @return string
 */
function service_mcp_token_where_own()
{
    return 'user_id = ' . db_escape(intval($_SESSION['auth']['user']['id']));
}

/**
 * 自分のMCPトークンか
 *
 * ほかの人のトークン（管理者だけが開ける）は、名前だけを編集できる
 *
 * @param array $mcp_token
 *
 * @return bool
 */
function service_mcp_token_own($mcp_token)
{
    return isset($mcp_token['user_id']) && intval($mcp_token['user_id']) === intval($_SESSION['auth']['user']['id']);
}
