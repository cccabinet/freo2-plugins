<?php

// ルールを取得
$_view['rewrite_rules'] = model('select_rewrite_rules', [
    'order_by' => 'sort, id',
]);

// タイトル
$_view['title'] = 'リライト管理';
