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
                    <li class="breadcrumb-item active"><?php h($_view['title']) ?></li>
                </ol>
            </nav>
        </div>

        <?php if (!empty($_GET['id'])) : ?>
        <?php
        $total = 0;
        foreach ($_view['order_record_items'] as $order_record_item) {
            $total += $order_record_item['selling_price'] * $order_record_item['quantity'];
        }
        $total += $_view['order_record']['payment_fee'] + $_view['order_record']['delivery_cost'] - $_view['order_record']['discount'];
        ?>
        <div class="card shadow-sm mb-3">
            <div class="card-header heading">注文明細</div>
            <div class="card-body">
                <p>注文番号 <?php h($_view['order_record']['id']) ?></p>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th class="text-nowrap">商品名</th>
                            <th class="text-nowrap">規格名</th>
                            <th class="text-nowrap text-end">単価</th>
                            <th class="text-nowrap text-end">数</th>
                            <th class="text-nowrap text-end">小計</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($_view['order_record_items'] as $order_record_item) : ?>
                        <tr>
                            <td><?php h($_view['order_spec_sets'][$order_record_item['spec_id']]['entry_title'] ?? $order_record_item['name']) ?></td>
                            <td><?php h($_view['order_spec_sets'][$order_record_item['spec_id']]['name'] ?? '') ?></td>
                            <td class="text-end"><?php h(number_format($order_record_item['selling_price'])) ?>円</td>
                            <td class="text-end"><?php h(number_format($order_record_item['quantity'])) ?></td>
                            <td class="text-end"><?php h(number_format($order_record_item['selling_price'] * $order_record_item['quantity'])) ?>円</td>
                        </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
                <dl class="row mb-0">
                    <dt class="col-sm-3">支払手数料</dt>
                    <dd class="col-sm-9"><?php h(number_format($_view['order_record']['payment_fee'])) ?>円</dd>
                    <dt class="col-sm-3">送料</dt>
                    <dd class="col-sm-9"><?php h(number_format($_view['order_record']['delivery_cost'])) ?>円</dd>
                    <dt class="col-sm-3">値引き額</dt>
                    <dd class="col-sm-9"><?php h(number_format($_view['order_record']['discount'])) ?>円</dd>
                    <dt class="col-sm-3 fw-bold">合計金額</dt>
                    <dd class="col-sm-9 fw-bold mb-0"><?php h(number_format($total)) ?>円</dd>
                </dl>
            </div>
        </div>
        <?php endif ?>

        <?php if (($_view['order_record']['provide'] ?? null) === 'download') : ?>
        <div class="card shadow-sm mb-3">
            <div class="card-header heading">ダウンロード案内</div>
            <div class="card-body">
                <?php if ($_view['order_download_text'] === '') : ?>
                <p>ダウンロード案内が登録されていません。</p>
                <?php else : ?>
                <p>ダウンロード案内は以下のとおりです。（メール本文に貼り付けるなどしてご利用ください。）</p>
                <pre class="bg-light border rounded p-3 my-2"><?php t($_view['order_download_text']) ?></pre>
                <?php endif ?>
            </div>
        </div>
        <?php endif ?>

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

                <form action="<?php t(MAIN_FILE) ?>/admin/order_form<?php $_view['order_record']['id'] ? t('?id=' . $_view['order_record']['id']) : '' ?>" method="post" class="register validate">
                    <input type="hidden" name="_token" value="<?php t($_view['token']) ?>" class="token">
                    <input type="hidden" name="id" value="<?php t($_view['order_record']['id']) ?>">
                    <div class="card shadow-sm mb-3">
                        <div class="card-header">
                            登録
                        </div>
                        <div class="card-body">
                            <?php if (empty($_GET['id'])) : ?>
                            <div class="form-group mb-2">
                                <label class="fw-bold">商品 <span class="badge text-light bg-secondary" data-bs-toggle="tooltip" title="必要な行だけ選択してください。">？</span></label>
                                <div id="order_record_item_rows">
                                    <?php for ($i = 0; $i < 5; $i++) : ?>
                                    <div class="order_record_item_row d-flex align-items-center gap-2 mb-1">
                                        <select name="spec_id[]" class="form-select">
                                            <option value=""></option>
                                            <?php foreach ($_view['order_specs'] as $order_spec) : ?>
                                            <option value="<?php t($order_spec['id']) ?>"<?php !empty($_view['order_spec_out_of_stocks'][$order_spec['id']]) ? e(' disabled="disabled"') : '' ?>><?php t($order_spec['entry_title'] . ' - ' . $order_spec['name']) ?><?php if (!empty($_view['order_spec_out_of_stocks'][$order_spec['id']])) : ?>（在庫切れ）<?php endif ?></option>
                                            <?php endforeach ?>
                                            <option value="__custom__">直接入力</option>
                                        </select>
                                        <input type="text" name="item_name[]" placeholder="名前" class="form-control order_record_item_name">
                                        <input type="text" name="item_price[]" placeholder="価格" size="5" class="form-control order_record_item_price" style="width: 100px;">
                                        <input type="text" name="quantity[]" value="1" size="5" class="form-control" style="width: 80px;">
                                    </div>
                                    <?php endfor ?>
                                </div>
                                <button type="button" id="order_record_item_add" class="btn btn-secondary btn-sm mt-1">＋ 行を追加</button>
                            </div>
                            <?php endif ?>
                            <div class="form-group mb-2">
                                <label class="fw-bold">提供方法 <span class="badge bg-danger">必須</span></label>
                                <select name="provide" class="form-select" style="width: 200px;">
                                    <option value=""></option>
                                    <?php foreach ($GLOBALS['plugin']['order']['option']['order_spec']['provide'] as $key => $value) : ?>
                                    <option value="<?php t($key) ?>"<?php $key == $_view['order_record']['provide'] ? e(' selected="selected"') : '' ?>><?php t($value) ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                            <div class="form-group mb-2">
                                <label class="fw-bold">状況 <span class="badge bg-danger">必須</span></label>
                                <select name="status" class="form-select" style="width: 200px;">
                                    <?php foreach ($GLOBALS['plugin']['order']['option']['order_record']['status'] as $key => $value) : ?>
                                    <option value="<?php t($key) ?>"<?php $key == $_view['order_record']['status'] ? e(' selected="selected"') : '' ?>><?php t($value) ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                            <div class="form-group mb-2">
                                <label class="fw-bold">支払方法</label>
                                <select name="payment_id" class="form-select" style="width: 200px;">
                                    <option value=""></option>
                                    <?php foreach ($_view['order_payments'] as $order_payment) : ?>
                                    <option value="<?php t($order_payment['id']) ?>"<?php $order_payment['id'] == $_view['order_record']['payment_id'] ? e(' selected="selected"') : '' ?>><?php t($order_payment['name']) ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                            <div class="form-group mb-2">
                                <label class="fw-bold">手数料</label>
                                <input type="text" name="payment_fee" size="30" value="<?php t($_view['order_record']['payment_fee']) ?>" class="form-control" style="width: 200px;">
                            </div>
                            <div class="form-group mb-2 for-delivery">
                                <label class="fw-bold">配送方法</label>
                                <select name="delivery_id" class="form-select" style="width: 200px;">
                                    <option value=""></option>
                                    <?php foreach ($_view['order_deliveries'] as $order_delivery) : ?>
                                    <option value="<?php t($order_delivery['id']) ?>"<?php $order_delivery['id'] == $_view['order_record']['delivery_id'] ? e(' selected="selected"') : '' ?>><?php t($order_delivery['name']) ?></option>
                                    <?php endforeach ?>
                                </select>
                            </div>
                            <div class="form-group mb-2 for-delivery">
                                <label class="fw-bold">送料</label>
                                <input type="text" name="delivery_cost" size="30" value="<?php t($_view['order_record']['delivery_cost']) ?>" class="form-control" style="width: 200px;">
                            </div>
                            <div class="form-group mb-2">
                                <label class="fw-bold">値引き額</label>
                                <input type="text" name="discount" size="30" value="<?php t($_view['order_record']['discount']) ?>" class="form-control" style="width: 200px;">
                            </div>
                            <div class="form-group mb-2 for-delivery">
                                <label class="fw-bold">配送日</label>
                                <input type="text" name="shipping_date" size="30" value="<?php t($_view['order_record']['shipping_date']) ?>" autocomplete="off" class="form-control" style="width: 200px;">
                            </div>
                            <div class="form-group mb-2 for-email">
                                <label class="fw-bold">メールアドレス</label>
                                <input type="text" name="email" size="30" value="<?php t($_view['order_record']['email']) ?>" class="form-control">
                            </div>
                            <div class="form-group mb-2 for-delivery">
                                <label class="fw-bold">名前 姓</label>
                                <input type="text" name="name_01" size="30" value="<?php t($_view['order_record']['name_01']) ?>" class="form-control">
                            </div>
                            <div class="form-group mb-2 for-delivery">
                                <label class="fw-bold">名前 名</label>
                                <input type="text" name="name_02" size="30" value="<?php t($_view['order_record']['name_02']) ?>" class="form-control">
                            </div>
                            <div class="form-group mb-2 for-delivery">
                                <label class="fw-bold">カナ 姓</label>
                                <input type="text" name="kana_01" size="30" value="<?php t($_view['order_record']['kana_01']) ?>" class="form-control">
                            </div>
                            <div class="form-group mb-2 for-delivery">
                                <label class="fw-bold">カナ 名</label>
                                <input type="text" name="kana_02" size="30" value="<?php t($_view['order_record']['kana_02']) ?>" class="form-control">
                            </div>
                            <div class="form-group mb-2 for-delivery">
                                <label class="fw-bold">郵便番号</label>
                                <input type="text" name="zipcode" size="30" value="<?php t($_view['order_record']['zipcode']) ?>" class="form-control" style="width: 200px;">
                            </div>
                            <div class="form-group mb-2 for-delivery">
                                <label class="fw-bold">都道府県</label>
                                <input type="text" name="prefecture" size="30" value="<?php t($_view['order_record']['prefecture']) ?>" class="form-control" style="width: 200px;">
                            </div>
                            <div class="form-group mb-2 for-delivery">
                                <label class="fw-bold">住所 1</label>
                                <input type="text" name="address_01" size="30" value="<?php t($_view['order_record']['address_01']) ?>" class="form-control">
                            </div>
                            <div class="form-group mb-2 for-delivery">
                                <label class="fw-bold">住所 2</label>
                                <input type="text" name="address_02" size="30" value="<?php t($_view['order_record']['address_02']) ?>" class="form-control">
                            </div>
                            <div class="form-group mb-2 for-delivery">
                                <label class="fw-bold">電話番号</label>
                                <input type="text" name="telephone" size="30" value="<?php t($_view['order_record']['telephone']) ?>" class="form-control" style="width: 200px;">
                            </div>
                            <div class="form-group mb-2 for-not_direct">
                                <label class="fw-bold">お問い合わせ内容</label>
                                <textarea name="message" rows="5" cols="50" class="form-control"><?php t($_view['order_record']['message']) ?></textarea>
                            </div>
                            <div class="form-group mb-2">
                                <label class="fw-bold">店舗用メモ <span class="badge text-light bg-secondary" data-bs-toggle="tooltip" title="公開されないテキスト。">？</span></label>
                                <textarea name="memo" rows="10" cols="50" class="form-control"><?php t($_view['order_record']['memo']) ?></textarea>
                            </div>
                            <div class="form-group mt-4">
                                <button type="submit" class="btn btn-primary px-4">登録</button>
                            </div>
                        </div>
                    </div>
                </form>

                <?php if (!empty($_GET['id'])) : ?>
                <form action="<?php t(MAIN_FILE) ?>/admin/order_delete" method="post" class="delete">
                    <input type="hidden" name="_token" value="<?php t($_view['token']) ?>" class="token">
                    <input type="hidden" name="id" value="<?php t($_view['order_record']['id']) ?>">
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
