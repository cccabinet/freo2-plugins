<?php import('app/views/header.php') ?>

    <div id="plugin-catalog">
        <h2 class="h3 mt-4 mb-3"><?php h($_view['title']) ?></h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th class="text-nowrap">規格名</th>
                    <th class="text-nowrap">商品名</th>
                    <th class="text-nowrap text-end">数</th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <th class="text-nowrap">規格名</th>
                    <th class="text-nowrap">商品名</th>
                    <th class="text-nowrap text-end">数</th>
                </tr>
            </tfoot>
            <tbody>
                <?php foreach ($_SESSION['item'] as $item) : ?>
                <tr>
                    <td><?php h($_view['order_spec_sets'][$item['order_spec_id']]['name']) ?></td>
                    <td><?php h($_view['entry_sets'][$_view['order_spec_sets'][$item['order_spec_id']]['entry_id']]['title']) ?></td>
                    <td class="text-end"><?php h($item['quantity']) ?></td>
                </tr>
                <?php endforeach ?>
            </tbody>
        </table>

        <?php if (isset($_view['warnings'])) : ?>
        <div class="alert alert-danger" role="alert">
            <svg class="bi flex-shrink-0 me-2" width="24" height="24"><use xlink:href="#symbol-exclamation-triangle-fill"/></svg>
            <?php foreach ($_view['warnings'] as $warning) : ?>
            <?php h($warning) ?>
            <?php endforeach ?>
        </div>
        <?php endif ?>

        <form action="<?php t(MAIN_FILE) ?>/order/" method="post" class="register validate">
            <input type="hidden" name="_token" value="<?php t($_view['token']) ?>" class="token">
            <?php if (!empty($_view['order_record']['provide'])) : ?>
            <input type="hidden" name="provide" value="<?php t($_view['order_record']['provide']) ?>">
            <?php endif ?>
            <div class="form-group mb-2">
                <label>支払方法</label>
                <select name="payment_id" class="form-select" style="width: 200px;">
                    <option value=""></option>
                    <?php foreach ($_view['order_payments'] as $order_payment) : ?>
                    <option value="<?php t($order_payment['id']) ?>"<?php $order_payment['id'] == $_view['order_record']['payment_id'] ? e(' selected="selected"') : '' ?>><?php t($order_payment['name']) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="form-group mb-2">
                <label>メールアドレス</label>
                <input type="text" name="email" value="<?php t($_view['order_record']['email']) ?>" class="form-control">
            </div>
            <?php if ($_view['order_record']['provide'] !== 'download') : ?>
            <div class="form-group mb-2">
                <label>配送方法</label>
                <select name="delivery_id" class="form-select" style="width: 200px;">
                    <option value=""></option>
                    <?php foreach ($_view['order_deliveries'] as $order_delivery) : ?>
                    <option value="<?php t($order_delivery['id']) ?>"<?php $order_delivery['id'] == $_view['order_record']['delivery_id'] ? e(' selected="selected"') : '' ?>><?php t($order_delivery['name']) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <?php if (!empty($_view['order_addresses'])) : ?>
            <div class="form-group mb-2">
                <label>登録した住所から選ぶ</label>
                <select id="address_select" class="form-select">
                    <option value=""></option>
                    <?php foreach ($_view['order_addresses'] as $order_address) : ?>
                    <option
                        value="<?php t($order_address['id']) ?>"
                        data-name_01="<?php t($order_address['name_01']) ?>"
                        data-name_02="<?php t($order_address['name_02']) ?>"
                        data-kana_01="<?php t($order_address['kana_01']) ?>"
                        data-kana_02="<?php t($order_address['kana_02']) ?>"
                        data-zipcode="<?php t($order_address['zipcode']) ?>"
                        data-prefecture="<?php t($order_address['prefecture']) ?>"
                        data-address_01="<?php t($order_address['address_01']) ?>"
                        data-address_02="<?php t($order_address['address_02']) ?>"
                        data-telephone="<?php t($order_address['telephone']) ?>"
                    ><?php t($order_address['name_01'] . ' ' . $order_address['name_02'] . '（' . $order_address['prefecture'] . $order_address['address_01'] . '）') ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <?php endif ?>
            <div class="form-group mb-2">
                <label>名前 姓</label>
                <input type="text" name="name_01" value="<?php t($_view['order_record']['name_01']) ?>" class="form-control">
            </div>
            <div class="form-group mb-2">
                <label>名前 名</label>
                <input type="text" name="name_02" value="<?php t($_view['order_record']['name_02']) ?>" class="form-control">
            </div>
            <div class="form-group mb-2">
                <label>カナ 姓</label>
                <input type="text" name="kana_01" value="<?php t($_view['order_record']['kana_01']) ?>" class="form-control">
            </div>
            <div class="form-group mb-2">
                <label>カナ 名</label>
                <input type="text" name="kana_02" value="<?php t($_view['order_record']['kana_02']) ?>" class="form-control">
            </div>
            <div class="form-group mb-2">
                <label>郵便番号</label>
                <input type="text" name="zipcode" value="<?php t($_view['order_record']['zipcode']) ?>" class="form-control">
            </div>
            <div class="form-group mb-2">
                <label>都道府県</label>
                <input type="text" name="prefecture" value="<?php t($_view['order_record']['prefecture']) ?>" class="form-control">
            </div>
            <div class="form-group mb-2">
                <label>住所 1</label>
                <input type="text" name="address_01" value="<?php t($_view['order_record']['address_01']) ?>" class="form-control">
            </div>
            <div class="form-group mb-2">
                <label>住所 2</label>
                <input type="text" name="address_02" value="<?php t($_view['order_record']['address_02']) ?>" class="form-control">
            </div>
            <div class="form-group mb-2">
                <label>電話番号</label>
                <input type="text" name="telephone" value="<?php t($_view['order_record']['telephone']) ?>" class="form-control">
            </div>
            <?php endif ?>
            <div class="form-group mb-2">
                <label>お問い合わせ内容</label>
                <textarea name="message" rows="10" cols="50" class="form-control"><?php t($_view['order_record']['message']) ?></textarea>
            </div>
            <div class="form-group mt-4">
                <?php if ($GLOBALS['config']['recaptcha_enable'] == true) : ?>
                <?php recaptcha_input($GLOBALS['config']['recaptcha_site_key']) ?>
                <?php endif ?>
                <button type="submit" class="btn btn-primary px-4"><?php h($GLOBALS['plugin']['order']['setting']['button_order_preview']) ?></button>
            </div>
        </form>

        <?php if ($GLOBALS['config']['recaptcha_enable'] == true) : ?>
        <?php recaptcha_import($GLOBALS['config']['recaptcha_site_key']) ?>
        <?php endif ?>
    </div>

<?php

$_view['script'] = ($_view['script'] ?? '') . '<script src="' . t($GLOBALS['config']['http_path'], true) . t(loader_file('plugins/order/js/order.js'), true) . '"></script>' . "\n";

?>
<?php import('app/views/footer.php') ?>
