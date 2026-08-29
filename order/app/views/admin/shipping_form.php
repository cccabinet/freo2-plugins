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
                    <li class="breadcrumb-item"><a href="<?php t(MAIN_FILE) ?>/admin/order">注文管理</a></li>
                    <li class="breadcrumb-item"><a href="<?php t(MAIN_FILE) ?>/admin/shipping?record_id=<?php t($_view['order_record']['id']) ?>">発送管理</a></li>
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

                <form action="<?php t(MAIN_FILE) ?>/admin/shipping_form?record_id=<?php t($_view['order_record']['id']) ?><?php $_view['order_shipping']['id'] ? t('&id=' . $_view['order_shipping']['id']) : '' ?>" method="post" class="register validate">
                    <input type="hidden" name="_token" value="<?php t($_view['token']) ?>" class="token">
                    <input type="hidden" name="id" value="<?php t($_view['order_shipping']['id']) ?>">
                    <input type="hidden" name="record_id" value="<?php t($_view['order_record']['id']) ?>">
                    <div class="card shadow-sm mb-3">
                        <div class="card-header">
                            登録
                        </div>
                        <div class="card-body">
                            <div class="form-group mb-2">
                                <label class="fw-bold">商品 <span class="badge text-light bg-secondary" data-toggle="tooltip" title="今回発送する数を入力してください。">？</span></label>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th class="text-nowrap">商品名</th>
                                            <th class="text-nowrap">規格名</th>
                                            <th class="text-nowrap text-end">注文数</th>
                                            <th class="text-nowrap text-end">発送可能数</th>
                                            <th class="text-nowrap text-end">今回の発送数</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($_view['order_record_items'] as $order_record_item) : ?>
                                        <tr>
                                            <td><?php h($order_record_item['entry_title'] ?? $order_record_item['name']) ?></td>
                                            <td><?php h($order_record_item['spec_name']) ?></td>
                                            <td class="text-end"><?php h(number_format($order_record_item['quantity'])) ?></td>
                                            <td class="text-end"><?php h(number_format($order_record_item['remained_quantity'])) ?></td>
                                            <td class="text-end">
                                                <input type="hidden" name="record_item_id[]" value="<?php t($order_record_item['id']) ?>">
                                                <input type="text" name="quantity[]" value="<?php t($order_record_item['input_quantity']) ?>" size="5" class="form-control d-inline-block" style="width: 80px;">
                                            </td>
                                        </tr>
                                        <?php endforeach ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="form-group mb-2">
                                <label class="fw-bold">状況 <span class="badge bg-danger">必須</span></label>
                                <select name="status" class="form-select" style="width: 200px;">
                                    <option value=""></option>
                                    <?php foreach ($GLOBALS['plugin']['order']['option']['order_shippings']['status'] as $key => $value) : ?>
                                    <option value="<?php t($key) ?>"<?php $key == $_view['order_shipping']['status'] ? e(' selected="selected"') : '' ?>><?php t($value) ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                            <div class="form-group mb-2">
                                <label class="fw-bold">配送方法</label>
                                <select name="delivery_id" class="form-select" style="width: 200px;">
                                    <option value=""></option>
                                    <?php foreach ($_view['order_deliveries'] as $order_delivery) : ?>
                                    <option value="<?php t($order_delivery['id']) ?>"<?php $order_delivery['id'] == $_view['order_shipping']['delivery_id'] ? e(' selected="selected"') : '' ?>><?php t($order_delivery['name']) ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                            <div class="form-group mb-2">
                                <label class="fw-bold">送料 <span class="badge text-light bg-secondary" data-toggle="tooltip" title="この発送で実際にかかった送料。">？</span></label>
                                <input type="text" name="delivery_cost" size="30" value="<?php t($_view['order_shipping']['delivery_cost']) ?>" class="form-control" style="width: 200px;">
                            </div>
                            <div class="form-group mb-2">
                                <label class="fw-bold">配送日</label>
                                <input type="text" name="shipping_date" size="30" value="<?php t($_view['order_shipping']['shipping_date']) ?>" autocomplete="off" class="form-control" style="width: 200px;">
                            </div>
                            <div class="form-group mb-2">
                                <label class="fw-bold">メールアドレス</label>
                                <input type="text" name="email" size="30" value="<?php t($_view['order_shipping']['email']) ?>" class="form-control">
                            </div>
                            <div class="form-group mb-2">
                                <label class="fw-bold">名前 姓</label>
                                <input type="text" name="name_01" size="30" value="<?php t($_view['order_shipping']['name_01']) ?>" class="form-control">
                            </div>
                            <div class="form-group mb-2">
                                <label class="fw-bold">名前 名</label>
                                <input type="text" name="name_02" size="30" value="<?php t($_view['order_shipping']['name_02']) ?>" class="form-control">
                            </div>
                            <div class="form-group mb-2">
                                <label class="fw-bold">カナ 姓</label>
                                <input type="text" name="kana_01" size="30" value="<?php t($_view['order_shipping']['kana_01']) ?>" class="form-control">
                            </div>
                            <div class="form-group mb-2">
                                <label class="fw-bold">カナ 名</label>
                                <input type="text" name="kana_02" size="30" value="<?php t($_view['order_shipping']['kana_02']) ?>" class="form-control">
                            </div>
                            <div class="form-group mb-2">
                                <label class="fw-bold">郵便番号</label>
                                <input type="text" name="zipcode" size="30" value="<?php t($_view['order_shipping']['zipcode']) ?>" class="form-control" style="width: 200px;">
                            </div>
                            <div class="form-group mb-2">
                                <label class="fw-bold">都道府県</label>
                                <input type="text" name="prefecture" size="30" value="<?php t($_view['order_shipping']['prefecture']) ?>" class="form-control" style="width: 200px;">
                            </div>
                            <div class="form-group mb-2">
                                <label class="fw-bold">住所 1</label>
                                <input type="text" name="address_01" size="30" value="<?php t($_view['order_shipping']['address_01']) ?>" class="form-control">
                            </div>
                            <div class="form-group mb-2">
                                <label class="fw-bold">住所 2</label>
                                <input type="text" name="address_02" size="30" value="<?php t($_view['order_shipping']['address_02']) ?>" class="form-control">
                            </div>
                            <div class="form-group mb-2">
                                <label class="fw-bold">電話番号</label>
                                <input type="text" name="telephone" size="30" value="<?php t($_view['order_shipping']['telephone']) ?>" class="form-control" style="width: 200px;">
                            </div>
                            <div class="form-group mb-2">
                                <label class="fw-bold">店舗用メモ <span class="badge text-light bg-secondary" data-toggle="tooltip" title="公開されないテキスト。">？</span></label>
                                <textarea name="memo" rows="10" cols="50" class="form-control"><?php t($_view['order_shipping']['memo']) ?></textarea>
                            </div>
                            <div class="form-group mt-4">
                                <button type="submit" class="btn btn-primary px-4">登録</button>
                            </div>
                        </div>
                    </div>
                </form>

                <?php if (!empty($_GET['id'])) : ?>
                <form action="<?php t(MAIN_FILE) ?>/admin/shipping_delete" method="post" class="delete">
                    <input type="hidden" name="_token" value="<?php t($_view['token']) ?>" class="token">
                    <input type="hidden" name="id" value="<?php t($_view['order_shipping']['id']) ?>">
                    <input type="hidden" name="record_id" value="<?php t($_view['order_record']['id']) ?>">
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
