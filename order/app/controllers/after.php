<?php

// 管理画面のダッシュボードに情報を表示
if ($_REQUEST['_mode'] === 'admin' && $_REQUEST['_work'] === 'index') {
    // 商品数を取得
    $entry_count = model('select_entries', [
        'select' => 'COUNT(DISTINCT entries.id) AS count',
        'where'  => 'types.code = ' . db_escape('catalog'),
    ], [
        'associate' => true,
    ]);
    $entry_count = $entry_count[0]['count'];

    // 商品数（未承認）を取得
    $entry_not_approved_count = model('select_entries', [
        'select' => 'COUNT(DISTINCT entries.id) AS count',
        'where'  => 'types.code = ' . db_escape('catalog') . ' AND entries.approved = 0',
    ], [
        'associate' => true,
    ]);
    $entry_not_approved_count = $entry_not_approved_count[0]['count'];

    // 商品数（非公開）を取得
    $entry_public_none_count = model('select_entries', [
        'select' => 'COUNT(DISTINCT entries.id) AS count',
        'where'  => 'types.code = ' . db_escape('catalog') . ' AND entries.public = ' . db_escape('none'),
    ], [
        'associate' => true,
    ]);
    $entry_public_none_count = $entry_public_none_count[0]['count'];

    // 表示用コンテンツを作成
    $entry_content  = '<span class="fs-4"><a href="' . t(MAIN_FILE, true) . '/admin/catalog">' . h($entry_count, true) . '</a></span>';
    $entry_content .= ($GLOBALS['plugin']['order']['setting']['use_approve'] ? ' / 未承認 ' . h($entry_not_approved_count, true) : ' / 非公開 ' . h($entry_public_none_count, true));

    // 注文数を取得
    $order_record_count = model('select_order_records', [
        'select' => 'COUNT(id) AS count',
    ]);
    $order_record_count = $order_record_count[0]['count'];

    // 注文数（未完了）を取得
    $order_record_incomplete_count = model('select_order_records', [
        'select' => 'COUNT(id) AS count',
        'where'  => 'status != ' . db_escape('incomplete'),
    ]);
    $order_record_incomplete_count = $order_record_incomplete_count[0]['count'];

    // 表示用コンテンツを作成
    $order_content  = '<span class="fs-4"><a href="' . t(MAIN_FILE, true) . '/admin/order">' . h($order_record_count, true) . '</a></span>';
    $order_content .= ' / 未完了 ' . h($order_record_incomplete_count, true);

    // ダッシュボードに追加
    $_view['widget_sets']['admin_ready'] .= <<< EOM
<script>
// ボックスを定義
var html = `
    <div class="col-md-3">
        <div class="card shadow-sm mb-3">
            <div class="card-header">商品数</div>
            <div class="card-body text-center">
                {$entry_content}
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm mb-3">
            <div class="card-header">注文数</div>
            <div class="card-body text-center">
                {$order_content}
            </div>
        </div>
    </div>
`;

// コンテンツの最後に追加
$('h3:contains("コンテンツ")')
    .next('.row')
    .append(html);
</script>
EOM;
}
