<?php

import('app/services/log.php');

/**
 * フィールドの登録
 *
 * @param array $queries
 * @param array $options
 *
 * @return resource
 */
function service_order_field_insert($queries, $options = [])
{
    // 操作ログの記録
    service_log_record(null, 'order_fields', 'insert');

    // フィールドを登録
    $resource = model('insert_order_fields', $queries, $options);
    if (!$resource) {
        error('データを登録できません。');
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
function service_order_field_update($queries, $options = [])
{
    $options = [
        'entry_id' => isset($options['entry_id']) ? $options['entry_id'] : null,
        'update'   => isset($options['update'])   ? $options['update']   : null,
    ];

    // 最終編集日時を確認
    if (isset($options['entry_id']) && isset($options['update']) && (!isset($queries['set']['modified']) || $queries['set']['modified'] !== false)) {
        $order_fields = model('select_order_fields', [
            'where' => [
                'entry_id = :entry_id AND modified > :update',
                [
                    'entry_id' => $options['entry_id'],
                    'update'   => $options['update'],
                ],
            ],
        ]);
        if (!empty($order_fields)) {
            error('編集開始後にデータが更新されています。');
        }
    }

    // 操作ログの記録
    service_log_record(null, 'order_fields', 'update');

    // フィールドを編集
    $resource = model('update_order_fields', $queries, $options);
    if (!$resource) {
        error('データを編集できません。');
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
function service_order_field_delete($queries, $options = [])
{
    // 操作ログの記録
    service_log_record(null, 'order_fields', 'delete');

    // フィールドを削除
    $resource = model('delete_order_fields', $queries, $options);
    if (!$resource) {
        error('データを削除できません。');
    }

    return $resource;
}
