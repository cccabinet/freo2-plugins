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
                    <li class="breadcrumb-item"><a href="<?php t(MAIN_FILE) ?>/admin/spec">規格管理</a></li>
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

                <form action="<?php t(MAIN_FILE) ?>/admin/spec_form?entry_id=<?php t($_GET['entry_id']) ?><?php $_view['order_spec']['id'] ? t('&id=' . $_view['order_spec']['id']) : '' ?>" method="post" class="register validate">
                    <input type="hidden" name="_token" value="<?php t($_view['token']) ?>" class="token">
                    <input type="hidden" name="id" value="<?php t($_view['order_spec']['id']) ?>">
                    <input type="hidden" name="entry_id" value="<?php t($_GET['entry_id']) ?>">
                    <div class="card shadow-sm mb-3">
                        <div class="card-header">
                            登録
                        </div>
                        <div class="card-body">
                            <div class="form-group mb-2">
                                <label class="fw-bold">規格管理コード <span class="badge text-light bg-secondary" data-toggle="tooltip" title="規格の管理コード。">？</span> <span class="badge bg-danger">必須</span></label>
                                <input type="text" name="code" size="30" value="<?php t($_view['order_spec']['code']) ?>" class="form-control">
                            </div>
                            <div class="form-group mb-2">
                                <label class="fw-bold">名前 <span class="badge bg-danger">必須</span></label>
                                <input type="text" name="name" size="30" value="<?php t($_view['order_spec']['name']) ?>" class="form-control">
                            </div>
                            <div class="form-group mb-2">
                                <label class="fw-bold">提供方法 <span class="badge bg-danger">必須</span></label>
                                <select name="provide" class="form-select" style="width: 200px;">
                                    <option value=""></option>
                                    <?php foreach ($GLOBALS['plugin']['order']['option']['order_spec']['provide'] as $key => $value) : ?>
                                    <option value="<?php t($key) ?>"<?php $key == $_view['order_spec']['provide'] ? e(' selected="selected"') : '' ?>><?php t($value) ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                            <div class="form-group mb-2">
                                <label class="fw-bold">販売価格 <span class="badge bg-danger">必須</span></label>
                                <input type="text" name="selling_price" size="30" value="<?php t($_view['order_spec']['selling_price']) ?>" class="form-control" style="width: 200px;">
                            </div>
                            <div class="form-group mb-2">
                                <label class="fw-bold">通常価格 <span class="badge text-light bg-secondary" data-toggle="tooltip" title="販売価格が特別な価格である場合、通常価格を併記できます。">？</span></label>
                                <input type="text" name="regular_price" size="30" value="<?php t($_view['order_spec']['regular_price']) ?>" class="form-control" style="width: 200px;">
                            </div>
                            <div class="form-group mb-2 for-delivery">
                                <label class="fw-bold">送料</label>
                                <input type="text" name="shipping_cost" size="30" value="<?php t($_view['order_spec']['shipping_cost']) ?>" class="form-control" style="width: 200px;">
                            </div>
                            <div class="form-group mb-2 for-delivery">
                                <label class="fw-bold">配送日目安</label>
                                <select name="delivery_days" class="form-select" style="width: 200px;">
                                    <option value=""></option>
                                    <?php foreach ($GLOBALS['plugin']['order']['option']['order_spec']['delivery_days'] as $key => $value) : ?>
                                    <option value="<?php t($key) ?>"<?php $key == $_view['order_spec']['delivery_days'] ? e(' selected="selected"') : '' ?>><?php t($value) ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                            <div class="form-group mb-2">
                                <label class="fw-bold">販売制限数 <span class="badge text-light bg-secondary" data-toggle="tooltip" title="一度に購入できる最大数。">？</span></label>
                                <input type="text" name="sales_limit" size="30" value="<?php t($_view['order_spec']['sales_limit']) ?>" class="form-control" style="width: 200px;">
                            </div>
                            <div class="form-group mb-2">
                                <label class="fw-bold">有効 <span class="badge bg-danger">必須</span></label>
                                <select name="enabled" class="form-select" style="width: 200px;">
                                    <?php foreach ($GLOBALS['plugin']['order']['option']['order_spec']['enabled'] as $key => $value) : ?>
                                    <option value="<?php t($key) ?>"<?php $key == $_view['order_spec']['enabled'] ? e(' selected="selected"') : '' ?>><?php t($value) ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                            <div class="form-group mb-2">
                                <label class="fw-bold">店舗用メモ <span class="badge text-light bg-secondary" data-toggle="tooltip" title="公開されないテキスト。">？</span></label>
                                <textarea name="memo" rows="10" cols="50" class="form-control"><?php t($_view['order_spec']['memo']) ?></textarea>
                            </div>
                            <div class="form-group mt-4">
                                <button type="submit" class="btn btn-primary px-4">登録</button>
                            </div>
                        </div>
                    </div>
                </form>

                <?php if (!empty($_GET['id'])) : ?>
                <form action="<?php t(MAIN_FILE) ?>/admin/spec_delete?entry_id=<?php t($_GET['entry_id']) ?>" method="post" class="delete">
                    <input type="hidden" name="_token" value="<?php t($_view['token']) ?>" class="token">
                    <input type="hidden" name="id" value="<?php t($_view['order_spec']['id']) ?>">
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

<?php

$_view['script'] = ($_view['script'] ?? '') . '<script src="' . t($GLOBALS['config']['http_path'], true) . t(loader_file('plugins/order/js/admin.js'), true) . '"></script>' . "\n";

?>
<?php import('app/views/admin/footer.php') ?>
