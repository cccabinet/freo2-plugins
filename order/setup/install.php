<?php

// 型を登録するためのソート順を取得
$categories = db_select([
    'select' => 'MAX(sort) AS sort',
    'from'   => DATABASE_PREFIX . 'types',
    'where'  => 'deleted IS NULL',
]);
$category_sort = $categories[0]['sort'];

// 型を登録
$resource = db_insert([
    'insert_into' => DATABASE_PREFIX . 'types',
    'values'      => [
        'created'  => localdate('Y-m-d H:i:s'),
        'modified' => localdate('Y-m-d H:i:s'),
        'code'     => 'catalog',
        'name'     => '商品',
        'memo'     => NULL,
        'sort'     => $category_sort + 1,
    ],
]);
if (!$resource) {
    error('プラグイン用SQL [型を登録] を実行できません。');
}

// テーブルを作成
$resource = db_query('
    CREATE TABLE IF NOT EXISTS ' . DATABASE_PREFIX . 'order_stocks(
        id         INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT \'代理キー\',
        created    DATETIME     NOT NULL                COMMENT \'作成日時\',
        modified   DATETIME     NOT NULL                COMMENT \'更新日時\',
        deleted    DATETIME                             COMMENT \'削除日時\',
        code       VARCHAR(255) NOT NULL UNIQUE         COMMENT \'在庫管理コード\',
        name       VARCHAR(255) NOT NULL                COMMENT \'名前\',
        text       TEXT                                 COMMENT \'内容\',
        kind       VARCHAR(20)  NOT NULL                COMMENT \'種類\',
        download   TEXT                                 COMMENT \'ダウンロード案内\',
        quantity   INT UNSIGNED                         COMMENT \'数\',
        cost_price INT UNSIGNED                         COMMENT \'原価\',
        memo       TEXT                                 COMMENT \'店舗用メモ\',
        PRIMARY KEY(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT \'オーダー 在庫\';
');
if (!$resource) {
    error('プラグイン用SQL [テーブルを作成] を実行できません。');
}

$resource = db_query('
    CREATE TABLE IF NOT EXISTS ' . DATABASE_PREFIX . 'order_specs(
        id            INT UNSIGNED        NOT NULL AUTO_INCREMENT COMMENT \'代理キー\',
        created       DATETIME            NOT NULL                COMMENT \'作成日時\',
        modified      DATETIME            NOT NULL                COMMENT \'更新日時\',
        deleted       DATETIME                                    COMMENT \'削除日時\',
        entry_id      INT UNSIGNED        NOT NULL                COMMENT \'外部キー エントリー\',
        code          VARCHAR(255)        NOT NULL UNIQUE         COMMENT \'規格管理コード\',
        enabled       TINYINT(1) UNSIGNED NOT NULL                COMMENT \'有効\',
        name          VARCHAR(255)        NOT NULL                COMMENT \'名前\',
        provide       VARCHAR(20)         NOT NULL                COMMENT \'提供方法\',
        selling_price INT UNSIGNED        NOT NULL                COMMENT \'販売価格\',
        regular_price INT UNSIGNED                                COMMENT \'通常価格\',
        shipping_cost INT UNSIGNED                                COMMENT \'送料\',
        delivery_days INT UNSIGNED                                COMMENT \'配送日目安\',
        sales_limit   INT UNSIGNED                                COMMENT \'販売制限数\',
        memo          TEXT                                        COMMENT \'店舗用メモ\',
        sort          INT UNSIGNED        NOT NULL                COMMENT \'並び順\',
        PRIMARY KEY(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT \'オーダー 規格\';
');
if (!$resource) {
    error('プラグイン用SQL [テーブルを作成] を実行できません。');
}

$resource = db_query('
    CREATE TABLE IF NOT EXISTS ' . DATABASE_PREFIX . 'order_products(
        id       INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT \'代理キー\',
        created  DATETIME     NOT NULL                COMMENT \'作成日時\',
        modified DATETIME     NOT NULL                COMMENT \'更新日時\',
        deleted  DATETIME                             COMMENT \'削除日時\',
        spec_id  INT UNSIGNED NOT NULL                COMMENT \'外部キー 規格\',
        stock_id INT UNSIGNED NOT NULL                COMMENT \'外部キー 在庫\',
        quantity INT UNSIGNED NOT NULL                COMMENT \'数\',
        memo     TEXT                                 COMMENT \'店舗用メモ\',
        sort     INT UNSIGNED NOT NULL                COMMENT \'並び順\',
        PRIMARY KEY(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT \'オーダー 製品\';
');
if (!$resource) {
    error('プラグイン用SQL [テーブルを作成] を実行できません。');
}

$resource = db_query('
    CREATE TABLE IF NOT EXISTS ' . DATABASE_PREFIX . 'order_payments(
        id       INT UNSIGNED        NOT NULL AUTO_INCREMENT COMMENT \'代理キー\',
        created  DATETIME            NOT NULL                COMMENT \'作成日時\',
        modified DATETIME            NOT NULL                COMMENT \'更新日時\',
        deleted  DATETIME                                    COMMENT \'削除日時\',
        enabled  TINYINT(1) UNSIGNED NOT NULL                COMMENT \'有効\',
        name     VARCHAR(255)        NOT NULL                COMMENT \'名前\',
        text     TEXT                                        COMMENT \'内容\',
        fee      INT UNSIGNED        NOT NULL                COMMENT \'手数料\',
        memo     TEXT                                        COMMENT \'店舗用メモ\',
        PRIMARY KEY(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT \'オーダー 支払方法\';
');
if (!$resource) {
    error('プラグイン用SQL [テーブルを作成] を実行できません。');
}

$resource = db_query('
    CREATE TABLE IF NOT EXISTS ' . DATABASE_PREFIX . 'order_deliveries(
        id         INT UNSIGNED        NOT NULL AUTO_INCREMENT COMMENT \'代理キー\',
        created    DATETIME            NOT NULL                COMMENT \'作成日時\',
        modified   DATETIME            NOT NULL                COMMENT \'更新日時\',
        deleted    DATETIME                                    COMMENT \'削除日時\',
        enabled    TINYINT(1) UNSIGNED NOT NULL                COMMENT \'有効\',
        name       VARCHAR(255)        NOT NULL                COMMENT \'名前\',
        text       TEXT                                        COMMENT \'内容\',
        cost       INT UNSIGNED        NOT NULL                COMMENT \'送料\',
        surcharge  TEXT                                        COMMENT \'送料（上乗せ）\',
        calculate  VARCHAR(20)         NOT NULL                COMMENT \'送料計算\',
        threshold  INT UNSIGNED                                COMMENT \'値引きする注文金額の閾値\',
        discounted INT UNSIGNED                                COMMENT \'値引き後の送料\',
        memo       TEXT                                        COMMENT \'店舗用メモ\',
        PRIMARY KEY(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT \'オーダー 配送方法\';
');
if (!$resource) {
    error('プラグイン用SQL [テーブルを作成] を実行できません。');
}

$resource = db_query('
    CREATE TABLE IF NOT EXISTS ' . DATABASE_PREFIX . 'order_addresses( # 内容は order_histories に合わせる
        id         INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT \'代理キー\',
        created    DATETIME     NOT NULL                COMMENT \'作成日時\',
        modified   DATETIME     NOT NULL                COMMENT \'更新日時\',
        deleted    DATETIME                             COMMENT \'削除日時\',
        user_id    INT UNSIGNED NOT NULL                COMMENT \'外部キー ユーザー\',
        name_01    VARCHAR(255)                         COMMENT \'名前 1\',
        name_02    VARCHAR(255)                         COMMENT \'名前 2\',
        kana_01    VARCHAR(255)                         COMMENT \'カナ 1\',
        kana_02    VARCHAR(255)                         COMMENT \'カナ 2\',
        zipcode    VARCHAR(80)                          COMMENT \'郵便番号\',
        prefecture VARCHAR(80)                          COMMENT \'都道府県\',
        address_01 TEXT                                 COMMENT \'住所 1\',
        address_02 TEXT                                 COMMENT \'住所 2\',
        telephone  VARCHAR(80)                          COMMENT \'電話番号\',
        PRIMARY KEY(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT \'オーダー 住所\';
');
if (!$resource) {
    error('プラグイン用SQL [テーブルを作成] を実行できません。');
}

$resource = db_query('
    CREATE TABLE IF NOT EXISTS ' . DATABASE_PREFIX . 'order_records(
        id            INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT \'代理キー\',
        created       DATETIME     NOT NULL                COMMENT \'作成日時\',
        modified      DATETIME     NOT NULL                COMMENT \'更新日時\',
        deleted       DATETIME                             COMMENT \'削除日時\',
        provide       VARCHAR(20)  NOT NULL                COMMENT \'提供方法\',
        payment_id    INT UNSIGNED NOT NULL                COMMENT \'外部キー 支払方法\',
        payment_fee   INT UNSIGNED NOT NULL                COMMENT \'手数料\',
        delivery_id   INT UNSIGNED                         COMMENT \'外部キー 配送方法\',
        delivery_cost INT UNSIGNED NOT NULL                COMMENT \'送料\',
        discount      INT UNSIGNED NOT NULL                COMMENT \'値引き額\',
        shipping_date DATE                                 COMMENT \'配送日\',
        status        VARCHAR(20)  NOT NULL                COMMENT \'状況\',
        user_id       INT UNSIGNED                         COMMENT \'外部キー ユーザー\',
        email         VARCHAR(255)                         COMMENT \'メールアドレス\',
        name_01       VARCHAR(255)                         COMMENT \'名前 姓\',
        name_02       VARCHAR(255)                         COMMENT \'名前 名\',
        kana_01       VARCHAR(255)                         COMMENT \'カナ 姓\',
        kana_02       VARCHAR(255)                         COMMENT \'カナ 名\',
        zipcode       VARCHAR(80)                          COMMENT \'郵便番号\',
        prefecture    VARCHAR(20)                          COMMENT \'都道府県\',
        address_01    TEXT                                 COMMENT \'住所 1\',
        address_02    TEXT                                 COMMENT \'住所 2\',
        telephone     VARCHAR(80)                          COMMENT \'電話番号\',
        message       TEXT                                 COMMENT \'お問い合わせ内容\',
        memo          TEXT                                 COMMENT \'店舗用メモ\',
        PRIMARY KEY(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT \'オーダー 注文記録\';
');
if (!$resource) {
    error('プラグイン用SQL [テーブルを作成] を実行できません。');
}

$resource = db_query('
    CREATE TABLE IF NOT EXISTS ' . DATABASE_PREFIX . 'order_record_items(
        id            INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT \'代理キー\',
        created       DATETIME     NOT NULL                COMMENT \'作成日時\',
        modified      DATETIME     NOT NULL                COMMENT \'更新日時\',
        deleted       DATETIME                             COMMENT \'削除日時\',
        record_id     INT UNSIGNED NOT NULL                COMMENT \'外部キー 注文記録\',
        spec_id       INT UNSIGNED                         COMMENT \'外部キー 規格\',
        name          VARCHAR(255)                         COMMENT \'項目\',
        selling_price INT UNSIGNED NOT NULL                COMMENT \'販売価格\',
        cost_price    INT UNSIGNED                         COMMENT \'原価\',
        quantity      INT UNSIGNED NOT NULL                COMMENT \'数\',
        PRIMARY KEY(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT \'オーダー 注文明細\';
');
if (!$resource) {
    error('プラグイン用SQL [テーブルを作成] を実行できません。');
}

$resource = db_query('
    CREATE TABLE IF NOT EXISTS ' . DATABASE_PREFIX . 'order_shippings(
        id            INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT \'代理キー\',
        created       DATETIME     NOT NULL                COMMENT \'作成日時\',
        modified      DATETIME     NOT NULL                COMMENT \'更新日時\',
        deleted       DATETIME                             COMMENT \'削除日時\',
        record_id     INT UNSIGNED NOT NULL                COMMENT \'外部キー 注文記録\',
        delivery_id   INT UNSIGNED                         COMMENT \'外部キー 配送方法\',
        delivery_cost INT UNSIGNED                         COMMENT \'送料\',
        shipping_date DATE                                 COMMENT \'配送日\',
        status        VARCHAR(20)  NOT NULL                COMMENT \'状況\',
        email         VARCHAR(255)                         COMMENT \'メールアドレス\',
        name_01       VARCHAR(255)                         COMMENT \'名前 姓\',
        name_02       VARCHAR(255)                         COMMENT \'名前 名\',
        kana_01       VARCHAR(255)                         COMMENT \'カナ 姓\',
        kana_02       VARCHAR(255)                         COMMENT \'カナ 名\',
        zipcode       VARCHAR(80)                          COMMENT \'郵便番号\',
        prefecture    VARCHAR(20)                          COMMENT \'都道府県\',
        address_01    TEXT                                 COMMENT \'住所 1\',
        address_02    TEXT                                 COMMENT \'住所 2\',
        telephone     VARCHAR(80)                          COMMENT \'電話番号\',
        memo          TEXT                                 COMMENT \'店舗用メモ\',
        PRIMARY KEY(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT \'オーダー 発送記録\';
');
if (!$resource) {
    error('プラグイン用SQL [テーブルを作成] を実行できません。');
}

$resource = db_query('
    CREATE TABLE IF NOT EXISTS ' . DATABASE_PREFIX . 'order_shipping_items(
        id             INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT \'代理キー\',
        created        DATETIME     NOT NULL                COMMENT \'作成日時\',
        modified       DATETIME     NOT NULL                COMMENT \'更新日時\',
        deleted        DATETIME                             COMMENT \'削除日時\',
        shipping_id    INT UNSIGNED NOT NULL                COMMENT \'外部キー 配送記録\',
        record_item_id INT UNSIGNED NOT NULL                COMMENT \'外部キー 注文明細\',
        quantity       INT UNSIGNED NOT NULL                COMMENT \'数\',
        PRIMARY KEY(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT \'オーダー 発送明細\';
');
if (!$resource) {
    error('プラグイン用SQL [テーブルを作成] を実行できません。');
}
