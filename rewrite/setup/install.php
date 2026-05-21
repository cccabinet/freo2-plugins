<?php

// テーブルを作成
$resource = db_query('
    CREATE TABLE IF NOT EXISTS ' . DATABASE_PREFIX . 'rewrite_rules(
        id       INT UNSIGNED        NOT NULL AUTO_INCREMENT COMMENT \'代理キー\',
        created  DATETIME            NOT NULL                COMMENT \'作成日時\',
        modified DATETIME            NOT NULL                COMMENT \'更新日時\',
        deleted  DATETIME                                    COMMENT \'削除日時\',
        enabled  TINYINT(1) UNSIGNED NOT NULL                COMMENT \'有効\',
        name     VARCHAR(255)        NOT NULL                COMMENT \'名前\',
        url      VARCHAR(255)        NOT NULL                COMMENT \'リライト前\',
        rewrited VARCHAR(255)        NOT NULL                COMMENT \'リライト後\',
        type     VARCHAR(80)         NOT NULL                COMMENT \'挙動\',
        memo     TEXT                                        COMMENT \'メモ\',
        sort     INT UNSIGNED        NOT NULL                COMMENT \'並び順\',
        PRIMARY KEY(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT \'リライト ルール\';
');
if (!$resource) {
    error('プラグイン用SQL [テーブルを作成] を実行できません。');
}
