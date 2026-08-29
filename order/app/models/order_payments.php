<?php

import('libs/modules/validator.php');

/**
 * 支払方法の取得
 *
 * @param array $queries
 * @param array $options
 *
 * @return array
 */
function select_order_payments($queries, $options = [])
{
    $queries = db_placeholder($queries);

    // 支払方法を取得
    $queries['from'] = DATABASE_PREFIX . 'order_payments';

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
 * 支払方法の登録
 *
 * @param array $queries
 * @param array $options
 *
 * @return resource
 */
function insert_order_payments($queries, $options = [])
{
    $queries = db_placeholder($queries);

    // 初期値を取得
    $defaults = model('default_order_payments');

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
    $queries['insert_into'] = DATABASE_PREFIX . 'order_payments';

    $resource = db_insert($queries);
    if (!$resource) {
        return $resource;
    }

    return $resource;
}

/**
 * 支払方法の編集
 *
 * @param array $queries
 * @param array $options
 *
 * @return resource
 */
function update_order_payments($queries, $options = [])
{
    $queries = db_placeholder($queries);

    // 初期値を取得
    $defaults = model('default_order_payments');

    if (isset($queries['set']['modified'])) {
        if ($queries['set']['modified'] === false) {
            unset($queries['set']['modified']);
        }
    } else {
        $queries['set']['modified'] = $defaults['modified'];
    }

    // データを編集
    $queries['update'] = DATABASE_PREFIX . 'order_payments';

    $resource = db_update($queries);
    if (!$resource) {
        return $resource;
    }

    return $resource;
}

/**
 * 支払方法の削除
 *
 * @param array $queries
 * @param array $options
 *
 * @return resource
 */
function delete_order_payments($queries, $options = [])
{
    $queries = db_placeholder($queries);
    $options = [
        'softdelete' => isset($options['softdelete']) ? $options['softdelete'] : true,
    ];

    if ($options['softdelete'] === true) {
        // データを編集
        $resource = db_update([
            'update' => DATABASE_PREFIX . 'order_payments AS order_payments',
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
            'delete_from' => DATABASE_PREFIX . 'order_payments',
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
 * 支払方法の正規化
 *
 * @param array $queries
 * @param array $options
 *
 * @return array
 */
function normalize_order_payments($queries, $options = [])
{
    // 手数料
    if (isset($queries['fee'])) {
        $queries['fee'] = mb_convert_kana($queries['fee'], 'n', MAIN_INTERNAL_ENCODING);
    }

    return $queries;
}

/**
 * 支払方法の検証
 *
 * @param array $queries
 * @param array $options
 *
 * @return array
 */
function validate_order_payments($queries, $options = [])
{
    $messages = [];

    // 有効
    if (isset($queries['enabled'])) {
        if (!validator_boolean($queries['enabled'])) {
            $messages['enabled'] = '有効の書式が不正です。';
        }
    }

    // 名前
    if (isset($queries['name'])) {
        if (!validator_required($queries['name'])) {
            $messages['name'] = '名前が入力されていません。';
        } elseif (!validator_max_length($queries['name'], 20)) {
            $messages['name'] = '名前は20文字以内で入力してください。';
        }
    }

    // 内容
    if (isset($queries['text'])) {
        if (!validator_required($queries['text'])) {
        } elseif (!validator_max_length($queries['text'], 5000)) {
            $messages['text'] = '内容は5000文字以内で入力してください。';
        }
    }

    // 手数料
    if (isset($queries['fee'])) {
        if (!validator_required($queries['fee'])) {
            $messages['fee'] = '手数料が入力されていません。';
        } elseif (!validator_numeric($queries['fee'])) {
            $messages['fee'] = '手数料は半角数字で入力してください。';
        } elseif (!validator_max_length($queries['fee'], 10)) {
            $messages['fee'] = '手数料は10桁以内で入力してください。';
        }
    }

    // 店舗用メモ
    if (isset($queries['memo'])) {
        if (!validator_required($queries['memo'])) {
        } elseif (!validator_max_length($queries['memo'], 5000)) {
            $messages['memo'] = '店舗用メモは5000文字以内で入力してください。';
        }
    }

    return $messages;
}

/**
 * 支払方法の初期値
 *
 * @return array
 */
function default_order_payments()
{
    return [
        'id'       => null,
        'created'  => localdate('Y-m-d H:i:s'),
        'modified' => localdate('Y-m-d H:i:s'),
        'deleted'  => null,
        'enabled'  => 1,
        'name'     => '',
        'text'     => null,
        'fee'      => 0,
        'memo'     => null,
    ];
}
