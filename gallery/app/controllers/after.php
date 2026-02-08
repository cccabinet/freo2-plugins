<?php

// 管理画面のダッシュボードに情報を表示
if ($_REQUEST['_mode'] === 'admin' && $_REQUEST['_work'] === 'index') {
    // ギャラリー数を取得
    $count = model('select_entries', [
        'select' => 'COUNT(DISTINCT entries.id) AS count',
        'where'  => 'types.code = ' . db_escape('gallery'),
    ], [
        'associate' => true,
    ]);
    $count = $count[0]['count'];

    // ギャラリー数（未承認）を取得
    $not_approved_count = model('select_entries', [
        'select' => 'COUNT(DISTINCT entries.id) AS count',
        'where'  => 'types.code = ' . db_escape('gallery') . ' AND entries.approved = 0',
    ], [
        'associate' => true,
    ]);
    $not_approved_count = $not_approved_count[0]['count'];

    // ギャラリー数（非公開）を取得
    $public_none_count = model('select_entries', [
        'select' => 'COUNT(DISTINCT entries.id) AS count',
        'where'  => 'types.code = ' . db_escape('gallery') . ' AND entries.public = ' . db_escape('none'),
    ], [
        'associate' => true,
    ]);
    $public_none_count = $public_none_count[0]['count'];

    // 表示用コンテンツを作成
    $content  = '<span class="fs-4"><a href="' . t(MAIN_FILE, true) . '/admin/gallery">' . h($count, true) . '</a></span>';
    $content .= ($GLOBALS['plugin']['gallery']['setting']['use_approve'] ? ' / 未承認 ' . h($not_approved_count, true) : ' / 非公開 ' . h($public_none_count, true));

    // ダッシュボードに追加
    $_view['widget_sets']['admin_ready'] .= <<< EOM
<script>
// ボックスを定義
var html = `
    <div class="col-md-3">
        <div class="card shadow-sm mb-3">
            <div class="card-header">ギャラリー数</div>
            <div class="card-body text-center">
                {$content}
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
