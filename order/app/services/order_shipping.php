<?php

import('app/services/log.php');

/**
 * 注文明細ごとの発送済み数を取得
 *
 * 配送失敗・返送になった発送記録は「発送済み」に含めない
 *
 * @param int      $record_id
 * @param array    $record_item_ids
 * @param int|null $exclude_shipping_id 集計から除く発送記録（編集中の発送記録など）
 *
 * @return array 注文明細のIDをキー、発送済み数を値とする配列
 */
function service_order_shipping_shipped_quantities($record_id, $record_item_ids, $exclude_shipping_id = null)
{
    if (empty($record_item_ids)) {
        return [];
    }

    $where = 'record_item_id IN(' . implode(',', array_map('db_escape', $record_item_ids)) . ')';

    // 配送失敗・返送になった発送記録を除く
    $excluded_ids = model('select_unsuccessful_order_shippings', 'record_id = ' . intval($record_id));
    if ($exclude_shipping_id) {
        $excluded_ids[] = intval($exclude_shipping_id);
    }
    if (!empty($excluded_ids)) {
        $where .= ' AND shipping_id NOT IN(' . implode(',', $excluded_ids) . ')';
    }

    $shipped_items = model('select_order_shipping_items', [
        'select'   => 'record_item_id, SUM(quantity) AS quantity',
        'where'    => $where,
        'group_by' => 'record_item_id',
    ]);

    $shipped_quantities = [];
    foreach ($shipped_items as $shipped_item) {
        $shipped_quantities[$shipped_item['record_item_id']] = intval($shipped_item['quantity']);
    }

    return $shipped_quantities;
}

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
