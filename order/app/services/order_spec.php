<?php

import('app/services/log.php');

/**
 * 規格の登録
 *
 * @param array $queries
 * @param array $options
 *
 * @return resource
 */
function service_order_spec_insert($queries, $options = [])
{
    // 操作ログの記録
    service_log_record(null, 'order_specs', 'insert');

    // 規格を登録
    $resource = model('insert_order_specs', $queries, $options);
    if (!$resource) {
        error('データを登録できません。');
    }

    return $resource;
}

/**
 * 規格の編集
 *
 * @param array $queries
 * @param array $options
 *
 * @return resource
 */
function service_order_spec_update($queries, $options = [])
{
    $options = [
        'id'     => isset($options['id'])     ? $options['id']     : null,
        'update' => isset($options['update']) ? $options['update'] : null,
    ];

    // 最終編集日時を確認
    if (isset($options['id']) && isset($options['update']) && (!isset($queries['set']['modified']) || $queries['set']['modified'] !== false)) {
        $order_specs = model('select_order_specs', [
            'where' => [
                'id = :id AND modified > :update',
                [
                    'id'     => $options['id'],
                    'update' => $options['update'],
                ],
            ],
        ]);
        if (!empty($order_specs)) {
            error('編集開始後にデータが更新されています。');
        }
    }

    // 操作ログの記録
    service_log_record(null, 'order_specs', 'update');

    // 規格を編集
    $resource = model('update_order_specs', $queries, $options);
    if (!$resource) {
        error('データを編集できません。');
    }

    return $resource;
}

/**
 * 規格の削除
 *
 * @param array $queries
 * @param array $options
 *
 * @return resource
 */
function service_order_spec_delete($queries, $options = [])
{
    // 操作ログの記録
    service_log_record(null, 'order_specs', 'delete');

    // 規格を削除
    $resource = model('delete_order_specs', $queries, $options);
    if (!$resource) {
        error('データを削除できません。');
    }

    return $resource;
}

/**
 * 規格の並び順を一括変更
 *
 * @param array $data
 *
 * @return void
 */
function service_order_spec_sort($data)
{
    // 並び順を更新
    foreach ($data as $id => $sort) {
        if (!preg_match('/^[\w\-\/]+$/', $id)) {
            continue;
        }
        if (!preg_match('/^\d+$/', $sort)) {
            continue;
        }

        $resource = service_order_spec_update([
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
