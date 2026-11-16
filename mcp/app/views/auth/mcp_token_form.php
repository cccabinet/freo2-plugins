<?php import('app/views/auth/header.php') ?>

    <main class="col-11 col-md-7 mx-auto my-4">
        <div class="mb-4 text-center">
            <h1 class="h3">
                <a href="<?php t(MAIN_FILE) ?>/auth/home"><?php h($GLOBALS['string']['heading_user']) ?></a>
            </h1>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header heading"><?php h($_view['title']) ?></div>
            <div class="card-body">
                <?php if (isset($_view['warnings'])) : ?>
                <div class="alert alert-danger">
                    <svg class="bi flex-shrink-0 me-2" width="24" height="24"><use xlink:href="#symbol-exclamation-triangle-fill"/></svg>
                    <?php foreach ($_view['warnings'] as $warning) : ?>
                    <?php h($warning) ?>
                    <?php endforeach ?>
                </div>
                <?php endif ?>

                <form action="<?php t(MAIN_FILE) ?>/auth/mcp_token_form" method="post" class="register validate">
                    <input type="hidden" name="_token" value="<?php t($_view['token']) ?>" class="token">
                    <input type="hidden" name="id" value="<?php t($_view['mcp_token']['id']) ?>">
                    <div class="form-group mb-2">
                        <label class="fw-bold">名前 <span class="badge bg-danger">必須</span> <span class="badge text-light bg-secondary" data-toggle="tooltip" title="どこで使うトークンかの控え。例: 自分のPCのClaude Code">？</span></label>
                        <input type="text" name="name" size="30" value="<?php t($_view['mcp_token']['name']) ?>" class="form-control">
                    </div>
                    <?php if (empty($_view['mcp_token']['id'])) : ?>
                    <div class="form-group mb-2">
                        <label class="fw-bold">範囲 <span class="badge bg-danger">必須</span></label>
                        <select name="scope" class="form-select" style="width: 300px;">
                            <?php foreach ($GLOBALS['plugin']['mcp']['option']['mcp_token']['scope'] as $key => $value) : ?>
                            <option value="<?php t($key) ?>"<?php $key == $_view['mcp_token']['scope'] ? e(' selected="selected"') : '' ?>><?php t($value) ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <?php else : ?>
                    <div class="form-group mb-2">
                        <label class="fw-bold">範囲</label>
                        <p class="mb-1"><?php h($GLOBALS['plugin']['mcp']['option']['mcp_token']['scope'][$_view['mcp_token']['scope']]) ?></p>
                        <p class="form-text mt-0">範囲は変更できません。変えるときは、このトークンを削除して発行し直してください。</p>
                    </div>
                    <?php endif ?>
                    <?php if ($_view['mcp_token_expire_editable']) : ?>
                    <div class="form-group mb-2">
                        <label class="fw-bold">有効期限 <span class="badge text-light bg-secondary" data-toggle="tooltip" title="この日の終わりまで使えます。空欄なら無期限です。">？</span></label>
                        <input type="date" name="expire" value="<?php t($_view['mcp_token']['expire'] === null ? '' : substr($_view['mcp_token']['expire'], 0, 10)) ?>" class="form-control" style="width: 200px;">
                    </div>
                    <?php else : ?>
                    <div class="form-group mb-2">
                        <label class="fw-bold">有効期限</label>
                        <p class="mb-1"><?php h($_view['mcp_token']['expire'] === null ? '無期限' : localdate('Y/m/d', $_view['mcp_token']['expire'])) ?></p>
                        <p class="form-text mt-0">ほかのユーザーのトークンは、名前だけを変更できます。</p>
                    </div>
                    <?php endif ?>
                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary px-4"><?php if (empty($_view['mcp_token']['id'])) : ?>発行<?php else : ?>編集<?php endif ?></button>
                    </div>
                </form>
            </div>
        </div>

        <?php if (!empty($_view['mcp_token']['id'])) : ?>
        <div class="card shadow-sm mb-3">
            <div class="card-header">削除</div>
            <div class="card-body">
                <form action="<?php t(MAIN_FILE) ?>/auth/mcp_token_delete" method="post" class="delete">
                    <input type="hidden" name="_token" value="<?php t($_view['token']) ?>" class="token">
                    <input type="hidden" name="id" value="<?php t($_view['mcp_token']['id']) ?>">
                    <p>削除したトークンでは、AIから接続できなくなります。</p>
                    <div class="form-group">
                        <button type="submit" class="btn btn-danger px-4">削除</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endif ?>
        <?php e($_view['widget_sets']['auth_page']) ?>
    </main>
    <div class="my-4 text-center">
        <a href="<?php t(MAIN_FILE) ?>/auth/mcp_token">MCPトークン一覧に戻る</a>
    </div>

<?php import('app/views/auth/footer.php') ?>
