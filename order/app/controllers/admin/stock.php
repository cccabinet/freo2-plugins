<?php

// 在庫を取得
$_view['order_stocks'] = model('select_order_stocks', [
    'order_by' => 'code, id',
]);

// タイトル
$_view['title'] = '在庫管理';
