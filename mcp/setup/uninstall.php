<?php

// テーブルを削除
$resource = db_query('DROP TABLE IF EXISTS ' . DATABASE_PREFIX . 'mcp_tokens');
if (!$resource) {
    error('プラグイン用SQL [テーブルを削除] を実行できません。');
}
