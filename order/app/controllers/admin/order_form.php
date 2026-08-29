<?php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ワンタイムトークン
    if (!token('check')) {
        error('不正な操作が検出されました。送信内容を確認して再度実行してください。');
    }

    // アクセス元
    if (empty($_SERVER['HTTP_REFERER']) || !preg_match('/^' . preg_quote($GLOBALS['config']['http_url'], '/') . '/', $_SERVER['HTTP_REFERER'])) {
        error('不正なアクセスです。');
    }

    // 入力データを整理
    $post = [
        'order_record' => model('normalize_order_records', [
            'id'            => isset($_POST['id'])            ? $_POST['id']            : '',
            'provide'       => isset($_POST['provide'])       ? $_POST['provide']       : '',
            'payment_id'    => isset($_POST['payment_id'])    ? $_POST['payment_id']    : '',
            'payment_fee'   => isset($_POST['payment_fee'])   ? $_POST['payment_fee']   : '',
            'delivery_id'   => isset($_POST['delivery_id'])   ? $_POST['delivery_id']   : '',
            'delivery_cost' => isset($_POST['delivery_cost']) ? $_POST['delivery_cost'] : '',
            'discount'      => isset($_POST['discount'])      ? $_POST['discount']      : '',
            'shipping_date' => isset($_POST['shipping_date']) ? $_POST['shipping_date'] : '',
            'status'        => isset($_POST['status'])        ? $_POST['status']        : '',
            'email'         => isset($_POST['email'])         ? $_POST['email']         : '',
            'name_01'       => isset($_POST['name_01'])       ? $_POST['name_01']       : '',
            'name_02'       => isset($_POST['name_02'])       ? $_POST['name_02']       : '',
            'kana_01'       => isset($_POST['kana_01'])       ? $_POST['kana_01']       : '',
            'kana_02'       => isset($_POST['kana_02'])       ? $_POST['kana_02']       : '',
            'zipcode'       => isset($_POST['zipcode'])       ? $_POST['zipcode']       : '',
            'prefecture'    => isset($_POST['prefecture'])    ? $_POST['prefecture']    : '',
            'address_01'    => isset($_POST['address_01'])    ? $_POST['address_01']    : '',
            'address_02'    => isset($_POST['address_02'])    ? $_POST['address_02']    : '',
            'telephone'     => isset($_POST['telephone'])     ? $_POST['telephone']     : '',
            'message'       => isset($_POST['message'])       ? $_POST['message']       : '',
            'memo'          => isset($_POST['memo'])          ? $_POST['memo']          : '',
        ]),
    ];

    // 入力データを検証＆登録
    $warnings = model('validate_order_records', $post['order_record']);
    if (isset($_POST['_type']) && $_POST['_type'] === 'json') {
        if (empty($warnings)) {
            ok();
        } else {
            warning($warnings);
        }
    } else {
        if (empty($warnings)) {
            $_SESSION['post']['order_record'] = $post['order_record'];

            // 商品を整理（新規登録のときのみ）
            if (empty($post['order_record']['id'])) {
                $order_record_items = [];
                if (!empty($_POST['spec_id']) && is_array($_POST['spec_id'])) {
                    foreach ($_POST['spec_id'] as $key => $spec_id) {
                        if (empty($spec_id)) {
                            continue;
                        }

                        if ($spec_id === '__custom__') {
                            // 直接入力
                            $name = trim($_POST['item_name'][$key] ?? '');
                            if ($name === '') {
                                continue;
                            }

                            $order_record_items[] = [
                                'order_spec_id' => null,
                                'name'          => $name,
                                'selling_price' => max(0, intval($_POST['item_price'][$key] ?? 0)),
                                'quantity'      => max(1, intval($_POST['quantity'][$key] ?? 1)),
                            ];
                            continue;
                        }

                        $order_record_items[] = [
                            'order_spec_id' => $spec_id,
                            'quantity'      => max(1, intval($_POST['quantity'][$key] ?? 1)),
                        ];
                    }
                }
                $_SESSION['post']['order_record_items'] = $order_record_items;
            }

            // フォワード
            forward('plugins/order/app/controllers/admin/order_post.php');
        } else {
            $_view['order_record'] = $post['order_record'];

            $_view['warnings'] = $warnings;
        }
    }
} else {
    // 初期データを取得
    if (empty($_GET['id'])) {
        $_view['order_record'] = model('default_order_records');

        if (($_GET['provide'] ?? null) === 'direct') {
            // 対面販売用の初期値
            $_view['order_record']['provide'] = 'direct';
        }
    } else {
        $order_records = model('select_order_records', [
            'where' => [
                'id = :id',
                [
                    'id' => $_GET['id'],
                ],
            ],
        ]);
        if (empty($order_records)) {
            warning('編集データが見つかりません。');
        } else {
            $_view['order_record'] = $order_records[0];
        }
    }

    // 投稿セッションを初期化
    unset($_SESSION['post']);

    // 編集開始日時を記録
    if (!empty($_GET['id'])) {
        $_SESSION['update']['order_record'] = localdate('Y-m-d H:i:s');
    }
}

