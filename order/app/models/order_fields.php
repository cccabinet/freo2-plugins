<?php

import('libs/modules/validator.php');

/**
 * フィールドの取得
 *
 * @param array $queries
 * @param array $options
 *
 * @return array
 */
function select_order_fields($queries, $options = [])
{
    $queries = db_placeholder($queries);

    // フィールドを取得
    $queries['from'] = DATABASE_PREFIX . 'order_fields';

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
 * フィールドの登録
 *
 * @param array $queries
 * @param array $options
 *
 * @return resource
 */
function insert_order_fields($queries, $options = [])
{
    $queries = db_placeholder($queries);

    // 初期値を取得
    $defaults = model('default_order_fields');

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
    $queries['insert_into'] = DATABASE_PREFIX . 'order_fields';

    $resource = db_insert($queries);
    if (!$resource) {
        return $resource;
    }

    return $resource;
}

/**
 * フィールドの編集
 *
 * @param array $queries
 * @param array $options
 *
 * @return resource
 */
function update_order_fields($queries, $options = [])
{
    $queries = db_placeholder($queries);

    // 初期値を取得
    $defaults = model('default_order_fields');

    if (isset($queries['set']['modified'])) {
        if ($queries['set']['modified'] === false) {
            unset($queries['set']['modified']);
        }
    } else {
        $queries['set']['modified'] = $defaults['modified'];
    }

    // データを編集
    $queries['update'] = DATABASE_PREFIX . 'order_fields';

    $resource = db_update($queries);
    if (!$resource) {
        return $resource;
    }

    return $resource;
}

/**
 * フィールドの削除
 *
 * @param array $queries
 * @param array $options
 *
 * @return resource
 */
function delete_order_fields($queries, $options = [])
{
    $queries = db_placeholder($queries);
    $options = [
        'softdelete' => isset($options['softdelete']) ? $options['softdelete'] : true,
    ];

    if ($options['softdelete'] === true) {
        // データを編集
        $resource = db_update([
            'update' => DATABASE_PREFIX . 'order_fields AS order_fields',
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
            'delete_from' => DATABASE_PREFIX . 'order_fields',
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
 * フィールドの正規化
 *
 * @param array $queries
 * @param array $options
 *
 * @return array
 */
function normalize_order_fields($queries, $options = [])
{
    // 販売価格
    if (isset($queries['selling_price'])) {
        $queries['selling_price'] = mb_convert_kana($queries['selling_price'], 'n', MAIN_INTERNAL_ENCODING);
    }

    // 通常価格
    if (isset($queries['regular_price'])) {
        $queries['regular_price'] = mb_convert_kana($queries['regular_price'], 'n', MAIN_INTERNAL_ENCODING);
    }

    // 送料
    if (isset($queries['shipping_cost'])) {
        $queries['shipping_cost'] = mb_convert_kana($queries['shipping_cost'], 'n', MAIN_INTERNAL_ENCODING);
    }

    // 配送日目安
    if (isset($queries['delivery_days'])) {
        $queries['delivery_days'] = mb_convert_kana($queries['delivery_days'], 'n', MAIN_INTERNAL_ENCODING);
    }

    // 販売制限数
    if (isset($queries['sales_limit'])) {
        $queries['sales_limit'] = mb_convert_kana($queries['sales_limit'], 'n', MAIN_INTERNAL_ENCODING);
    }

    return $queries;
}

/**
 * フィールドの検証
 *
 * @param array $queries
 * @param array $options
 *
 * @return array
 */
function validate_order_fields($queries, $options = [])
{
    $options = [
        'duplicate' => isset($options['duplicate']) ? $options['duplicate'] : true,
    ];

    $messages = [];

    // 販売価格
    if (isset($queries['selling_price'])) {
        if (!validator_required($queries['selling_price'])) {
            $messages['selling_price'] = '販売価格が入力されていません。';
        } elseif (!validator_numeric($queries['selling_price'])) {
            $messages['selling_price'] = '販売価格は半角数字で入力してください。';
        } elseif (!validator_max_length($queries['selling_price'], 10)) {
            $messages['selling_price'] = '販売価格は10桁以内で入力してください。';
        }
    }

    // 通常価格
    if (isset($queries['regular_price'])) {
        if (!validator_required($queries['regular_price'])) {
        } elseif (!validator_numeric($queries['regular_price'])) {
            $messages['regular_price'] = '通常価格は半角数字で入力してください。';
        } elseif (!validator_max_length($queries['regular_price'], 10)) {
            $messages['regular_price'] = '通常価格は10桁以内で入力してください。';
        }
    }

    // 送料
    if (isset($queries['shipping_cost'])) {
        if (!validator_required($queries['shipping_cost'])) {
        } elseif (!validator_numeric($queries['shipping_cost'])) {
            $messages['shipping_cost'] = '送料は半角数字で入力してください。';
        } elseif (!validator_max_length($queries['shipping_cost'], 10)) {
            $messages['shipping_cost'] = '送料は10桁以内で入力してください。';
        }
    }

    // 配送日目安
    if (isset($queries['delivery_days'])) {
        if (!validator_required($queries['delivery_days'])) {
        } elseif (!validator_numeric($queries['delivery_days'])) {
            $messages['delivery_days'] = '配送日目安は半角数字で入力してください。';
        } elseif (!validator_max_length($queries['delivery_days'], 10)) {
            $messages['delivery_days'] = '配送日目安は10桁以内で入力してください。';
        }
    }

    // 販売制限数
    if (isset($queries['sales_limit'])) {
        if (!validator_required($queries['sales_limit'])) {
        } elseif (!validator_numeric($queries['sales_limit'])) {
            $messages['sales_limit'] = '販売制限数は半角数字で入力してください。';
        } elseif (!validator_max_length($queries['sales_limit'], 10)) {
            $messages['sales_limit'] = '販売制限数は10桁以内で入力してください。';
        }
    }

    // メモ
    if (isset($queries['memo'])) {
        if (!validator_required($queries['memo'])) {
        } elseif (!validator_max_length($queries['memo'], 5000)) {
            $messages['memo'] = 'メモは5000文字以内で入力してください。';
        }
    }

    return $messages;
}

/**
 * フィールドの初期値
 *
 * @return array
 */
function default_order_fields()
{
    return [
        'id'            => null,
        'created'       => localdate('Y-m-d H:i:s'),
        'modified'      => localdate('Y-m-d H:i:s'),
        'deleted'       => null,
        'entry_id'      => 0,
        'selling_price' => 0,
        'regular_price' => null,
        'shipping_cost' => null,
        'delivery_days' => null,
        'sales_limit'   => null,
        'memo'          => null,
    ];
}
