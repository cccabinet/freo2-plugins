<?php

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
