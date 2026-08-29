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
                    <li class="breadcrumb-item active"><?php h($_view['title']) ?></li>
                </ol>
            </nav>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header heading"><?php h($_view['title']) ?></div>
            <div class="card-body">
                <p>在庫を管理します。</p>
                <p><a href="<?php t(MAIN_FILE) ?>/admin/stock_form" class="btn btn-primary">在庫登録</a></p>
                <?php if (isset($_GET['ok'])) : ?>
                <div class="alert alert-success">
                    <svg class="bi flex-shrink-0 me-2" width="24" height="24"><use xlink:href="#symbol-exclamation-triangle-fill"/></svg>
                    <?php if ($_GET['ok'] === 'post') : ?>
                    在庫を登録しました。
                    <?php elseif ($_GET['ok'] === 'delete') : ?>
                    在庫を削除しました。
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

                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th class="text-nowrap d-none d-md-table-cell">在庫管理コード</th>
                            <th class="text-nowrap">名前</th>
                            <th class="text-nowrap d-none d-md-table-cell">種類</th>
                            <th class="text-nowrap">原価</th>
                            <th class="text-nowrap">数</th>
                            <th class="text-nowrap">作業</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th class="text-nowrap d-none d-md-table-cell">在庫管理コード</th>
                            <th class="text-nowrap">名前</th>
                            <th class="text-nowrap d-none d-md-table-cell">種類</th>
                            <th class="text-nowrap">原価</th>
                            <th class="text-nowrap">数</th>
                            <th class="text-nowrap">作業</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        <?php foreach ($_view['order_stocks'] as $order_stock) : ?>
                        <tr id="sort_<?php h($order_stock['id']) ?>">
                            <td class="d-none d-md-table-cell"><code class="text-dark"><?php h($order_stock['code']) ?></code></td>
                            <td><?php h(truncate($order_stock['name'], 50)) ?></td>
                            <td class="text-nowrap d-none d-md-table-cell"><span class="badge <?php t(app_badge('kind', $order_stock['kind'])) ?>"><?php h($GLOBALS['plugin']['order']['option']['order_stock']['kind'][$order_stock['kind']]) ?></span></td>
                            <td class="text-end"><?php if ($order_stock['cost_price'] !== null) : ?><?php h(number_format($order_stock['cost_price'])) ?>円<?php endif ?></td>
                            <td class="text-end"><?php h($order_stock['quantity']) ?></td>
                            <td><a href="<?php t(MAIN_FILE) ?>/admin/stock_form?id=<?php t($order_stock['id']) ?>" class="btn btn-primary btn-sm text-nowrap">編集</a></td>
                        </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php e($_view['widget_sets']['admin_page']) ?>
    </main>

<?php import('app/views/admin/footer.php') ?>
