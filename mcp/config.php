<?php

/*******************************************************************************

 設定ファイル

*******************************************************************************/

/* プラグインコード */
$GLOBALS['plugin']['mcp']['code'] = 'mcp';

/* プラグイン名 */
$GLOBALS['plugin']['mcp']['name'] = 'MCP';

/* プラグイン概要 */
$GLOBALS['plugin']['mcp']['description'] = 'ClaudeなどのAIから、MCPでコンテンツを操作できるようにします。';

/* プラグイン詳細 */
$GLOBALS['plugin']['mcp']['detail'] = <<< EOM
MCP（Model Context Protocol）に対応したAIのクライアントから、エントリーやページを検索・取得できるようにします。コメント・メディア・属性・サイトの情報も読めます（管理者のトークンでは、メニュー・ウィジェットも読めます）。
受け口のURLは /mcp/ です。クライアントには、管理画面で発行したトークンをAuthorizationヘッダーで送らせます。
トークンは、管理画面の右上のメニューに追加される「MCPトークン」で発行します。
範囲を「読み取りと下書きの作成」にしたトークンでは、エントリーとページの下書きを作成・更新できます。プラグインの型は、そのプラグインがデータの定義（config.php の data）を持っていれば下書きを作れます。下書きは非公開で作られ、公開は管理画面で行います。更新できるのは非公開のものだけです。
下書きには、カテゴリー・属性・公開期間・コメントの受付も入れられます。画像・サムネイル・フィールドの画像やファイルは、メディアに登録したファイルを複製して付けます（AIから新しいファイルは受け取りません）。公開範囲は、管理画面で公開するときに決めます。
EOM;

/* プラグインバージョン */
$GLOBALS['plugin']['mcp']['version'] = '0.0.0';

/* プラグイン更新日 */
$GLOBALS['plugin']['mcp']['updated'] = '2026-11-10';

/* プラグイン製作者 */
$GLOBALS['plugin']['mcp']['author'] = 'refirio';

/* プラグインURL */
$GLOBALS['plugin']['mcp']['link'] = 'https://refirio.org/';

/* HTML出力で出力するページ（false ならHTML出力に対応しない） */
$GLOBALS['plugin']['mcp']['export'] = false;

/* 対応するプロトコルの版（セッションを持たない版） */
$GLOBALS['plugin']['mcp']['versions'] = ['2026-07-28'];

/* 対応するプロトコルの版（initialize で始める以前の版。先頭を既定にする） */
$GLOBALS['plugin']['mcp']['legacy_versions'] = ['2025-11-25', '2025-06-18', '2025-03-26'];

/* トークンを使える権限の下限（2 = 投稿者） */
$GLOBALS['plugin']['mcp']['power'] = 2;

/* エントリーの検索で一度に返す件数の上限 */
$GLOBALS['plugin']['mcp']['search_limit'] = 100;

/* サイトの情報（get_site）で返す設定（サイト全体に関係するものだけ。型ごとの設定は list_types で返す。メールの設定などは含めない） */
$GLOBALS['plugin']['mcp']['site_settings'] = [
    'page_home_code',
    'page_url_omission',
    'comment_use_approve',
];

/* 選択肢 */
$GLOBALS['plugin']['mcp']['option'] = [
    'mcp_token' => [
        'scope' => [
            'read'  => '読み取り',
            'draft' => '読み取りと下書きの作成',
        ],
    ],
];
