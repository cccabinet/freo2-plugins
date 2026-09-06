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
                <p>URLを操作するためのルールを管理します。</p>
                <p><a href="<?php t(MAIN_FILE) ?>/admin/rewrite_form" class="btn btn-primary">ルール登録</a></p>
                <?php if (isset($_GET['ok'])) : ?>
                <div class="alert alert-success">
                    <svg class="bi flex-shrink-0 me-2" width="24" height="24"><use xlink:href="#symbol-exclamation-triangle-fill"/></svg>
                    <?php if ($_GET['ok'] === 'post') : ?>
                    ルールを登録しました。
                    <?php elseif ($_GET['ok'] === 'sort') : ?>
                    ルールを並び替えました。
                    <?php elseif ($_GET['ok'] === 'delete') : ?>
                    ルールを削除しました。
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

                <form action="<?php t(MAIN_FILE) ?>/admin/rewrite_sort" method="post" id="sortable">
                    <input type="hidden" name="_token" value="<?php t($_view['token']) ?>" class="token">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th class="text-nowrap">名前</th>
                                <th class="text-nowrap">挙動</th>
                                <th class="text-nowrap">有効</th>
                                <th class="text-nowrap d-none d-md-table-cell">並び替え</th>
                                <th class="text-nowrap">作業</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th class="text-nowrap">名前</th>
                                <th class="text-nowrap">挙動</th>
                                <th class="text-nowrap">有効</th>
                                <th class="text-nowrap d-none d-md-table-cell">並び替え</th>
                                <th class="text-nowrap">作業</th>
                            </tr>
                        </tfoot>
                        <tbody>
                            <?php foreach ($_view['rewrite_rules'] as $rewrite_rule) : ?>
                            <tr id="sort_<?php h($rewrite_rule['id']) ?>">
                                <td><?php h(truncate($rewrite_rule['name'], 50)) ?></td>
                                <td class="text-nowrap d-none d-md-table-cell"><span class="badge <?php t(app_badge('kind', $rewrite_rule['type'])) ?>"><?php h($GLOBALS['plugin']['rewrite']['option']['rewrite_rule']['type'][$rewrite_rule['type']]) ?></span></td>
                                <td><span class="badge <?php t(app_badge('enabled', $rewrite_rule['enabled'])) ?>"><?php h($GLOBALS['plugin']['rewrite']['option']['rewrite_rule']['enabled'][$rewrite_rule['enabled']]) ?></span></td>
                                <td class="text-nowrap d-none d-md-table-cell"><span class="handle text-nowrap"><svg class="bi flex-shrink-0 me-1 mb-1" width="16" height="16"><use xlink:href="#symbol-arrow-down-up"/></svg></span></td>
                                <td><a href="<?php t(MAIN_FILE) ?>/admin/rewrite_form?id=<?php t($rewrite_rule['id']) ?>" class="btn btn-primary btn-sm text-nowrap">編集</a></td>
                            </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </form>
            </div>
        </div>
        <?php e($_view['widget_sets']['admin_page']) ?>
    </main>

<?php import('app/views/admin/footer.php') ?>
