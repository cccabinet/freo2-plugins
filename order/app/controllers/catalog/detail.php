<?php

import('app/services/entry.php');
import('app/services/comment.php');
import('libs/modules/recaptcha.php');

// 表示対象を取得
if (isset($_params[2])) {
    $_GET['code'] = $_params[2];
}
if (!isset($_GET['code'])) {
    error('不正なアクセスです。');
}

// 商品を取得
$entries = service_entry_select_published('catalog', [
    'where' => [
        'entries.code = :code',
        [
            'code' => $_GET['code'],
        ],
    ],
]);
if (empty($entries)) {
    warning('商品が見つかりません。');
} else {
    $_view['entry'] = $entries[0];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ワンタイムトークン
    if (!token('check')) {
        error('不正なアクセスです。');
    }

    if (isset($_POST['exec']) && $_POST['exec'] === 'password') {
        // パスワード認証
        if (service_entry_password($entries[0], isset($_POST['password']) ? $_POST['password'] : '')) {
            // リダイレクト
            redirect(service_entry_url($entries[0]));
        } else {
            warning('パスワードが違います。');
        }
    } elseif (isset($_POST['exec']) && $_POST['exec'] === 'comment') {
        // コメントの受付を確認
        if (!service_comment_acceptable($entries[0])) {
            error('不正なアクセスです。');
        }

        // reCAPTCHA
        if (!service_comment_recaptcha()) {
            error('reCAPTCHAでの認証に失敗しました。');
        }

        // 入力データを整理
        $post = [
            'comment' => service_comment_normalize($_POST, $entries[0]),
        ];

        // 入力データを検証＆登録
        $warnings = model('validate_comments', $post['comment']);
        if (isset($_POST['_type']) && $_POST['_type'] === 'json') {
            if (empty($warnings)) {
                ok();
            } else {
                warning($warnings);
            }
        } else {
            if (empty($warnings)) {
                $_SESSION['post']['comment'] = $post['comment'];

                // リダイレクト
                redirect('/comment/preview');
            } else {
                $_view['comment'] = $post['comment'];

                $_view['warnings'] = $warnings;
            }
        }
    }
} elseif (isset($_GET['referer']) && $_GET['referer'] === 'preview') {
    // 入力データを復元
    $_view['comment'] = $_SESSION['post']['comment'];
} else {
    // 初期データを取得
    $_view['comment'] = service_comment_default();
}

// フィールドを取得
$_view['fields'] = model('select_fields', [
    'where'    => 'type_id = ' . intval($entries[0]['type_id']),
    'order_by' => 'sort, id',
]);

// コメントを取得
$_view['comments'] = service_comment_select_approved($entries[0]['id']);

// 規格を取得（対面販売は管理画面からのみ受注登録できるため除外）
$_view['order_specs'] = model('select_order_specs', [
    'where' => [
        'order_specs.entry_id = :entry_id AND order_specs.enabled = 1 AND order_specs.provide != :provide',
        [
            'entry_id' => $entries[0]['id'],
            'provide'  => 'direct',
        ],
    ],
    'order_by' => 'order_specs.sort, order_specs.id',
], [
    'associate' => true,
]);

// 規格ごとの在庫切れを確認
$_view['order_spec_out_of_stocks'] = [];
if ($GLOBALS['plugin']['order']['setting']['use_stock'] && !empty($_view['order_specs'])) {
    $order_products = model('select_order_products', [
        'where' => 'spec_id IN(' . implode(',', array_map('db_escape', array_column($_view['order_specs'], 'id'))) . ')',
    ], [
        'associate' => true,
    ]);
    foreach ($order_products as $order_product) {
        if ($order_product['order_stock_quantity'] !== null && $order_product['order_stock_quantity'] <= 0) {
            $_view['order_spec_out_of_stocks'][$order_product['spec_id']] = true;
        }
    }
}

// タイトル
if (isset($_view['entry']['title'])) {
    $_view['title'] = $_view['entry']['title'];
} else {
    $_view['title'] = 'エントリー';
}
