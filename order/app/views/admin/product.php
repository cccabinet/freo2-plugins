<?php import('app/views/admin/header.php') ?>

    <main class="col-md-9 ms-sm-auto col-lg-10 mb-2 px-md-4">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 mb-2">
            <h2 class="h3">
                <svg class="bi flex-shrink-0 me-1 mb-1" width="24" height="24"><use xlink:href="#symbol-list-ul"/></svg>
                コンテンツ
            </h2>
            <nav style="--bs-breadcrumb-divider: '>';">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php t(MAIN_FILE) ?>/admin/">ホーム</a></li>
                    <li class="breadcrumb-item"><a href="<?php t(MAIN_FILE) ?>/admin/catalog">商品管理</a></li>
                    <li class="breadcrumb-item"><a href="<?php t(MAIN_FILE) ?>/admin/spec?entry_id=<?php t($_GET['entry_id']) ?>">規格管理</a></li>
                    <li class="breadcrumb-item active"><?php h($_view['title']) ?></li>
                </ol>
            </nav>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header heading"><?php h($_view['title']) ?></div>
            <div class="card-body">
                <p>規格に属する製品を管理します。「購入を完了した際に在庫数を減らす」設定として使用します。</p>
                <p><a href="<?php t(MAIN_FILE) ?>/admin/product_form?entry_id=<?php t($_GET['entry_id']) ?>&amp;spec_id=<?php t($_GET['spec_id']) ?>" class="btn btn-primary" role="button">製品登録</a></p>
                <?php if (isset($_GET['ok'])) : ?>
                <div class="alert alert-success">
                    <svg class="bi flex-shrink-0 me-2" width="24" height="24"><use xlink:href="#symbol-exclamation-triangle-fill"/></svg>
                    <?php if ($_GET['ok'] === 'post') : ?>
                    製品を登録しました。
                    <?php elseif ($_GET['ok'] === 'sort') : ?>
                    製品を並び替えました。
                    <?php elseif ($_GET['ok'] === 'delete') : ?>
                    製品を削除しました。
                    <?php endif ?>
                </div>
                <?php elseif (isset($_GET['warning'])) : ?>
                <div class="alert alert-danger">
                    <svg class="bi flex-shrink-0 me-2" width="24" height="24"><use xlink:href="#symbol-exclamation-triangle-fill"/></svg>
                    <?php if ($_GET['warning'] === 'delete') : ?>
                    削除対象が選択されていません。
                    <?php endif ?>
                </div>
                <?php endif ?>

                <?php if (!empty($_view['order_product_out_of_stocks'])) : ?>
                <div class="alert alert-warning">
                    <svg class="bi flex-shrink-0 me-2" width="24" height="24"><use xlink:href="#symbol-exclamation-triangle-fill"/></svg>
                    在庫数が0の製品があります。
                </div>
                <?php endif ?>

                <form action="<?php t(MAIN_FILE) ?>/admin/product_sort?entry_id=<?php t($_GET['entry_id']) ?>&amp;spec_id=<?php t($_GET['spec_id']) ?>" method="post" id="sortable">
                    <input type="hidden" name="_token" value="<?php t($_view['token']) ?>" class="token">
                    <input type="hidden" name="spec_id" value="<?php t($_GET['spec_id']) ?>">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th class="text-nowrap d-none d-md-table-cell">在庫管理コード</th>
                                <th class="text-nowrap">名前</th>
                                <th class="text-nowrap">数</th>
                                <th class="text-nowrap d-none d-md-table-cell">並び順</th>
                                <th class="text-nowrap">作業</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th class="text-nowrap d-none d-md-table-cell">在庫管理コード</th>
                                <th class="text-nowrap">名前</th>
                                <th class="text-nowrap">数</th>
                                <th class="text-nowrap d-none d-md-table-cell">並び順</th>
                                <th class="text-nowrap">作業</th>
                            </tr>
                        </tfoot>
                        <tbody>
                            <?php foreach ($_view['order_products'] as $order_product) : ?>
                            <tr id="sort_<?php h($order_product['id']) ?>"<?php !empty($_view['order_product_out_of_stocks'][$order_product['id']]) ? e(' class="table-warning"') : '' ?>>
                                <td class="d-none d-md-table-cell"><code class="text-dark"><?php h($order_product['order_stock_code']) ?></code></td>
                                <td><?php h($order_product['order_stock_name']) ?></td>
                                <td><?php h($order_product['quantity']) ?></td>
                                <td class="d-none d-md-table-cell"><span class="handle text-nowrap"><svg class="bi flex-shrink-0 me-1 mb-1" width="16" height="16"><use xlink:href="#symbol-arrow-down-up"/></svg></span></td>
                                <td><a href="<?php t(MAIN_FILE) ?>/admin/product_form?entry_id=<?php t($_GET['entry_id']) ?>&amp;spec_id=<?php t($_GET['spec_id']) ?>&amp;id=<?php t($order_product['id']) ?>" class="btn btn-primary btn-sm text-nowrap" role="button">編集</a></td>
                            </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </form>
            </div>
        </div>
        <?php e($_view['widget_sets']['admin_page']) ?>
    </main>

<?php import('app/views/admin/footer.php') ?>
