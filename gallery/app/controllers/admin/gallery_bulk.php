<?php

import('app/services/entry.php');

if (isset($_POST['_type']) && $_POST['_type'] === 'json') {
    // 処理対象を保持（型ごとに分ける）
    service_entry_bulk_select('gallery', $_POST);

    ok();
} else {
    // 処理対象を取得（選んだもののうち、この型のものだけ）
    $_view['entries'] = service_entry_bulk_entries('gallery');
    if (!empty($_view['entries'])) {
        $_view['entry_bulks'] = array_column($_view['entries'], 'id');
    }
}

// タイトル
$_view['title'] = 'ギャラリー一括削除';
