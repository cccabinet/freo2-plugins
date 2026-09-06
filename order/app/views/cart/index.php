<?php import('app/views/header.php') ?>

    <div id="plugin-catalog">
        <h2 class="h3 mt-4 mb-3"><?php h($GLOBALS['plugin']['order']['setting']['heading_cart']) ?></h2>
        <?php if (isset($_GET['ok'])) : ?>
        <div class="alert alert-success" role="alert">
            <svg class="bi flex-shrink-0 me-2" width="24" height="24"><use xlink:href="#symbol-exclamation-triangle-fill"/></svg>
            <?php if ($_GET['ok'] === 'add') : ?>
            カートに商品を追加しました。
            <?php elseif ($_GET['ok'] === 'edit') : ?>
            カートの数を変更しました。
            <?php elseif ($_GET['ok'] === 'remove') : ?>
            カートから商品を削除しました。
            <?php endif ?>
        </div>
        <?php endif ?>
        <?php e($GLOBALS['plugin']['order']['setting']['text_cart']) ?>
        <?php foreach (['delivery' => '配送商品', 'download' => 'ダウンロード商品'] as $provide_key => $provide_value) : ?>
        <?php if (!empty($_SESSION['cart']['delivery']) && !empty($_SESSION['cart']['download'])) : ?>
        <h3 class="h5 mt-4 mb-3"><?php h($provide_value) ?></h3>
        <?php endif ?>
        <?php if (!empty($_SESSION['cart'][$provide_key])) : ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th class="text-nowrap">規格名</th>
                    <th class="text-nowrap">商品名</th>
                    <th class="text-nowrap">数</th>
                    <th class="text-nowrap">作業</th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <th class="text-nowrap">規格名</th>
                    <th class="text-nowrap">商品名</th>
                    <th class="text-nowrap">数</th>
                    <th class="text-nowrap">作業</th>
                </tr>
            </tfoot>
            <tbody>
                <?php foreach ($_SESSION['cart'][$provide_key] as $cart) : ?>
                <tr>
                    <td><?php h($_view['order_spec_sets'][$cart['order_spec_id']]['name']) ?></td>
                    <td><?php h($_view['entry_sets'][$_view['order_spec_sets'][$cart['order_spec_id']]['entry_id']]['title']) ?></td>
                    <td>
                        <form action="<?php t(MAIN_FILE) ?>/cart/edit" method="post" class="d-flex align-items-center gap-2">
                            <input type="hidden" name="_token" value="<?php t($_view['token']) ?>" class="token">
                            <input type="hidden" name="provide" value="<?php t($provide_key) ?>">
                            <input type="hidden" name="order_spec_id" value="<?php t($cart['order_spec_id']) ?>">
                            <input type="number" name="quantity" value="<?php t($cart['quantity']) ?>" min="1" class="form-control form-control-sm" style="width: 80px;">
                            <button type="submit" class="btn btn-primary btn-sm text-nowrap">変更</button>
                        </form>
                    </td>
                    <td>
                        <form action="<?php t(MAIN_FILE) ?>/cart/remove" method="post" class="delete">
                            <input type="hidden" name="_token" value="<?php t($_view['token']) ?>" class="token">
                            <input type="hidden" name="provide" value="<?php t($provide_key) ?>">
                            <input type="hidden" name="order_spec_id" value="<?php t($cart['order_spec_id']) ?>">
                            <button type="submit" class="btn btn-danger btn-sm text-nowrap">削除</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach ?>
            </tbody>
        </table>
        <?php if (!empty($_SESSION['cart']['delivery']) && !empty($_SESSION['cart']['download'])) : ?>
        <p><a href="<?php t(MAIN_FILE) ?>/order/?provide=<?php t($provide_key) ?>" class="btn btn-primary"><?php h($GLOBALS['plugin']['order']['setting']['button_cart_order']) ?></a></p>
        <?php else : ?>
        <p><a href="<?php t(MAIN_FILE) ?>/order/" class="btn btn-primary"><?php h($GLOBALS['plugin']['order']['setting']['button_cart_order']) ?></a></p>
        <?php endif ?>
        <?php endif ?>
        <?php endforeach ?>
    </div>

<?php import('app/views/footer.php') ?>
