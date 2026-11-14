<?php

// MCP トークンの画面は、トークンを使える権限のユーザーだけ
if ($_REQUEST['_mode'] === 'admin' && preg_match('/^mcp_token(_|$)/', $_REQUEST['_work'])) {
    if (!isset($GLOBALS['authority']) || $GLOBALS['authority']['power'] < $GLOBALS['plugin']['mcp']['power']) {
        error('不正なアクセスです。');
    }
}
