<?php

import('app/services/log.php');

/**
 * 製品の登録
 *
 * @param array $queries
 * @param array $options
 *
 * @return resource
 */
function service_order_product_insert($queries, $options = [])
{
    // 操作ログの記録
    service_log_record(null, 'order_products', 'insert');

    // 製品を登録
    $resource = model('insert_order_products', $queries, $options);
    if (!$resource) {
        error('データを登録できません。');
    }

    return $resource;
}

/**
 * 製品の編集
 *
 * @param array $queries
 * @param array $options
 *
 * @return resource
 */
function service_order_product_update($queries, $options = [])
{
    $options = [
        'id'     => isset($options['id'])     ? $options['id']     : null,
        'update' => isset($options['update']) ? $options['update'] : null,
    ];

    // 最終編集日時を確認
    if (isset($options['id']) && isset($options['update']) && (!isset($queries['set']['modified']) || $queries['set']['modified'] !== false)) {
        $order_products = model('select_order_products', [
            'where' => [
                'id = :id AND modified > :update',
                [
                    'id'     => $options['id'],
                    'update' => $options['update'],
                ],
            ],
        ]);
        if (!empty($order_products)) {
            error('編集開始後にデータが更新されています。');
        }
    }

    // 操作ログの記録
    service_log_record(null, 'order_products', 'update');

    // 製品を編集
    $resource = model('update_order_products', $queries, $options);
    if (!$resource) {
        error('データを編集できません。');
    }

    return $resource;
}

/**
 * 製品の削除
 *
 * @param array $queries
 * @param array $options
 *
 * @return resource
 */
function service_order_product_delete($queries, $options = [])
{
    // 操作ログの記録
    service_log_record(null, 'order_products', 'delete');

    // 製品を削除
    $resource = model('delete_order_products', $queries, $options);
    if (!$resource) {
        error('データを削除できません。');
    }

    return $resource;
}

/**
 * 製品の並び順を一括変更
 *
 * @param array $data
 *
 * @return void
 */
function service_order_product_sort($data)
{
    // 並び順を更新
    foreach ($data as $id => $sort) {
        if (!preg_match('/^[\w\-\/]+$/', $id)) {
            continue;
        }
        if (!preg_match('/^\d+$/', $sort)) {
            continue;
        }

        $resource = service_order_product_update([
            'set'   => [
                'sort' => $sort,
            ],
            'where' => [
                'id = :id',
                [
                    'id' => $id,
                ],
            ],
        ]);
        if (!$resource) {
            error('データを編集できません。');
        }
    }

    return;
}