// 支払方法を取得
$_view['order_payments'] = model('select_order_payments', [
    'order_by' => 'id',
]);

// 配送方法を取得
$_view['order_deliveries'] = model('select_order_deliveries', [
    'order_by' => 'id',
]);

// 規格を取得（新規登録の商品選択用）
$_view['order_specs']             = [];
$_view['order_spec_out_of_stocks'] = [];
if (empty($_GET['id'])) {
    $order_specs_where = 'order_specs.enabled = 1';
    if (($_GET['provide'] ?? null) === 'direct') {
        // 対面販売では、対面販売できる規格のみを選択肢にする
        $order_specs_where .= ' AND order_specs.provide = ' . db_escape('direct');
    }

    $_view['order_specs'] = model('select_order_specs', [
        'where'    => $order_specs_where,
        'order_by' => 'entry_title, order_specs.sort, order_specs.id',
    ], [
        'associate' => true,
    ]);

    // 規格ごとの在庫切れを確認
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
}

// 注文明細を取得
$_view['order_record_items'] = [];
$_view['order_spec_sets']    = [];
if (!empty($_GET['id'])) {
    $_view['order_record_items'] = model('select_order_record_items', [
        'where' => [
            'record_id = :record_id',
            [
                'record_id' => $_GET['id'],
            ],
        ],
        'order_by' => 'id',
    ]);

    if (!empty($_view['order_record_items'])) {
        $order_specs = model('select_order_specs', [
            'where'     => 'order_specs.id IN(' . implode(',', array_map('db_escape', array_column($_view['order_record_items'], 'spec_id'))) . ')',
            'order_by'  => 'order_specs.id',
        ], [
            'associate' => true,
        ]);
        foreach ($order_specs as $order_spec) {
            $_view['order_spec_sets'][$order_spec['id']] = $order_spec;
        }
    }
}

// ダウンロード案内を取得（ダウンロード販売の場合のみ）
$_view['order_downloads'] = [];
if (!empty($_GET['id']) && ($_view['order_record']['provide'] ?? null) === 'download' && !empty($_view['order_record_items'])) {
    $order_products = model('select_order_products', [
        'where' => 'spec_id IN(' . implode(',', array_map('db_escape', array_column($_view['order_record_items'], 'spec_id'))) . ')',
    ], [
        'associate' => true,
    ]);
    foreach ($order_products as $order_product) {
        if (!empty($order_product['order_stock_download'])) {
            $_view['order_downloads'][] = [
                'stock_name' => $order_product['order_stock_name'],
                'download'   => $order_product['order_stock_download'],
            ];
        }
    }
}

// ダウンロード案内をテキストにまとめる
$_view['order_download_text'] = '';
if (!empty($_view['order_downloads'])) {
    $texts = [];
    foreach ($_view['order_downloads'] as $order_download) {
        $texts[] = '【' . $order_download['stock_name'] . '】' . "\n" . $order_download['download'];
    }
    $_view['order_download_text'] = implode("\n\n", $texts);
}

// タイトル
if (empty($_GET['id'])) {
    $_view['title'] = '注文登録';
} else {
    $_view['title'] = '注文編集';
}
