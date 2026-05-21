<?php

import('plugins/rewrite/app/services/rewrite_rule.php');

// フォワードを確認
if (forward() === null) {
    error('不正なアクセスです。');
}

// 投稿データを確認
if (empty($_SESSION['post'])) {
    // リダイレクト
    redirect('/admin/rewrite_form');
}

// トランザクションを開始
db_transaction();

if (empty($_SESSION['post']['rewrite_rule']['id'])) {
    // ルールを登録
    $resource = service_rewrite_rule_insert([
        'values' => [
            'enabled'  => $_SESSION['post']['rewrite_rule']['enabled'],
            'name'     => $_SESSION['post']['rewrite_rule']['name'],
            'url'      => $_SESSION['post']['rewrite_rule']['url'],
            'rewrited' => $_SESSION['post']['rewrite_rule']['rewrited'],
            'type'     => $_SESSION['post']['rewrite_rule']['type'],
            'memo'     => $_SESSION['post']['rewrite_rule']['memo'],
            'sort'     => $_SESSION['post']['rewrite_rule']['sort'],
        ],
    ]);
    if (!$resource) {
        error('データを登録できません。');
    }
} else {
    // ルールを編集
    $resource = service_rewrite_rule_update([
        'set'   => [
            'enabled'  => $_SESSION['post']['rewrite_rule']['enabled'],
            'name'     => $_SESSION['post']['rewrite_rule']['name'],
            'url'      => $_SESSION['post']['rewrite_rule']['url'],
            'rewrited' => $_SESSION['post']['rewrite_rule']['rewrited'],
            'type'     => $_SESSION['post']['rewrite_rule']['type'],
            'memo'     => $_SESSION['post']['rewrite_rule']['memo'],
        ],
        'where' => [
            'id = :id',
            [
                'id' => $_SESSION['post']['rewrite_rule']['id'],
            ],
        ],
    ], [
        'id'     => intval($_SESSION['post']['rewrite_rule']['id']),
        'update' => $_SESSION['update']['rewrite_rule'],
    ]);
    if (!$resource) {
        error('データを編集できません。');
    }
}

// トランザクションを終了
db_commit();

// 投稿セッションを初期化
unset($_SESSION['post']);
unset($_SESSION['update']);

// リダイレクト
redirect('/admin/rewrite?ok=post');
