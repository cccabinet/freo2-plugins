<?php

/**
 * 注文記録の取得
 *
 * @param array $queries
 * @param array $options
 *
 * @return array
 */
function select_order_record_items($queries, $options = [])
{
    $queries = db_placeholder($queries);

    // 注文記録を取得
    $queries['from'] = DATABASE_PREFIX . 'order_record_items';

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
 * 注文記録の登録
 *
 * @param array $queries
 * @param array $options
 *
 * @return resource
 */
function insert_order_record_items($queries, $options = [])
{
    $queries = db_placeholder($queries);

    // 初期値を取得
    $defaults = model('default_order_record_items');

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
    $queries['insert_into'] = DATABASE_PREFIX . 'order_record_items';

    $resource = db_insert($queries);
    if (!$resource) {
        return $resource;
    }

    return $resource;
}

/**
 * 注文記録の編集
 *
 * @param array $queries
 * @param array $options
 *
 * @return resource
 */
function update_order_record_items($queries, $options = [])
{
    $queries = db_placeholder($queries);

    // 初期値を取得
    $defaults = model('default_order_record_items');

    if (isset($queries['set']['modified'])) {
        if ($queries['set']['modified'] === false) {
            unset($queries['set']['modified']);
        }
    } else {
        $queries['set']['modified'] = $defaults['modified'];
    }

    // データを編集
    $queries['update'] = DATABASE_PREFIX . 'order_record_items';

    $resource = db_update($queries);
    if (!$resource) {
        return $resource;
    }

    return $resource;
}

/**
 * 注文記録の削除
 *
 * @param array $queries
 * @param array $options
 *
 * @return resource
 */
function delete_order_record_items($queries, $options = [])
{
    $queries = db_placeholder($queries);
    $options = [
        'softdelete' => isset($options['softdelete']) ? $options['softdelete'] : true,
    ];

    if ($options['softdelete'] === true) {
        // データを編集
        $resource = db_update([
            'update' => DATABASE_PREFIX . 'order_record_items AS order_record_items',
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
            'delete_from' => DATABASE_PREFIX . 'order_record_items',
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
 * 注文記録の初期値
 *
 * @return array
 */
function default_order_record_items()
{
    return [
        'id'            => null,
        'created'       => localdate('Y-m-d H:i:s'),
        'modified'      => localdate('Y-m-d H:i:s'),
        'deleted'       => null,
        'record_id'     => 0,
        'spec_id'       => null,
        'name'          => null,
        'selling_price' => 0,
        'cost_price'    => null,
        'quantity'      => 0,
    ];
}
