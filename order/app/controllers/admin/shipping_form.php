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
        'order_shipping' => model('normalize_order_shippings', [
            'id'            => isset($_POST['id'])            ? $_POST['id']            : '',
            'record_id'     => isset($_POST['record_id'])     ? $_POST['record_id']     : '',
            'delivery_id'   => isset($_POST['delivery_id'])   ? $_POST['delivery_id']   : '',
            'delivery_cost' => isset($_POST['delivery_cost']) ? $_POST['delivery_cost'] : '',
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
            'memo'          => isset($_POST['memo'])          ? $_POST['memo']          : '',
        ]),
    ];

    // 発送する商品を整理
    $order_shipping_items = [];
    if (!empty($_POST['record_item_id']) && is_array($_POST['record_item_id'])) {
        foreach ($_POST['record_item_id'] as $key => $record_item_id) {
            $quantity = intval($_POST['quantity'][$key] ?? 0);
            if ($quantity <= 0) {
                continue;
            }

            $order_shipping_items[] = [
                'record_item_id' => $record_item_id,
                'quantity'       => $quantity,
            ];
        }
    }

    // 入力データを検証＆登録
    $warnings = model('validate_order_shippings', $post['order_shipping']);
    if (isset($_POST['_type']) && $_POST['_type'] === 'json') {
        if (empty($warnings)) {
            ok();
        } else {
            warning($warnings);
        }
    } else {
        if (empty($warnings)) {
            $_SESSION['post']['order_shipping']       = $post['order_shipping'];
            $_SESSION['post']['order_shipping_items'] = $order_shipping_items;

            // フォワード
            forward('plugins/order/app/controllers/admin/shipping_post.php');
        } else {
            $_view['order_shipping'] = $post['order_shipping'];

            $_view['warnings'] = $warnings;
        }
    }
} else {
    // 初期データを取得
    if (empty($_GET['id'])) {
        $_view['order_shipping'] = model('default_order_shippings');
        $_view['order_shipping']['record_id'] = $_GET['record_id'] ?? '';
    } else {
        $order_shippings = model('select_order_shippings', [
            'where' => [
                'id = :id',
                [
                    'id' => $_GET['id'],
                ],
            ],
        ]);
        if (empty($order_shippings)) {
            warning('編集データが見つかりません。');
        } else {
            $_view['order_shipping'] = $order_shippings[0];
        }
    }

    $order_shipping_items = [];

    // 投稿セッションを初期化
    unset($_SESSION['post']);

    // 編集開始日時を記録
    if (!empty($_GET['id'])) {
        $_SESSION['update']['order_shipping'] = localdate('Y-m-d H:i:s');
    }
}

// 注文記録を取得
$order_records = model('select_order_records', [
    'where' => [
        'id = :id',
        [
            'id' => $_view['order_shipping']['record_id'],
        ],
    ],
]);
if (empty($order_records)) {
    error('対象の注文が見つかりません。');
}
$_view['order_record'] = $order_records[0];

// 新規登録時は、注文のお届け先を初期値にする
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && empty($_GET['id'])) {
    foreach (['email', 'name_01', 'name_02', 'kana_01', 'kana_02', 'zipcode', 'prefecture', 'address_01', 'address_02', 'telephone'] as $key) {
        $_view['order_shipping'][$key] = $order_records[0][$key];
    }
}

// 配送方法を取得
$_view['order_deliveries'] = model('select_order_deliveries', [
    'order_by' => 'id',
]);

// 注文明細を取得
$order_record_items = model('select_order_record_items', [
    'where' => [
        'record_id = :record_id',
        [
            'record_id' => $_view['order_record']['id'],
        ],
    ],
    'order_by' => 'id',
]);

// 規格を取得
$order_spec_sets = [];
if (!empty($order_record_items)) {
    $order_specs = model('select_order_specs', [
        'where' => 'order_specs.id IN(' . implode(',', array_map('db_escape', array_column($order_record_items, 'spec_id'))) . ')',
    ], [
        'associate' => true,
    ]);
    foreach ($order_specs as $order_spec) {
        $order_spec_sets[$order_spec['id']] = $order_spec;
    }
}

// 他の発送記録での発送済み数を取得（この発送記録自身と、配送失敗・返送になった発送記録は除く）
$shipped_quantities = [];
if (!empty($order_record_items)) {
    $excluded_ids   = model('select_unsuccessful_order_shippings', 'record_id = ' . intval($_view['order_record']['id']));
    $excluded_where = !empty($excluded_ids) ? ' AND shipping_id NOT IN(' . implode(',', $excluded_ids) . ')' : '';

    $shipped_items = model('select_order_shipping_items', [
        'select'   => 'record_item_id, SUM(quantity) AS quantity',
        'where'    => 'record_item_id IN(' . implode(',', array_map('db_escape', array_column($order_record_items, 'id'))) . ') AND shipping_id != ' . intval($_view['order_shipping']['id'] ?: 0) . $excluded_where,
        'group_by' => 'record_item_id',
    ]);
    foreach ($shipped_items as $shipped_item) {
        $shipped_quantities[$shipped_item['record_item_id']] = $shipped_item['quantity'];
    }
}

// この画面で入力済みの数量を取得（バリデーションエラー時は入力値、編集時は登録済みの値）
$input_quantities = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($order_shipping_items as $item) {
        $input_quantities[$item['record_item_id']] = $item['quantity'];
    }
} elseif (!empty($_GET['id'])) {
    $current_items = model('select_order_shipping_items', [
        'where' => [
            'shipping_id = :shipping_id',
            [
                'shipping_id' => $_GET['id'],
            ],
        ],
    ]);
    foreach ($current_items as $item) {
        $input_quantities[$item['record_item_id']] = $item['quantity'];
    }
}

// 明細ごとの発送可能数を組み立て
$_view['order_record_items'] = [];
foreach ($order_record_items as $order_record_item) {
    $shipped                                = $shipped_quantities[$order_record_item['id']] ?? 0;
    $order_record_item['entry_title']       = $order_spec_sets[$order_record_item['spec_id']]['entry_title'] ?? null;
    $order_record_item['spec_name']         = $order_spec_sets[$order_record_item['spec_id']]['name'] ?? null;
    $order_record_item['remained_quantity'] = $order_record_item['quantity'] - $shipped;
    $order_record_item['input_quantity']    = $input_quantities[$order_record_item['id']] ?? 0;

    $_view['order_record_items'][] = $order_record_item;
}

// タイトル
if (empty($_GET['id'])) {
    $_view['title'] = '発送登録';
} else {
    $_view['title'] = '発送編集';
}
