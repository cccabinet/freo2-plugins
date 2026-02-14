<?php

// 型IDを取得
$types = db_select([
    'select' => 'id',
    'from'   => DATABASE_PREFIX . 'types',
    'where'  => 'deleted IS NULL AND code = \'gallery\'',
]);
$type_id = $types[0]['id'];

// 削除するデータのIDを取得
$entries = db_select([
    'select' => 'id',
    'from'   => DATABASE_PREFIX . 'entries',
    'where'  => [
        'deleted IS NULL AND type_id = :type_id',
        [
            'type_id' => $type_id,
        ],
    ],
]);

$ids = [];
foreach ($entries as $entry) {
    $ids[] = intval($entry['id']);
}

// フィールドセットを削除
if (!empty($ids)) {
    $resource = db_delete([
        'delete_from' => DATABASE_PREFIX . 'field_sets',
        'where'       => 'entry_id IN(' . implode(',', array_map('db_escape', $ids)) . ')',
    ]);
    if (!$resource) {
        error('プラグイン用SQL [フィールドセットを削除] を実行できません。');
    }
}

// カテゴリーセットを削除
if (!empty($ids)) {
    $resource = db_delete([
        'delete_from' => DATABASE_PREFIX . 'category_sets',
        'where'       => 'entry_id IN(' . implode(',', array_map('db_escape', $ids)) . ')',
    ]);
    if (!$resource) {
        error('プラグイン用SQL [カテゴリーセットを削除] を実行できません。');
    }
}

// エントリーを削除
$resource = db_update([
    'update' => DATABASE_PREFIX . 'entries',
    'set'    => [
        'deleted' => localdate('Y-m-d H:i:s'),
    ],
    'where'  => [
        'deleted IS NULL AND type_id = :type_id',
        [
            'type_id' => $type_id,
        ],
    ],
]);
if (!$resource) {
    error('プラグイン用SQL [エントリーを削除] を実行できません。');
}

// フィールドを削除
$resource = db_update([
    'update' => DATABASE_PREFIX . 'fields',
    'set'    => [
        'deleted' => localdate('Y-m-d H:i:s'),
    ],
    'where'  => [
        'deleted IS NULL AND type_id = :type_id',
        [
            'type_id' => $type_id,
        ],
    ],
]);
if (!$resource) {
    error('プラグイン用SQL [フィールドを削除] を実行できません。');
}

// カテゴリーを削除
$resource = db_update([
    'update' => DATABASE_PREFIX . 'categories',
    'set'    => [
        'deleted' => localdate('Y-m-d H:i:s'),
        'code'    => ['CONCAT(\'DELETED ' . localdate('YmdHis') . ' \', code)'],
    ],
    'where'  => [
        'deleted IS NULL AND type_id = :type_id',
        [
            'type_id' => $type_id,
        ],
    ],
]);
if (!$resource) {
    error('プラグイン用SQL [カテゴリーを削除] を実行できません。');
}

// 型を削除
$resource = db_update([
    'update' => DATABASE_PREFIX . 'types',
    'set'    => [
        'deleted' => localdate('Y-m-d H:i:s'),
        'code'    => ['CONCAT(\'DELETED ' . localdate('YmdHis') . ' \', code)'],
    ],
    'where'  => [
        'deleted IS NULL AND id = :type_id',
        [
            'type_id' => $type_id,
        ],
    ],
]);
if (!$resource) {
    error('プラグイン用SQL [型を削除] を実行できません。');
}
