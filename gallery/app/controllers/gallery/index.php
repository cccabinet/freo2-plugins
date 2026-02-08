<?php

import('app/services/entry.php');

// エントリーを取得
$entries = service_entry_select_published('gallery', [
    'order_by' => 'entries.code',
]);
$_view['entry_sets'] = [];
foreach ($entries as $entry) {
    $category_id = 0;
    if (isset($entry['category_sets'][0]['category_id'])) {
        $category_id = $entry['category_sets'][0]['category_id'];
    }

    $_view['entry_sets'][$category_id][] = $entry;
}

// カテゴリーを取得
$_view['categories'] = model('select_categories', [
    'where'    => 'types.code = ' .  db_escape('gallery'),
    'order_by' => 'categories.sort, categories.id',
], [
    'associate' => true,
]);
array_unshift($_view['categories'], [
    'id' => 0,
]);

// タイトル
$_view['title'] = 'ギャラリー';
