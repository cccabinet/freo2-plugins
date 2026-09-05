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
                <p>商品に属する規格を管理します。「商品をカートに入れる際に選択できる」項目として使用します。</p>
                <p><a href="<?php t(MAIN_FILE) ?>/admin/spec_form?entry_id=<?php t($_GET['entry_id']) ?>" class="btn btn-primary" role="button">規格登録</a></p>
                <?php if (isset($_GET['ok'])) : ?>
                <div class="alert alert-success">
                    <svg class="bi flex-shrink-0 me-2" width="24" height="24"><use xlink:href="#symbol-exclamation-triangle-fill"/></svg>
                    <?php if ($_GET['ok'] === 'post') : ?>
                    規格を登録しました。
                    <?php elseif ($_GET['ok'] === 'sort') : ?>
                    規格を並び替えました。
                    <?php elseif ($_GET['ok'] === 'delete') : ?>
                    規格を削除しました。
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

                <?php if (!empty($_view['order_spec_out_of_stocks'])) : ?>
                <div class="alert alert-warning">
                    <svg class="bi flex-shrink-0 me-2" width="24" height="24"><use xlink:href="#symbol-exclamation-triangle-fill"/></svg>
                    在庫数が0の、有効な規格があります。
                </div>
                <?php endif ?>

                <form action="<?php t(MAIN_FILE) ?>/admin/spec_sort?entry_id=<?php t($_GET['entry_id']) ?>" method="post" id="sortable">
                    <input type="hidden" name="_token" value="<?php t($_view['token']) ?>" class="token">
                    <input type="hidden" name="entry_id" value="<?php t($_GET['entry_id']) ?>">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th class="text-nowrap d-none d-md-table-cell">規格管理コード</th>
                                <th class="text-nowrap">名前</th>
                                <th class="text-nowrap">販売価格</th>
                                <th class="text-nowrap">有効</th>
                                <th class="text-nowrap d-none d-md-table-cell">並び順</th>
                                <th class="text-nowrap">作業</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th class="text-nowrap d-none d-md-table-cell">規格管理コード</th>
                                <th class="text-nowrap">名前</th>
                                <th class="text-nowrap">販売価格</th>
                                <th class="text-nowrap">有効</th>
                                <th class="text-nowrap d-none d-md-table-cell">並び順</th>
                                <th class="text-nowrap">作業</th>
                            </tr>
                        </tfoot>
                        <tbody>
                            <?php foreach ($_view['order_specs'] as $order_spec) : ?>
                            <tr id="sort_<?php h($order_spec['id']) ?>"<?php !empty($_view['order_spec_out_of_stocks'][$order_spec['id']]) ? e(' class="table-warning"') : '' ?>>
                                <td class="d-none d-md-table-cell"><code class="text-dark"><?php h($order_spec['code']) ?></code></td>
                                <td><?php h(truncate($order_spec['name'], 50)) ?></td>
                                <td><?php h(number_format($order_spec['selling_price'])) ?>円</td>
                                <td><span class="badge <?php t(app_badge('enabled', $order_spec['enabled'])) ?>"><?php h($GLOBALS['plugin']['order']['option']['order_spec']['enabled'][$order_spec['enabled']]) ?></span></td>
                                <td class="d-none d-md-table-cell"><span class="handle text-nowrap"><svg class="bi flex-shrink-0 me-1 mb-1" width="16" height="16"><use xlink:href="#symbol-arrow-down-up"/></svg></span></td>
                                <td>
                                    <?php if ($GLOBALS['plugin']['order']['setting']['use_stock']) : ?>
                                    <a href="<?php t(MAIN_FILE) ?>/admin/product?entry_id=<?php t($_GET['entry_id']) ?>&amp;spec_id=<?php t($order_spec['id']) ?>" class="btn btn-primary btn-sm text-nowrap" role="button">製品</a>
                                    <?php endif ?>
                                    <a href="<?php t(MAIN_FILE) ?>/admin/spec_form?entry_id=<?php t($_GET['entry_id']) ?>&amp;id=<?php t($order_spec['id']) ?>" class="btn btn-primary btn-sm text-nowrap" role="button">編集</a>
                                </td>
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
