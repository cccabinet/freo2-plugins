<?php

// テーブルを削除
$resource = db_query('DROP TABLE IF EXISTS ' . DATABASE_PREFIX . 'rewrite_rules');
if (!$resource) {
    error('プラグイン用SQL [テーブルを削除] を実行できません。');
}
