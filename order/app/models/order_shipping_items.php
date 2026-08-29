<?php

/**
 * 発送明細の取得
 *
 * @param array $queries
 * @param array $options
 *
 * @return array
 */
function select_order_shipping_items($queries, $options = [])
{
    $queries = db_placeholder($queries);

    // 発送明細を取得
    $queries['from'] = DATABASE_PREFIX . 'order_shipping_items';

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
 * 発送明細の登録
 *
 * @param array $queries
 * @param array $options
 *
 * @return resource
 */
function insert_order_shipping_items($queries, $options = [])
{
    $queries = db_placeholder($queries);

    // 初期値を取得
    $defaults = model('default_order_shipping_items');

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
    $queries['insert_into'] = DATABASE_PREFIX . 'order_shipping_items';

    $resource = db_insert($queries);
    if (!$resource) {
        return $resource;
    }

    return $resource;
}

/**
 * 発送明細の編集
 *
 * @param array $queries
 * @param array $options
 *
 * @return resource
 */
function update_order_shipping_items($queries, $options = [])
{
    $queries = db_placeholder($queries);

    // 初期値を取得
    $defaults = model('default_order_shipping_items');

    if (isset($queries['set']['modified'])) {
        if ($queries['set']['modified'] === false) {
            unset($queries['set']['modified']);
        }
    } else {
        $queries['set']['modified'] = $defaults['modified'];
    }

    // データを編集
    $queries['update'] = DATABASE_PREFIX . 'order_shipping_items';

    $resource = db_update($queries);
    if (!$resource) {
        return $resource;
    }

    return $resource;
}

/**
 * 発送明細の削除
 *
 * @param array $queries
 * @param array $options
 *
 * @return resource
 */
function delete_order_shipping_items($queries, $options = [])
{
    $queries = db_placeholder($queries);
    $options = [
        'softdelete' => isset($options['softdelete']) ? $options['softdelete'] : true,
    ];

    if ($options['softdelete'] === true) {
        // データを編集
        $resource = db_update([
            'update' => DATABASE_PREFIX . 'order_shipping_items AS order_shipping_items',
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
            'delete_from' => DATABASE_PREFIX . 'order_shipping_items',
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
 * 発送明細の初期値
 *
 * @return array
 */
function default_order_shipping_items()
{
    return [
        'id'             => null,
        'created'        => localdate('Y-m-d H:i:s'),
        'modified'       => localdate('Y-m-d H:i:s'),
        'deleted'        => null,
        'shipping_id'    => 0,
        'record_item_id' => 0,
        'quantity'       => 0,
    ];
}
