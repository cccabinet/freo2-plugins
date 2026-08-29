<?php

import('app/services/log.php');

/**
 * 支払方法の登録
 *
 * @param array $queries
 * @param array $options
 *
 * @return resource
 */
function service_order_payment_insert($queries, $options = [])
{
    // 操作ログの記録
    service_log_record(null, 'order_payments', 'insert');

    // 支払方法を登録
    $resource = model('insert_order_payments', $queries, $options);
    if (!$resource) {
        error('データを登録できません。');
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
function service_order_payment_update($queries, $options = [])
{
    $options = [
        'id'     => isset($options['id'])     ? $options['id']     : null,
        'update' => isset($options['update']) ? $options['update'] : null,
    ];

    // 最終編集日時を確認
    if (isset($options['id']) && isset($options['update']) && (!isset($queries['set']['modified']) || $queries['set']['modified'] !== false)) {
        $order_payments = model('select_order_payments', [
            'where' => [
                'id = :id AND modified > :update',
                [
                    'id'     => $options['id'],
                    'update' => $options['update'],
                ],
            ],
        ]);
        if (!empty($order_payments)) {
            error('編集開始後にデータが更新されています。');
        }
    }

    // 操作ログの記録
    service_log_record(null, 'order_payments', 'update');

    // 支払方法を編集
    $resource = model('update_order_payments', $queries, $options);
    if (!$resource) {
        error('データを編集できません。');
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
function service_order_payment_delete($queries, $options = [])
{
    // 操作ログの記録
    service_log_record(null, 'order_payments', 'delete');

    // 支払方法を削除
    $resource = model('delete_order_payments', $queries, $options);
    if (!$resource) {
        error('データを削除できません。');
    }

    return $resource;
}
