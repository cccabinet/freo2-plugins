<?php

import('app/services/log.php');

/**
 * 住所の登録
 *
 * @param array $queries
 * @param array $options
 *
 * @return resource
 */
function service_order_address_insert($queries, $options = [])
{
    // 操作ログの記録
    service_log_record(null, 'order_addresses', 'insert');

    // 住所を登録
    $resource = model('insert_order_addresses', $queries, $options);
    if (!$resource) {
        error('データを登録できません。');
    }

    return $resource;
}

/**
 * 住所の編集
 *
 * @param array $queries
 * @param array $options
 *
 * @return resource
 */
function service_order_address_update($queries, $options = [])
{
    $options = [
        'id'     => isset($options['id'])     ? $options['id']     : null,
        'update' => isset($options['update']) ? $options['update'] : null,
    ];

    // 最終編集日時を確認
    if (isset($options['id']) && isset($options['update']) && (!isset($queries['set']['modified']) || $queries['set']['modified'] !== false)) {
        $order_addresses = model('select_order_addresses', [
            'where' => [
                'id = :id AND modified > :update',
                [
                    'id'     => $options['id'],
                    'update' => $options['update'],
                ],
            ],
        ]);
        if (!empty($order_addresses)) {
            error('編集開始後にデータが更新されています。');
        }
    }

    // 操作ログの記録
    service_log_record(null, 'order_addresses', 'update');

    // 住所を編集
    $resource = model('update_order_addresses', $queries, $options);
    if (!$resource) {
        error('データを編集できません。');
    }

    return $resource;
}

/**
 * 住所の削除
 *
 * @param array $queries
 * @param array $options
 *
 * @return resource
 */
function service_order_address_delete($queries, $options = [])
{
    // 操作ログの記録
    service_log_record(null, 'order_addresses', 'delete');

    // 住所を削除
    $resource = model('delete_order_addresses', $queries, $options);
    if (!$resource) {
        error('データを削除できません。');
    }

    return $resource;
}
