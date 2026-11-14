<?php

import('libs/modules/validator.php');

/**
 * MCP トークンの取得
 *
 * @param array $queries
 * @param array $options
 *
 * @return array
 */
function select_mcp_tokens($queries, $options = [])
{
    $queries = db_placeholder($queries);

    // MCP トークンを取得
    $queries['from'] = DATABASE_PREFIX . 'mcp_tokens';

    // 削除済みデータは取得しない
    if (!isset($queries['where'])) {
        $queries['where'] = 'TRUE';
    }
    $queries['where'] = 'deleted IS NULL AND (' . $queries['where'] . ')';

    // データを取得
    $results = db_select($queries);

    return $results;
}

/**
 * MCP トークンの登録
 *
 * @param array $queries
 * @param array $options
 *
 * @return resource
 */
function insert_mcp_tokens($queries, $options = [])
{
    $queries = db_placeholder($queries);

    // 初期値を取得
    $defaults = model('default_mcp_tokens');

    if (isset($queries['values']['created'])) {
        if ($queries['values']['created'] === false) {
            unset($queries['values']['created']);
        }
    } else {
        $queries['values']['created'] = $defaults['created'];
    }
    if (isset($queries['values']['modified'])) {
        if ($queries['values']['modified'] === false) {
            unset($queries['values']['modified']);
        }
    } else {
        $queries['values']['modified'] = $defaults['modified'];
    }

    // データを登録
    $queries['insert_into'] = DATABASE_PREFIX . 'mcp_tokens';

    $resource = db_insert($queries);
    if (!$resource) {
        return $resource;
    }

    return $resource;
}

/**
 * MCP トークンの編集
 *
 * @param array $queries
 * @param array $options
 *
 * @return resource
 */
function update_mcp_tokens($queries, $options = [])
{
    $queries = db_placeholder($queries);

    // 初期値を取得
    $defaults = model('default_mcp_tokens');

    if (isset($queries['set']['modified'])) {
        if ($queries['set']['modified'] === false) {
            unset($queries['set']['modified']);
        }
    } else {
        $queries['set']['modified'] = $defaults['modified'];
    }

    // データを編集
    $queries['update'] = DATABASE_PREFIX . 'mcp_tokens';

    $resource = db_update($queries);
    if (!$resource) {
        return $resource;
    }

    return $resource;
}

/**
 * MCP トークンの削除
 *
 * @param array $queries
 * @param array $options
 *
 * @return resource
 */
function delete_mcp_tokens($queries, $options = [])
{
    $queries = db_placeholder($queries);
    $options = [
        'softdelete' => isset($options['softdelete']) ? $options['softdelete'] : true,
    ];

    if ($options['softdelete'] === true) {
        // データを編集
        $resource = db_update([
            'update' => DATABASE_PREFIX . 'mcp_tokens AS mcp_tokens',
            'set'    => [
                'deleted' => localdate('Y-m-d H:i:s'),
            ],
            'where'  => isset($queries['where']) ? $queries['where'] : '',
            'limit'  => isset($queries['limit']) ? $queries['limit'] : '',
        ]);
        if (!$resource) {
            return $resource;
        }
    } else {
        // データを削除
        $resource = db_delete([
            'delete_from' => DATABASE_PREFIX . 'mcp_tokens',
            'where'       => isset($queries['where']) ? $queries['where'] : '',
            'limit'       => isset($queries['limit']) ? $queries['limit'] : '',
        ]);
        if (!$resource) {
            return $resource;
        }
    }

    return $resource;
}

/**
 * MCP トークンの正規化
 *
 * @param array $queries
 * @param array $options
 *
 * @return array
 */
function normalize_mcp_tokens($queries, $options = [])
{
    // 有効期限
    if (isset($queries['expire'])) {
        $queries['expire'] = mb_convert_kana($queries['expire'], 'a', MAIN_INTERNAL_ENCODING);

        // 日付だけなら、その日の終わりまで有効にする
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $queries['expire'])) {
            $queries['expire'] .= ' 23:59:59';
        }
    }

    return $queries;
}

/**
 * MCP トークンの検証
 *
 * @param array $queries
 * @param array $options
 *
 * @return array
 */
function validate_mcp_tokens($queries, $options = [])
{
    $messages = [];

    // ユーザー
    if (isset($queries['user_id'])) {
        if (!validator_required($queries['user_id'])) {
            $messages['user_id'] = 'ユーザーが入力されていません。';
        }
    }

    // 名前
    if (isset($queries['name'])) {
        if (!validator_required($queries['name'])) {
            $messages['name'] = '名前が入力されていません。';
        } elseif (!validator_max_length($queries['name'], 100)) {
            $messages['name'] = '名前は100文字以内で入力してください。';
        }
    }

    // 範囲
    if (isset($queries['scope'])) {
        if (!validator_required($queries['scope'])) {
            $messages['scope'] = '範囲が入力されていません。';
        } elseif (!validator_list($queries['scope'], $GLOBALS['plugin']['mcp']['option']['mcp_token']['scope'])) {
            $messages['scope'] = '範囲の値が不正です。';
        }
    }

    // 有効期限
    if (isset($queries['expire'])) {
        if (!validator_required($queries['expire'])) {
        } elseif (!validator_datetime($queries['expire'])) {
            $messages['expire'] = '有効期限の値が不正です。';
        } elseif ($queries['expire'] <= localdate('Y-m-d H:i:s')) {
            $messages['expire'] = '有効期限は今日以降の日付を指定してください。';
        }
    }

    return $messages;
}

/**
 * MCP トークンの初期値
 *
 * @return array
 */
function default_mcp_tokens()
{
    return [
        'id'       => null,
        'created'  => localdate('Y-m-d H:i:s'),
        'modified' => localdate('Y-m-d H:i:s'),
        'deleted'  => null,
        'user_id'  => null,
        'name'     => '',
        'token'    => '',
        'scope'    => 'read',
        'accessed' => null,
        'expire'   => null,
    ];
}
