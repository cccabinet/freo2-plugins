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
$insert_position = array_search('page', array_keys($GLOBALS['menu_contents']['admin']['contents']));

// メニューを挿入
array_splice($GLOBALS['menu_contents']['admin']['contents'], $insert_position + 1, 0, [$insert_key => $insert_value]);

// 権限を確認
if (!empty($_SESSION['auth']['user']['id'])) {
    if ($GLOBALS['authority']['power'] < 2) {
        if (preg_match('/^(admin)$/', $_REQUEST['_mode'])) {
            if (preg_match('/^(gallery)(_|$)/', $_REQUEST['_work'])) {
                error('不正なアクセスです。');
            }
        }
    }
}
