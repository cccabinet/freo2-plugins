<?php /** @var array $_view */ ?>
<?php import('app/views/header.php') ?>

    <div id="order">
        <h2 class="h3 mt-4 mb-3"><?php h($_view['title']) ?></h2>
        <?php e($GLOBALS['plugin']['order']['setting']['text_order_preview']) ?>
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
        <form action="<?php t(MAIN_FILE) ?>/order/preview" method="post">
            <input type="hidden" name="_token" value="<?php t($_view['token']) ?>" class="token">
            <dl class="row">
                <dt class="col-sm-3">支払方法</dt>
                <dd class="col-sm-9"><?php h($_view['order_payment']['name'] ?? '') ?></dd>
                <dt class="col-sm-3">配送方法</dt>
                <dd class="col-sm-9"><?php h($_view['order_delivery']['name'] ?? '') ?></dd>
                <dt class="col-sm-3">メールアドレス</dt>
                <dd class="col-sm-9"><?php h($_view['order_record']['email']) ?></dd>
                <?php if ($_view['order_record']['provide'] !== 'download') : ?>
                <dt class="col-sm-3">名前 姓</dt>
                <dd class="col-sm-9"><?php h($_view['order_record']['name_01']) ?></dd>
                <dt class="col-sm-3">名前 名</dt>
                <dd class="col-sm-9"><?php h($_view['order_record']['name_02']) ?></dd>
                <dt class="col-sm-3">カナ 姓</dt>
                <dd class="col-sm-9"><?php h($_view['order_record']['kana_01']) ?></dd>
                <dt class="col-sm-3">カナ 名</dt>
                <dd class="col-sm-9"><?php h($_view['order_record']['kana_02']) ?></dd>
                <dt class="col-sm-3">郵便番号</dt>
                <dd class="col-sm-9"><?php h($_view['order_record']['zipcode']) ?></dd>
                <dt class="col-sm-3">都道府県</dt>
                <dd class="col-sm-9"><?php h($_view['order_record']['prefecture']) ?></dd>
                <dt class="col-sm-3">住所 1</dt>
                <dd class="col-sm-9"><?php h($_view['order_record']['address_01']) ?></dd>
                <dt class="col-sm-3">住所 2</dt>
                <dd class="col-sm-9"><?php h($_view['order_record']['address_02']) ?></dd>
                <dt class="col-sm-3">電話番号</dt>
                <dd class="col-sm-9"><?php h($_view['order_record']['telephone']) ?></dd>
                <?php endif ?>
                <dt class="col-sm-3">お問い合わせ内容</dt>
                <dd class="col-sm-9"><?php h($_view['order_record']['message']) ?></dd>
            </dl>
            <div class="form-group mt-4">
                <a href="<?php t(MAIN_FILE) ?>/order/?referer=preview" class="btn btn-secondary px-4" role="button">修正</a>
                <button type="submit" class="btn btn-primary px-4"><?php h($GLOBALS['plugin']['order']['setting']['button_order']) ?></button>
            </div>
        </form>
    </div>

    <?php e($_view['widget_sets']['public_page']) ?>

<?php import('app/views/footer.php') ?>
