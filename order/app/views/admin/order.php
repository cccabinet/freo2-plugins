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
                    <li class="breadcrumb-item active"><?php h($_view['title']) ?></li>
                </ol>
            </nav>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header heading"><?php h($_view['title']) ?></div>
            <div class="card-body">
                <p>注文を管理します。</p>
                <p>
                    <a href="<?php t(MAIN_FILE) ?>/admin/order_form" class="btn btn-primary" role="button">注文登録</a>
                    <?php if ($GLOBALS['plugin']['order']['setting']['use_direct']) : ?>
                    <a href="<?php t(MAIN_FILE) ?>/admin/order_form?provide=direct" class="btn btn-primary" role="button">注文登録（対面）</a>
                    <?php endif ?>
                    <a href="<?php t(MAIN_FILE) ?>/admin/payment" class="btn btn-primary" role="button">支払方法管理</a>
                    <a href="<?php t(MAIN_FILE) ?>/admin/delivery" class="btn btn-primary" role="button">配送方法管理</a>
                </p>
                <?php if (isset($_GET['ok'])) : ?>
                <div class="alert alert-success">
                    <svg class="bi flex-shrink-0 me-2" width="24" height="24"><use xlink:href="#symbol-exclamation-triangle-fill"/></svg>
                    <?php if ($_GET['ok'] === 'post') : ?>
                    注文を登録しました。
                    <?php elseif ($_GET['ok'] === 'delete') : ?>
                    注文を削除しました。
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

                <?php
                $status_badges = [
                    'order'     => 'text-bg-info',
                    'pending'   => 'text-bg-warning',
                    'paid'      => 'text-bg-warning',
                    'shipping'  => 'text-bg-warning',
                    'shipped'   => 'text-bg-success',
                    'provided'  => 'text-bg-success',
                    'completed' => 'text-bg-success',
                    'returned'  => 'text-bg-danger',
                    'cancelled' => 'text-bg-secondary',
                    'hold'      => 'text-bg-warning',
                ];
                ?>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th class="text-nowrap">注文番号</th>
                            <th class="text-nowrap">日時</th>
                            <th class="text-nowrap d-none d-md-table-cell">お名前</th>
                            <th class="text-nowrap d-none d-md-table-cell">提供方法</th>
                            <th class="text-nowrap d-none d-md-table-cell">支払方法</th>
                            <th class="text-nowrap d-none d-md-table-cell">合計金額</th>
                            <th class="text-nowrap">状況</th>
                            <th class="text-nowrap">作業</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th class="text-nowrap">注文番号</th>
                            <th class="text-nowrap">日時</th>
                            <th class="text-nowrap d-none d-md-table-cell">お名前</th>
                            <th class="text-nowrap d-none d-md-table-cell">提供方法</th>
                            <th class="text-nowrap d-none d-md-table-cell">支払方法</th>
                            <th class="text-nowrap d-none d-md-table-cell">合計金額</th>
                            <th class="text-nowrap">状況</th>
                            <th class="text-nowrap">作業</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        <?php foreach ($_view['order_records'] as $order_record) : ?>
                        <?php $total = ($_view['order_record_item_totals'][$order_record['id']] ?? 0) + $order_record['payment_fee'] + $order_record['delivery_cost'] - $order_record['discount'] ?>
                        <?php
                        $display_name = trim(($order_record['name_01'] ?? '') . ' ' . ($order_record['name_02'] ?? ''));
                        if ($display_name === '') {
                            if (!empty($order_record['user_id']) && !empty($_view['user_sets'][$order_record['user_id']])) {
                                $display_name = $_view['user_sets'][$order_record['user_id']]['name'];
                            } elseif (!empty($order_record['email'])) {
                                $display_name = $order_record['email'];
                            }
                        }
                        ?>
                        <tr>
                            <td class="text-nowrap"><?php h($order_record['id']) ?></td>
                            <td class="text-nowrap"><?php h(localdate('Ymd', $order_record['created']) == localdate('Ymd') ? localdate('H:i:s', $order_record['created']) : localdate('Y/m/d', $order_record['created'])) ?></td>
                            <td class="d-none d-md-table-cell"><?php if (!empty($order_record['user_id'])) : ?><a href="<?php t(MAIN_FILE) ?>/admin/user_form?id=<?php t($order_record['user_id']) ?>"><?php h(truncate($display_name !== '' ? $display_name : '-', 50)) ?></a><?php else : ?><?php h(truncate($display_name !== '' ? $display_name : '-', 50)) ?><?php endif ?></td>
                            <td class="d-none d-md-table-cell"><?php h($GLOBALS['plugin']['order']['option']['order_spec']['provide'][$order_record['provide']] ?? $order_record['provide']) ?></td>
                            <td class="d-none d-md-table-cell"><?php h($_view['order_payment_sets'][$order_record['payment_id']]['name'] ?? '') ?></td>
                            <td class="text-end d-none d-md-table-cell"><?php h(number_format($total)) ?>円</td>
                            <td class="text-nowrap"><span class="badge <?php t($status_badges[$order_record['status']] ?? 'text-bg-secondary') ?>"><?php h($GLOBALS['plugin']['order']['option']['order_record']['status'][$order_record['status']] ?? $order_record['status']) ?></span></td>
                            <td>
                                <?php if ($GLOBALS['plugin']['order']['setting']['use_shipping'] && $order_record['provide'] === 'delivery') : ?>
                                <a href="<?php t(MAIN_FILE) ?>/admin/shipping?record_id=<?php t($order_record['id']) ?>" class="btn btn-primary btn-sm text-nowrap" role="button">発送</a>
                                <?php endif ?>
                                <a href="<?php t(MAIN_FILE) ?>/admin/order_form?id=<?php t($order_record['id']) ?>" class="btn btn-primary btn-sm text-nowrap" role="button">編集</a>
                            </td>
                        </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
                <?php if ($_view['order_record_page'] > 1) : ?>
                    <ul class="pagination d-flex justify-content-end">
                        <li class="page-item"><a href="<?php t(MAIN_FILE) ?>/admin/order?page=1" class="page-link">&laquo;</a></li>
                        <?php for ($i = max(1, $_GET['page'] - floor($GLOBALS['plugin']['order']['setting']['number_width_admin_order'] / 2)); $i <= min($_view['order_record_page'], $_GET['page'] + floor($GLOBALS['plugin']['order']['setting']['number_width_admin_order'] / 2)); $i++) : ?>
                        <li class="page-item<?php if ($i == $_GET['page']) : ?> active<?php endif ?>"><a href="<?php t(MAIN_FILE) ?>/admin/order?page=<?php t($i) ?>" class="page-link"><?php t($i) ?></a></li>
                        <?php endfor ?>
                        <li class="page-item"><a href="<?php t(MAIN_FILE) ?>/admin/order?page=<?php t($_view['order_record_page']) ?>" class="page-link">&raquo;</a></li>
                    </ul>
                <?php endif ?>
            </div>
        </div>
        <?php e($_view['widget_sets']['admin_page']) ?>
    </main>

<?php import('app/views/admin/footer.php') ?>
