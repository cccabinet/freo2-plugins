<?php

// モデルを読み込み
import('plugins/order/app/models/order_stocks.php');
import('plugins/order/app/models/order_specs.php');
import('plugins/order/app/models/order_products.php');
import('plugins/order/app/models/order_payments.php');
import('plugins/order/app/models/order_deliveries.php');
import('plugins/order/app/models/order_addresses.php');
import('plugins/order/app/models/order_records.php');
import('plugins/order/app/models/order_record_items.php');
import('plugins/order/app/models/order_shippings.php');
import('plugins/order/app/models/order_shipping_items.php');

// メニューを追加
$menu_contents = [];
foreach ($GLOBALS['menu_contents']['admin']['contents'] as $key => $value) {
    if ($key === 'menu') {
        $menu_contents['catalog'] = [
            'name'   => '商品管理',
            'link'   => '/admin/catalog',
            'active' => '/^(catalog|stock|spec|product)(_|$)/',
            'icon'   => '#symbol-list-ul',
            'show'   => true,
        ];
        $menu_contents['order'] = [
            'name'   => '注文管理',
            'link'   => '/admin/order',
            'active' => '/^(order|payment|delivery|shipping)(_|$)/',
            'icon'   => '#symbol-list-ul',
            'show'   => true,
        ];
    }
    $menu_contents[$key] = $value;
}
$GLOBALS['menu_contents']['admin']['contents'] = $menu_contents;

$menu_contents = [];
foreach ($GLOBALS['menu_contents']['admin']['auth'] as $key => $value) {
    if ($key === 'modify') {
        $menu_contents['history'] = [
            'name'   => '注文履歴',
            'link'   => '/auth/history',
            'active' => null,
            'icon'   => null,
            'show'   => true,
        ];
        $menu_contents['address'] = [
            'name'   => '住所管理',
            'link'   => '/auth/address',
            'active' => null,
            'icon'   => null,
            'show'   => true,
        ];
    }
    $menu_contents[$key] = $value;
}
$GLOBALS['menu_contents']['admin']['auth'] = $menu_contents;

$menu_contents = [];
foreach ($GLOBALS['menu_contents']['auth']['home'] as $key => $value) {
    if ($key === 'modify') {
        $menu_contents['history'] = [
            'name'   => '注文履歴',
            'link'   => '/auth/history',
            'active' => '/^history$/',
            'icon'   => null,
            'show'   => true,
        ];
        $menu_contents['address'] = [
            'name'   => '住所管理',
            'link'   => '/auth/address',
            'active' => '/^address$/',
            'icon'   => null,
            'show'   => true,
        ];
    }
    $menu_contents[$key] = $value;
}
$GLOBALS['menu_contents']['auth']['home'] = $menu_contents;
