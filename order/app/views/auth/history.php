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
                            <th class="text-nowrap">日時</th>
                            <th class="text-nowrap d-none d-md-table-cell">支払方法</th>
                            <th class="text-nowrap d-none d-md-table-cell">配送方法</th>
                            <th class="text-nowrap">合計金額</th>
                            <th class="text-nowrap">状況</th>
                            <th class="text-nowrap">作業</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th class="text-nowrap">日時</th>
                            <th class="text-nowrap d-none d-md-table-cell">支払方法</th>
                            <th class="text-nowrap d-none d-md-table-cell">配送方法</th>
                            <th class="text-nowrap">合計金額</th>
                            <th class="text-nowrap">状況</th>
                            <th class="text-nowrap">作業</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        <?php foreach ($_view['order_records'] as $order_record) : ?>
                        <?php $total = ($_view['order_record_item_totals'][$order_record['id']] ?? 0) + $order_record['payment_fee'] + $order_record['delivery_cost'] - $order_record['discount'] ?>
                        <tr>
                            <td class="text-nowrap"><?php h(localdate('Y/m/d', $order_record['created'])) ?></td>
                            <td class="d-none d-md-table-cell"><?php h($_view['order_payment_sets'][$order_record['payment_id']]['name'] ?? '') ?></td>
                            <td class="d-none d-md-table-cell"><?php h($_view['order_delivery_sets'][$order_record['delivery_id']]['name'] ?? '') ?></td>
                            <td class="text-end"><?php h(number_format($total)) ?>円</td>
                            <td class="text-nowrap"><span class="badge <?php t($status_badges[$order_record['status']] ?? 'text-bg-secondary') ?>"><?php h($GLOBALS['plugin']['order']['option']['order_record']['status'][$order_record['status']] ?? $order_record['status']) ?></span></td>
                            <td><a href="<?php t(MAIN_FILE) ?>/auth/history_view?id=<?php t($order_record['id']) ?>" class="btn btn-primary btn-sm text-nowrap">表示</a></td>
                        </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
                <?php if ($_view['history_page'] > 1) : ?>
                    <ul class="pagination d-flex justify-content-end">
                        <li class="page-item"><a href="<?php t(MAIN_FILE) ?>/auth/history?page=1" class="page-link">&laquo;</a></li>
                        <?php for ($i = max(1, $_GET['page'] - floor($_view['history_width'] / 2)); $i <= min($_view['history_page'], $_GET['page'] + floor($_view['history_width'] / 2)); $i++) : ?>
                        <li class="page-item<?php if ($i == $_GET['page']) : ?> active<?php endif ?>"><a href="<?php t(MAIN_FILE) ?>/auth/history?page=<?php t($i) ?>" class="page-link"><?php t($i) ?></a></li>
                        <?php endfor ?>
                        <li class="page-item"><a href="<?php t(MAIN_FILE) ?>/auth/history?page=<?php t($_view['history_page']) ?>" class="page-link">&raquo;</a></li>
                    </ul>
                <?php endif ?>
            </div>
        </div>
        <?php e($_view['widget_sets']['auth_page']) ?>
    </main>
    <div class="my-4 text-center">
        <a href="<?php t(MAIN_FILE) ?>/auth/home"><?php h($GLOBALS['string']['text_goto_auth_home']) ?></a>
    </div>

<?php import('app/views/auth/footer.php') ?>
