<?php import('app/views/auth/header.php') ?>

    <main class="col-11 col-md-7 mx-auto my-4">
        <div class="mb-4 text-center">
            <h1 class="h3">
                <a href="<?php t(MAIN_FILE) ?>/auth/home"><?php h($GLOBALS['string']['heading_mypage']) ?></a>
            </h1>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header heading"><?php h($_view['title']) ?></div>
            <div class="card-body">
                <?php if (isset($_GET['ok'])) : ?>
                <div class="alert alert-success" role="alert">
                    <svg class="bi flex-shrink-0 me-2" width="24" height="24"><use xlink:href="#symbol-exclamation-triangle-fill"/></svg>
                    <?php if ($_GET['ok'] === 'post') : ?>
                    住所を登録しました。
                    <?php elseif ($_GET['ok'] === 'delete') : ?>
                    住所を削除しました。
                    <?php endif ?>
                </div>
                <?php elseif (isset($_GET['warning'])) : ?>
                <div class="alert alert-danger" role="alert">
                    <svg class="bi flex-shrink-0 me-2" width="24" height="24"><use xlink:href="#symbol-exclamation-triangle-fill"/></svg>
                    <?php if ($_GET['warning'] === 'delete') : ?>
                    削除対象が選択されていません。
                    <?php endif ?>
                </div>
                <?php endif ?>

                <p><a href="<?php t(MAIN_FILE) ?>/auth/address_form" class="btn btn-primary" role="button">住所登録</a></p>

                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th class="text-nowrap">お名前</th>
                            <th class="text-nowrap d-none d-md-table-cell">住所</th>
                            <th class="text-nowrap">作業</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th class="text-nowrap">お名前</th>
                            <th class="text-nowrap d-none d-md-table-cell">住所</th>
                            <th class="text-nowrap">作業</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        <?php foreach ($_view['order_addresses'] as $order_address) : ?>
                        <tr>
                            <td><?php h($order_address['name_01'] . ' ' . $order_address['name_02']) ?></td>
                            <td class="d-none d-md-table-cell"><?php h(truncate($order_address['prefecture'] . $order_address['address_01'], 50)) ?></td>
                            <td><a href="<?php t(MAIN_FILE) ?>/auth/address_form?id=<?php t($order_address['id']) ?>" class="btn btn-primary btn-sm text-nowrap" role="button">編集</a></td>
                        </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php e($_view['widget_sets']['auth_page']) ?>
    </main>
    <div class="my-4 text-center">
        <?php if ($GLOBALS['authority']['power'] >= 1) : ?>
        <a href="<?php t(MAIN_FILE) ?>/admin/home"><?php h($GLOBALS['string']['text_goto_admin_home']) ?></a>
        <?php else : ?>
        <a href="<?php t(MAIN_FILE) ?>/auth/home"><?php h($GLOBALS['string']['text_goto_auth_home']) ?></a>
        <?php endif ?>
    </div>

<?php import('app/views/auth/footer.php') ?>
