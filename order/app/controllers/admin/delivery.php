<?php

// 配送方法を取得
$_view['order_deliveries'] = model('select_order_deliveries', [
    'order_by' => 'id',
]);

// タイトル
$_view['title'] = '配送方法管理';
