<?php

// エントリーを取得
$_view['entries'] = model('select_entries', [
    'where'    => 'types.code = ' . db_escape('gallery'),
    'order_by' => 'entries.sort DESC, entries.code, entries.id',
], [
    'associate' => true,
]);

// カテゴリーを取得
$_view['categories'] = model('select_categories', [
    'where'    => 'types.code = ' .  db_escape('gallery'),
    'order_by' => 'categories.sort, categories.id',
], [
    'associate' => true,
]);

// タイトル
$_view['title'] = 'ギャラリー管理';
