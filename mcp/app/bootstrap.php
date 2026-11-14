<?php

// モデルを読み込み
import('plugins/mcp/app/models/mcp_tokens.php');

// メニューを追加
$menu_contents = [];
foreach ($GLOBALS['menu_contents']['admin']['auth'] as $key => $value) {
    $menu_contents[$key] = $value;

    if ($key === 'modify') {
        $menu_contents['mcp_token'] = [
            'name'   => 'MCPトークン管理',
            'link'   => '/admin/mcp_token',
            'active' => '/^mcp_token(_|$)/',
            'icon'   => null,
            'show'   => isset($GLOBALS['authority']) && $GLOBALS['authority']['power'] >= $GLOBALS['plugin']['mcp']['power'],
        ];
    }
}
$GLOBALS['menu_contents']['admin']['auth'] = $menu_contents;
