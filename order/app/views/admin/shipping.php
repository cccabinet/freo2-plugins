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
                    <li class="breadcrumb-item"><a href="<?php t(MAIN_FILE) ?>/admin/order">注文管理</a></li>
                    <li class="breadcrumb-item active"><?php h($_view['title']) ?></li>
                </ol>
            </nav>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header heading">注文</div>
            <div class="card-body">
                <?php if ($_view['shipping_delivery_cost_total'] > $_view['order_record']['delivery_cost']) : ?>
                <div class="alert alert-warning" role="alert">
                    <svg class="bi flex-shrink-0 me-2" width="24" height="24"><use xlink:href="#symbol-exclamation-triangle-fill"/></svg>
                    実際の送料が、注文時点の送料を超過しています。
                </div>
                <?php endif ?>
                <p>
                    注文番号 <?php h($_view['order_record']['id']) ?>：
                    <?php h(trim(($_view['order_record']['name_01'] ?? '') . ' ' . ($_view['order_record']['name_02'] ?? '')) ?: $_view['order_record']['email']) ?>
                    （<a href="<?php t(MAIN_FILE) ?>/admin/order_form?id=<?php t($_view['order_record']['id']) ?>">注文編集へ</a>）
                </p>
                <dl class="row mb-0">
                    <dt class="col-sm-3">送料（注文時点）</dt>
                    <dd class="col-sm-9"><?php h(number_format($_view['order_record']['delivery_cost'])) ?>円</dd>
                    <dt class="col-sm-3">送料（発送分の合計）</dt>
                    <dd class="col-sm-9 mb-0"><?php h(number_format($_view['shipping_delivery_cost_total'])) ?>円</dd>
                </dl>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header heading">発送状況</div>
            <div class="card-body">
                <?php
                $has_over_shipped = false;
                foreach ($_view['order_record_items'] as $order_record_item) {
                    $shipped = $_view['shipped_quantities'][$order_record_item['id']] ?? 0;
                    if ($shipped > $order_record_item['quantity']) {
                        $has_over_shipped = true;
                        break;
                    }
                }
                ?>
                <?php if ($has_over_shipped) : ?>
                <div class="alert alert-warning" role="alert">
                    <svg class="bi flex-shrink-0 me-2" width="24" height="24"><use xlink:href="#symbol-exclamation-triangle-fill"/></svg>
                    発送数が注文数を超えている商品があります。
                </div>
                <?php endif ?>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th class="text-nowrap">商品名</th>
                            <th class="text-nowrap">規格名</th>
                            <th class="text-nowrap text-end">注文数</th>
                            <th class="text-nowrap text-end">発送数</th>
                            <th class="text-nowrap text-end">残り</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($_view['order_record_items'] as $order_record_item) : ?>
                        <?php $shipped = $_view['shipped_quantities'][$order_record_item['id']] ?? 0 ?>
                        <?php $remained = $order_record_item['quantity'] - $shipped ?>
                        <tr<?php $remained < 0 ? e(' class="table-warning"') : '' ?>>
                            <td><?php h($_view['order_spec_sets'][$order_record_item['spec_id']]['entry_title'] ?? $order_record_item['name']) ?></td>
                            <td><?php h($_view['order_spec_sets'][$order_record_item['spec_id']]['name'] ?? '') ?></td>
                            <td class="text-end"><?php h(number_format($order_record_item['quantity'])) ?></td>
                            <td class="text-end"><?php h(number_format($shipped)) ?></td>
                            <td class="text-end"><?php h(number_format($remained)) ?></td>
                        </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header heading">発送履歴</div>
            <div class="card-body">
                <p><a href="<?php t(MAIN_FILE) ?>/admin/shipping_form?record_id=<?php t($_GET['record_id']) ?>" class="btn btn-primary">発送登録</a></p>
                <?php if (isset($_GET['ok'])) : ?>
                <div class="alert alert-success" role="alert">
                    <svg class="bi flex-shrink-0 me-2" width="24" height="24"><use xlink:href="#symbol-exclamation-triangle-fill"/></svg>
                    <?php if ($_GET['ok'] === 'post') : ?>
                    発送を登録しました。
                    <?php elseif ($_GET['ok'] === 'delete') : ?>
                    発送を削除しました。
                    <?php endif ?>
                </div>
                <?php endif ?>

                <?php
                $shipping_status_badges = [
                    'preparing' => 'text-bg-warning',
                    'completed' => 'text-bg-success',
                    'failed'    => 'text-bg-danger',
                    'returned'  => 'text-bg-secondary',
                ];
                ?>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th class="text-nowrap">発送日</th>
                            <th class="text-nowrap d-none d-md-table-cell">配送方法</th>
                            <th class="text-nowrap">送料</th>
                            <th class="text-nowrap">状況</th>
                            <th class="text-nowrap">作業</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th class="text-nowrap">発送日</th>
                            <th class="text-nowrap d-none d-md-table-cell">配送方法</th>
                            <th class="text-nowrap">送料</th>
                            <th class="text-nowrap">状況</th>
                            <th class="text-nowrap">作業</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        <?php foreach ($_view['order_shippings'] as $order_shipping) : ?>
                        <tr>
                            <td class="text-nowrap"><?php h($order_shipping['shipping_date']) ?></td>
                            <td class="d-none d-md-table-cell"><?php h($_view['order_delivery_sets'][$order_shipping['delivery_id']]['name'] ?? '') ?></td>
                            <td class="text-end"><?php if ($order_shipping['delivery_cost'] !== null) : ?><?php h(number_format($order_shipping['delivery_cost'])) ?>円<?php endif ?></td>
                            <td class="text-nowrap"><span class="badge <?php t($shipping_status_badges[$order_shipping['status']] ?? 'text-bg-secondary') ?>"><?php h($GLOBALS['plugin']['order']['option']['order_shippings']['status'][$order_shipping['status']] ?? $order_shipping['status']) ?></span></td>
                            <td><a href="<?php t(MAIN_FILE) ?>/admin/shipping_form?record_id=<?php t($_GET['record_id']) ?>&amp;id=<?php t($order_shipping['id']) ?>" class="btn btn-primary btn-sm text-nowrap">編集</a></td>
                        </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php e($_view['widget_sets']['admin_page']) ?>
    </main>

<?php import('app/views/admin/footer.php') ?>
