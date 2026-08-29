<?php

import('app/services/log.php');

/**
 * 発送記録の登録
 *
 * @param array $queries
 * @param array $options
 *
 * @return resource
 */
function service_order_shipping_insert($queries, $options = [])
{
    // 操作ログの記録
    service_log_record(null, 'order_shippings', 'insert');

    // 発送記録を登録
    $resource = model('insert_order_shippings', $queries, $options);
    if (!$resource) {
        error('データを登録できません。');
    }

    return $resource;
}

/**
 * 発送記録の編集
 *
 * @param array $queries
 * @param array $options
 *
 * @return resource
 */
function service_order_shipping_update($queries, $options = [])
{
    $update_options = [
        'id'         => isset($options['id'])         ? $options['id']         : null,
        'update'     => isset($options['update'])     ? $options['update']     : null,
        'record_id'  => isset($options['record_id'])  ? $options['record_id']  : null,
    ];
    if (array_key_exists('items', $options)) {
        $update_options['items'] = $options['items'];
    }
    $options = $update_options;

    // 最終編集日時を確認
    if (isset($options['id']) && isset($options['update']) && (!isset($queries['set']['modified']) || $queries['set']['modified'] !== false)) {
        $order_shippings = model('select_order_shippings', [
            'where' => [
                'id = :id AND modified > :update',
                [
                    'id'     => $options['id'],
                    'update' => $options['update'],
                ],
            ],
        ]);
        if (!empty($order_shippings)) {
            error('編集開始後にデータが更新されています。');
        }
    }

    // 操作ログの記録
    service_log_record(null, 'order_shippings', 'update');

    // 発送記録を編集
    $resource = model('update_order_shippings', $queries, $options);
    if (!$resource) {
        error('データを編集できません。');
    }

    return $resource;
}

/**
 * 発送記録の削除
 *
 * @param array $queries
 * @param array $options
 *
 * @return resource
 */
function service_order_shipping_delete($queries, $options = [])
{
    // 操作ログの記録
    service_log_record(null, 'order_shippings', 'delete');

    // 発送記録を削除
    $resource = model('delete_order_shippings', $queries, $options);
    if (!$resource) {
        error('データを削除できません。');
    }

    return $resource;
}
