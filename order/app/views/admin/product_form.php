<?php import('app/views/admin/header.php') ?>

    <main class="col-md-9 ms-sm-auto col-lg-10 mb-2 px-md-4">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 mb-2">
            <h2 class="h3">
                <svg class="bi flex-shrink-0 me-1 mb-1" width="24" height="24"><use xlink:href="#symbol-pencil-square"/></svg>
                コンテンツ
            </h2>
            <nav style="--bs-breadcrumb-divider: '>';">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php t(MAIN_FILE) ?>/admin/">ホーム</a></li>
                    <li class="breadcrumb-item"><a href="<?php t(MAIN_FILE) ?>/admin/catalog">商品管理</a></li>
                    <li class="breadcrumb-item"><a href="<?php t(MAIN_FILE) ?>/admin/spec?entry_id=<?php t($_GET['entry_id']) ?>">規格管理</a></li>
                    <li class="breadcrumb-item"><a href="<?php t(MAIN_FILE) ?>/admin/product?entry_id=<?php t($_GET['entry_id']) ?>&amp;spec_id=<?php t($_GET['spec_id']) ?>">製品管理</a></li>
                    <li class="breadcrumb-item active"><?php h($_view['title']) ?></li>
                </ol>
            </nav>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header heading"><?php h($_view['title']) ?></div>
            <div class="card-body">
                <?php if (isset($_view['warnings'])) : ?>
                <div class="alert alert-danger">
                    <svg class="bi flex-shrink-0 me-2" width="24" height="24"><use xlink:href="#symbol-exclamation-triangle-fill"/></svg>
                    <?php foreach ($_view['warnings'] as $warning) : ?>
                    <?php h($warning) ?>
                    <?php endforeach ?>
                </div>
                <?php endif ?>

                <form action="<?php t(MAIN_FILE) ?>/admin/product_form?entry_id=<?php t($_GET['entry_id']) ?>&amp;spec_id=<?php t($_GET['spec_id']) ?><?php $_view['order_product']['id'] ? t('&id=' . $_view['order_product']['id']) : '' ?>" method="post" class="register validate">
                    <input type="hidden" name="_token" value="<?php t($_view['token']) ?>" class="token">
                    <input type="hidden" name="id" value="<?php t($_view['order_product']['id']) ?>">
                    <input type="hidden" name="spec_id" value="<?php t($_GET['spec_id']) ?>">
                    <input type="hidden" name="entry_id" value="<?php t($_GET['entry_id']) ?>">
                    <div class="card shadow-sm mb-3">
                        <div class="card-header">
                            登録
                        </div>
                        <div class="card-body">
                            <div class="form-group mb-2">
                                <label class="fw-bold">在庫 <span class="badge text-bg-danger">必須</span></label>
                                <select name="stock_id" class="form-select">
                                    <option value=""></option>
                                    <?php foreach ($_view['stocks'] as $stock) : ?>
                                    <option value="<?php t($stock['id']) ?>"<?php $stock['id'] == $_view['order_product']['stock_id'] ? e(' selected="selected"') : '' ?>><?php t($stock['code']) ?> - <?php t($stock['name']) ?><?php t($stock['quantity'] !== null ? ' - 在庫数 ' . number_format($stock['quantity']) : '') ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                            <div class="form-group mb-2">
                                <label class="fw-bold">数 <span class="badge text-bg-secondary" data-bs-toggle="tooltip" title="購入時に在庫を減らす数。">？</span> <span class="badge text-bg-danger">必須</span></label>
                                <input type="text" name="quantity" size="30" value="<?php t($_view['order_product']['quantity']) ?>" class="form-control" style="width: 200px;">
                            </div>
                            <div class="form-group mb-2">
                                <label class="fw-bold">店舗用メモ <span class="badge text-bg-secondary" data-bs-toggle="tooltip" title="公開されないテキスト。">？</span></label>
                                <textarea name="memo" rows="10" cols="50" class="form-control"><?php t($_view['order_product']['memo']) ?></textarea>
                            </div>
                            <div class="form-group mt-4">
                                <button type="submit" class="btn btn-primary px-4">登録</button>
                            </div>
                        </div>
                    </div>
                </form>

                <?php if (!empty($_GET['id'])) : ?>
                <form action="<?php t(MAIN_FILE) ?>/admin/product_delete?spec_id=<?php t($_GET['spec_id']) ?>" method="post" class="delete">
                    <input type="hidden" name="_token" value="<?php t($_view['token']) ?>" class="token">
                    <input type="hidden" name="id" value="<?php t($_view['order_product']['id']) ?>">
                    <input type="hidden" name="spec_id" value="<?php t($_GET['spec_id']) ?>">
                    <input type="hidden" name="entry_id" value="<?php t($_GET['entry_id']) ?>">
                    <div class="card shadow-sm mb-3">
                        <div class="card-header">
                            削除
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <button type="submit" class="btn btn-danger px-4">削除</button>
                            </div>
                        </div>
                    </div>
                </form>
                <?php endif ?>
            </div>
        </div>
        <?php e($_view['widget_sets']['admin_page']) ?>
    </main>

<?php import('app/views/admin/footer.php') ?>
