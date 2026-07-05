<?php

// メニューを追加
$menu_contents = [];
foreach ($GLOBALS['menu_contents']['admin']['contents'] as $key => $value) {
    if ($key === 'menu') {
        $menu_contents['gallery'] = [
            'name'   => 'ギャラリー管理',
            'link'   => '/admin/gallery',
            'active' => '/^gallery(_|$)/',
            'icon'   => '#symbol-list-ul',
            'show'   => true,
        ];
    }
    $menu_contents[$key] = $value;
}
$GLOBALS['menu_contents']['admin']['contents'] = $menu_contents;
