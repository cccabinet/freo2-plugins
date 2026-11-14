<?php import('app/views/admin/header.php') ?>

    <main class="col-md-9 ms-sm-auto col-lg-10 mb-2 px-md-4">
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 mb-2">
            <h2 class="h3">
                <svg class="bi flex-shrink-0 me-1 mb-1" width="24" height="24"><use xlink:href="#symbol-list-ul"/></svg>
                認証
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
                <p>ClaudeなどのAIから、MCPでコンテンツを操作するためのトークンを管理します。<?php if ($GLOBALS['authority']['power'] < 3) : ?>表示されるのは、自分が発行したトークンだけです。<?php endif ?></p>
                <!-- <p>受け口のURL: <code><?php h($_view['mcp_url']) ?></code></p> -->
                <p><a href="<?php t(MAIN_FILE) ?>/admin/mcp_token_form" class="btn btn-primary">MCPトークン発行</a></p>
                <?php if (isset($_GET['ok'])) : ?>
                <div class="alert alert-success">
                    <svg class="bi flex-shrink-0 me-2" width="24" height="24"><use xlink:href="#symbol-exclamation-triangle-fill"/></svg>
                    <?php if ($_GET['ok'] === 'post') : ?>
                    MCPトークンを発行しました。
                    <?php elseif ($_GET['ok'] === 'edit') : ?>
                    MCPトークンを編集しました。
                    <?php elseif ($_GET['ok'] === 'delete') : ?>
                    MCPトークンを削除しました。
                    <?php endif ?>
                </div>
                <?php elseif (isset($_GET['warning'])) : ?>
                <div class="alert alert-danger">
                    <svg class="bi flex-shrink-0 me-2" width="24" height="24"><use xlink:href="#symbol-exclamation-triangle-fill"/></svg>
                    <?php if ($_GET['warning'] === 'delete') : ?>
                    削除する対象が選択されていません。
                    <?php endif ?>
                </div>
                <?php endif ?>

                <?php if ($_view['mcp_token_issued'] !== null) : ?>
                <div class="card border-warning mb-3">
                    <div class="card-header">発行したトークン</div>
                    <div class="card-body">
                        <p class="text-danger fw-bold">トークンは、この画面でしか表示されません。画面を閉じる前に控えてください。</p>
                        <input type="text" value="<?php t($_view['mcp_token_issued']) ?>" class="form-control mb-3 font-monospace mcp-token-issued" readonly>
                        <p class="mb-1">接続先のURL（方式はStreamable HTTP）:</p>
                        <pre class="bg-light p-2 mb-3"><code><?php h($_view['mcp_url']) ?></code></pre>
                        <p class="mb-1">リクエストヘッダー:</p>
                        <pre class="bg-light p-2 mb-3"><code>Authorization: Bearer <?php h($_view['mcp_token_issued']) ?></code></pre>
                        <p class="mb-1">Claude Codeから使う例:</p>
                        <pre class="bg-light p-2 mb-0"><code>claude mcp add --transport http --scope user freo2 <?php h($_view['mcp_url']) ?> --header "Authorization: Bearer <?php h($_view['mcp_token_issued']) ?>"</code></pre>
                    </div>
                </div>
                <?php endif ?>

                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th class="text-nowrap">名前</th>
                            <th class="text-nowrap d-none d-md-table-cell">ユーザー</th>
                            <th class="text-nowrap">範囲</th>
                            <th class="text-nowrap d-none d-md-table-cell">有効期限</th>
                            <th class="text-nowrap d-none d-md-table-cell">最終利用日時</th>
                            <th class="text-nowrap">作業</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <th class="text-nowrap">名前</th>
                            <th class="text-nowrap d-none d-md-table-cell">ユーザー</th>
                            <th class="text-nowrap">範囲</th>
                            <th class="text-nowrap d-none d-md-table-cell">有効期限</th>
                            <th class="text-nowrap d-none d-md-table-cell">最終利用日時</th>
                            <th class="text-nowrap">作業</th>
                        </tr>
                    </tfoot>
                    <tbody>
                        <?php foreach ($_view['mcp_tokens'] as $mcp_token) : ?>
                        <tr>
                            <td><?php h(truncate($mcp_token['name'], 50)) ?></td>
                            <td class="d-none d-md-table-cell"><?php h(isset($_view['users'][$mcp_token['user_id']]) ? $_view['users'][$mcp_token['user_id']] : '') ?></td>
                            <td><span class="badge <?php t(app_badge('scope', $mcp_token['scope'])) ?>"><?php h($GLOBALS['plugin']['mcp']['option']['mcp_token']['scope'][$mcp_token['scope']]) ?></span></td>
                            <td class="d-none d-md-table-cell">
                                <?php if ($mcp_token['expire'] === null) : ?>
                                無期限
                                <?php elseif ($mcp_token['expire'] <= localdate('Y-m-d H:i:s')) : ?>
                                <span class="text-danger"><?php h(localdate('Y/m/d', $mcp_token['expire'])) ?>（期限切れ）</span>
                                <?php else : ?>
                                <?php h(localdate('Y/m/d', $mcp_token['expire'])) ?>
                                <?php endif ?>
                            </td>
                            <td class="d-none d-md-table-cell"><?php h($mcp_token['accessed'] === null ? '未使用' : localdate('Y/m/d H:i', $mcp_token['accessed'])) ?></td>
                            <td><a href="<?php t(MAIN_FILE) ?>/admin/mcp_token_form?id=<?php t($mcp_token['id']) ?>" class="btn btn-primary btn-sm text-nowrap">編集</a></td>
                        </tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php e($_view['widget_sets']['admin_page']) ?>
    </main>

<?php import('app/views/admin/footer.php') ?>
