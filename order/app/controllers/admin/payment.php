<?php

// 支払方法を取得
$_view['order_payments'] = model('select_order_payments', [
    'order_by' => 'id',
]);

// タイトル
$_view['title'] = '支払方法管理';
