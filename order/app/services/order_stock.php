<?php

import('app/services/log.php');

/**
 * 在庫の登録
 *
 * @param array $queries
 * @param array $options
 *
 * @return resource
 */
function service_order_stock_insert($queries, $options = [])
{
    // 操作ログの記録
    service_log_record(null, 'order_stocks', 'insert');

    // 在庫を登録
    $resource = model('insert_order_stocks', $queries, $options);
    if (!$resource) {
        error('データを登録できません。');
    }

    return $resource;
}

/**
 * 在庫の編集
 *
 * @param array $queries
 * @param array $options
 *
 * @return resource
 */
function service_order_stock_update($queries, $options = [])
{
    $options = [
        'id'     => isset($options['id'])     ? $options['id']     : null,
        'update' => isset($options['update']) ? $options['update'] : null,
    ];

    // 最終編集日時を確認
    if (isset($options['id']) && isset($options['update']) && (!isset($queries['set']['modified']) || $queries['set']['modified'] !== false)) {
        $order_stocks = model('select_order_stocks', [
            'where' => [
                'id = :id AND modified > :update',
                [
                    'id'     => $options['id'],
                    'update' => $options['update'],
                ],
            ],
        ]);
        if (!empty($order_stocks)) {
            error('編集開始後にデータが更新されています。');
        }
    }

    // 操作ログの記録
    service_log_record(null, 'order_stocks', 'update');

    // 在庫を編集
    $resource = model('update_order_stocks', $queries, $options);
    if (!$resource) {
        error('データを編集できません。');
    }

    return $resource;
}

/**
 * 在庫の削除
 *
 * @param array $queries
 * @param array $options
 *
 * @return resource
 */
function service_order_stock_delete($queries, $options = [])
{
    // 操作ログの記録
    service_log_record(null, 'order_stocks', 'delete');

    // 在庫を削除
    $resource = model('delete_order_stocks', $queries, $options);
    if (!$resource) {
        error('データを削除できません。');
    }

    return $resource;
}
