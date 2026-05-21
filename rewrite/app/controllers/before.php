<?php

// メニューを定義
$insert_key = 'rewrite';
$insert_value = [
    'name'   => 'リライト管理',
    'link'   => '/admin/rewrite',
    'active' => '/^rewrite(_|$)/',
    'icon'   => '#symbol-list-ul',
    'show'   => true,
];

// メニューを挿入
$menu_contents = [];
foreach ($GLOBALS['menu_contents']['admin']['contents'] as $key => $value) {
    if ($key === 'widget') {
        $menu_contents[$insert_key] = $insert_value;
    }
    $menu_contents[$key] = $value;
}
$GLOBALS['menu_contents']['admin']['contents'] = $menu_contents;

// 権限を確認
if (!empty($_SESSION['auth']['user']['id'])) {
    if ($GLOBALS['authority']['power'] < 2) {
        if (preg_match('/^(admin)$/', $_REQUEST['_mode'])) {
            if (preg_match('/^(rewrite)(_|$)/', $_REQUEST['_work'])) {
                error('不正なアクセスです。');
            }
        }
    }
}

// 現在のURLを取得
$url = '/';
if (!empty($_params)) {
    $url .= implode('/', $_params);
}
if (!empty($_SERVER['QUERY_STRING'])) {
    $url .= '?' . $_SERVER['QUERY_STRING'];
}

// ルールを取得
$rewrite_rules = model('select_rewrite_rules', [
    'where'    => 'enabled = 1',
    'order_by' => 'sort, id',
]);

// リライトを実行
foreach ($rewrite_rules as $rewrite_rule) {
    if (preg_match('/^' . preg_quote($rewrite_rule['url'], '/') . '/', $url)) {
        $url = preg_replace('/^' . preg_quote($rewrite_rule['url'], '/') . '/', $rewrite_rule['rewrited'], $url);

        if ($rewrite_rule['type'] === 'rewrite') {
            $_params = explode('/', $url);
            array_shift($_params);

            if (empty($_params[0])) {
                $_REQUEST['_mode'] = MAIN_DEFAULT_MODE;
            } else {
                $_REQUEST['_mode'] = $_params[0];
            }
            if (empty($_params[1])) {
                $_REQUEST['_work'] = MAIN_DEFAULT_WORK;
            } else {
                $_REQUEST['_work'] = $_params[1];
            }
        } elseif ($rewrite_rule['type'] === 'redirect') {
            redirect($url);
        }

        break;
    }
}
