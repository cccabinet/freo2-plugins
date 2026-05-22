<?php

// メニューを定義
$insert_key = 'gallery';
$insert_value = [
    'name'   => 'ギャラリー管理',
    'link'   => '/admin/gallery',
    'active' => '/^gallery(_|$)/',
    'icon'   => '#symbol-list-ul',
    'show'   => true,
];

// メニューを挿入
$menu_contents = [];
foreach ($GLOBALS['menu_contents']['admin']['contents'] as $key => $value) {
    if ($key === 'menu') {
        $menu_contents[$insert_key] = $insert_value;
    }
    $menu_contents[$key] = $value;
}
$GLOBALS['menu_contents']['admin']['contents'] = $menu_contents;
