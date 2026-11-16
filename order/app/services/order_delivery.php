<?php

import('app/services/log.php');

/**
 * 送料の計算
 *
 * 配送方法の送料計算（注文ごと・商品の数ごと）で計算し、商品合計金額が閾値以上なら値引き後の送料にする
 *
 * @param array|null $order_delivery 配送方法（配送しない注文なら null）
 * @param int        $subtotal       商品合計金額
 * @param int        $quantity       商品の数の合計
 *
 * @return int
 */
function service_order_delivery_cost($order_delivery, $subtotal, $quantity)
{
    if ($order_delivery === null) {
        return 0;
    }

    if ($order_delivery['calculate'] === 'product') {
        $delivery_cost = $order_delivery['cost'] * $quantity;
    } else {
        $delivery_cost = $order_delivery['cost'];
    }
    if ($order_delivery['threshold'] !== null && $subtotal >= $order_delivery['threshold']) {
        $delivery_cost = $order_delivery['discounted'];
    }

    // TODO: 都道府県ごとの上乗せ送料（surcharge）は未対応

    return $delivery_cost;
}

/**
 * 配送方法の登録
 *
 * @param array $queries
 * @param array $options
 *
 * @return resource
 */
function service_order_delivery_insert($queries, $options = [])
{
    // 操作ログの記録
    service_log_record(null, 'order_deliveries', 'insert');

    // 配送方法を登録
    $resource = model('insert_order_deliveries', $queries, $options);
    if (!$resource) {
        error('データを登録できません。');
    }

    return $resource;
}

/**
 * 配送方法の編集
 *
 * @param array $queries
 * @param array $options
 *
 * @return resource
 */
function service_order_delivery_update($queries, $options = [])
{
    $options = [
        'id'     => isset($options['id'])     ? $options['id']     : null,
        'update' => isset($options['update']) ? $options['update'] : null,
    ];

    // 最終編集日時を確認
    if (isset($options['id']) && isset($options['update']) && (!isset($queries['set']['modified']) || $queries['set']['modified'] !== false)) {
        $order_deliveries = model('select_order_deliveries', [
            'where' => [
                'id = :id AND modified > :update',
                [
                    'id'     => $options['id'],
                    'update' => $options['update'],
                ],
            ],
        ]);
        if (!empty($order_deliveries)) {
            error('編集開始後にデータが更新されています。');
        }
    }

    // 操作ログの記録
    service_log_record(null, 'order_deliveries', 'update');

    // 配送方法を編集
    $resource = model('update_order_deliveries', $queries, $options);
    if (!$resource) {
        error('データを編集できません。');
    }

    return $resource;
}

/**
 * 配送方法の削除
 *
 * @param array $queries
 * @param array $options
 *
 * @return resource
 */
function service_order_delivery_delete($queries, $options = [])
{
    // 操作ログの記録
    service_log_record(null, 'order_deliveries', 'delete');

    // 配送方法を削除
    $resource = model('delete_order_deliveries', $queries, $options);
    if (!$resource) {
        error('データを削除できません。');
    }

    return $resource;
}
