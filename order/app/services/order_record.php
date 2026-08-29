<?php

import('app/services/log.php');

/**
 * 注文記録の登録
 *
 * @param array $queries
 * @param array $options
 *
 * @return resource
 */
function service_order_record_insert($queries, $options = [])
{
    // 操作ログの記録
    service_log_record(null, 'order_records', 'insert');

    // 注文記録を登録
    $resource = model('insert_order_records', $queries, $options);
    if (!$resource) {
        error('データを登録できません。');
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
function service_order_record_update($queries, $options = [])
{
    $options = [
        'id'     => isset($options['id'])     ? $options['id']     : null,
        'update' => isset($options['update']) ? $options['update'] : null,
        'items'  => isset($options['items'])  ? $options['items']  : null,
    ];

    // 最終編集日時を確認
    if (isset($options['id']) && isset($options['update']) && (!isset($queries['set']['modified']) || $queries['set']['modified'] !== false)) {
        $order_records = model('select_order_records', [
            'where' => [
                'id = :id AND modified > :update',
                [
                    'id'     => $options['id'],
                    'update' => $options['update'],
                ],
            ],
        ]);
        if (!empty($order_records)) {
            error('編集開始後にデータが更新されています。');
        }
    }

    // 操作ログの記録
    service_log_record(null, 'order_records', 'update');

    // 注文記録を編集
    $resource = model('update_order_records', $queries, $options);
    if (!$resource) {
        error('データを編集できません。');
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
function service_order_record_delete($queries, $options = [])
{
    // 操作ログの記録
    service_log_record(null, 'order_records', 'delete');

    // 注文記録を削除
    $resource = model('delete_order_records', $queries, $options);
    if (!$resource) {
        error('データを削除できません。');
    }

    return $resource;
}
