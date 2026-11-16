<?php

// MCPトークンの画面は、トークンを使える権限のユーザーだけ（/auth/ は会員もログインして開けるため、ここで止める）
if ($_REQUEST['_mode'] === 'auth' && preg_match('/^mcp_token(_|$)/', $_REQUEST['_work'])) {
    if (!isset($GLOBALS['authority']) || $GLOBALS['authority']['power'] < $GLOBALS['plugin']['mcp']['power']) {
        error('不正なアクセスです。');
    }
}
