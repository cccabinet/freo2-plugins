<?php

// 型を登録するためのソート順を取得
$categories = db_select([
    'select' => 'MAX(sort) AS sort',
    'from'   => DATABASE_PREFIX . 'types',
    'where'  => 'deleted IS NULL',
]);
$category_sort = $categories[0]['sort'];

// 型を登録
$resource = db_insert([
    'insert_into' => DATABASE_PREFIX . 'types',
    'values'      => [
        'created'  => localdate('Y-m-d H:i:s'),
        'modified' => localdate('Y-m-d H:i:s'),
        'code'     => 'gallery',
        'name'     => 'ギャラリー',
        'memo'     => NULL,
        'sort'     => $category_sort + 1,
    ],
]);
if (!$resource) {
    error('プラグイン用SQL [型を登録] を実行できません。');
}
