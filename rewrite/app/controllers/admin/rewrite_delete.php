<?php

import('plugins/rewrite/app/services/rewrite_rule.php');

// ワンタイムトークン
if (!token('check')) {
    error('不正な操作が検出されました。送信内容を確認して再度実行してください。');
}

// アクセス元
if (empty($_SERVER['HTTP_REFERER']) || !preg_match('/^' . preg_quote($GLOBALS['config']['http_url'], '/') . '/', $_SERVER['HTTP_REFERER'])) {
    error('不正なアクセスです。');
}

if (!empty($_POST['id'])) {
    // トランザクションを開始
    db_transaction();

    // ルールを削除
    $resource = service_rewrite_rule_delete([
        'where' => [
            'rewrite_rules.id = :id',
            [
                'id' => $_POST['id'],
            ],
        ],
    ]);
    if (!$resource) {
        error('データを削除できません。');
    }

    // トランザクションを終了
    db_commit();

    // リダイレクト
    redirect('/admin/rewrite?ok=delete');
} else {
    // リダイレクト
    redirect('/admin/rewrite?warning=delete');
}
