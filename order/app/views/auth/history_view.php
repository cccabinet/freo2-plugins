<?php import('app/views/auth/header.php') ?>

    <main class="col-11 col-md-7 mx-auto my-4">
        <div class="mb-4 text-center">
            <h1 class="h3">
                <a href="<?php t(MAIN_FILE) ?>/auth/home"><?php h($GLOBALS['string']['heading_mypage']) ?></a>
            </h1>
        </div>

        <?php
        $total = 0;
        foreach ($_view['order_record_items'] as $order_record_item) {
            $total += $order_record_item['selling_price'] * $order_record_item['quantity'];
        }
        $total += $_view['order_record']['payment_fee'] + $_view['order_record']['delivery_cost'] - $_view['order_record']['discount'];
        ?>

        <div class="card shadow-sm mb-3">
            <div class="card-header heading"><?php h($_view['title']) ?></div>
            <div class="card-body">
                <dl class="row">
                    <dt class="col-sm-3">日時</dt>
                    <dd class="col-sm-9"><?php h(localdate('Y/m/d H:i:s', $_view['order_record']['created'])) ?></dd>
                    <dt class="col-sm-3">状況</dt>
                    <dd class="col-sm-9"><?php h($GLOBALS['plugin']['order']['option']['order_record']['status'][$_view['order_record']['status']] ?? $_view['order_record']['status']) ?></dd>
                    <dt class="col-sm-3">支払方法</dt>
                    <dd class="col-sm-9"><?php h($_view['order_payment']['name'] ?? '') ?></dd>
                    <dt class="col-sm-3">配送方法</dt>
                    <dd class="col-sm-9"><?php h($_view['order_delivery']['name'] ?? '') ?></dd>
                    <dt class="col-sm-3">メールアドレス</dt>
                    <dd class="col-sm-9"><?php h($_view['order_record']['email']) ?></dd>
                    <?php if (!empty($_view['order_record']['address_01'])) : ?>
                    <dt class="col-sm-3">お届け先</dt>
                    <dd class="col-sm-9">
                        <?php h($_view['order_record']['name_01'] . ' ' . $_view['order_record']['name_02']) ?><br>
                        〒<?php h($_view['order_record']['zipcode']) ?> <?php h($_view['order_record']['prefecture'] . $_view['order_record']['address_01'] . ' ' . ($_view['order_record']['address_02'] ?? '')) ?><br>
                        <?php h($_view['order_record']['telephone']) ?>
                    </dd>
                    <?php endif ?>
                    <?php if (!empty($_view['order_record']['message'])) : ?>
                    <dt class="col-sm-3">お問い合わせ内容</dt>
                    <dd class="col-sm-9"><?php h($_view['order_record']['message']) ?></dd>
                    <?php endif ?>
                </dl>

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

                <dl class="row">
                    <dt class="col-sm-3">支払手数料</dt>
                    <dd class="col-sm-9"><?php h(number_format($_view['order_record']['payment_fee'])) ?>円</dd>
                    <dt class="col-sm-3">送料</dt>
                    <dd class="col-sm-9"><?php h(number_format($_view['order_record']['delivery_cost'])) ?>円</dd>
                    <dt class="col-sm-3">値引き額</dt>
                    <dd class="col-sm-9"><?php h(number_format($_view['order_record']['discount'])) ?>円</dd>
                    <dt class="col-sm-3 fw-bold">合計金額</dt>
                    <dd class="col-sm-9 fw-bold"><?php h(number_format($total)) ?>円</dd>
                </dl>
            </div>
        </div>
        <?php e($_view['widget_sets']['auth_page']) ?>
    </main>
    <div class="my-4 text-center">
        <a href="<?php t(MAIN_FILE) ?>/auth/history">注文履歴に戻る</a>
    </div>

<?php import('app/views/auth/footer.php') ?>
