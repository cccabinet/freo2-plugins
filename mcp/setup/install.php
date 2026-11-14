<?php

// テーブルを作成
$resource = db_query('
    CREATE TABLE IF NOT EXISTS ' . DATABASE_PREFIX . 'mcp_tokens(
        id       INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT \'代理キー\',
        created  DATETIME     NOT NULL                COMMENT \'作成日時\',
        modified DATETIME     NOT NULL                COMMENT \'更新日時\',
        deleted  DATETIME                             COMMENT \'削除日時\',
        user_id  INT UNSIGNED NOT NULL                COMMENT \'外部キー ユーザー\',
        name     VARCHAR(255) NOT NULL                COMMENT \'名前\',
        token    VARCHAR(64)  NOT NULL UNIQUE         COMMENT \'トークン\',
        scope    VARCHAR(20)  NOT NULL                COMMENT \'範囲\',
        accessed DATETIME                             COMMENT \'最終利用日時\',
        expire   DATETIME                             COMMENT \'有効期限\',
        PRIMARY KEY(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT \'MCP トークン\';
');
if (!$resource) {
    error('プラグイン用SQL [テーブルを作成] を実行できません。');
}
