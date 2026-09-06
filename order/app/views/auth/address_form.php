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
                <?php if (isset($_view['warnings'])) : ?>
                <div class="alert alert-danger">
                    <svg class="bi flex-shrink-0 me-2" width="24" height="24"><use xlink:href="#symbol-exclamation-triangle-fill"/></svg>
                    <?php foreach ($_view['warnings'] as $warning) : ?>
                    <?php h($warning) ?>
                    <?php endforeach ?>
                </div>
                <?php endif ?>

                <form action="<?php t(MAIN_FILE) ?>/auth/address_form<?php $_view['order_address']['id'] ? t('?id=' . $_view['order_address']['id']) : '' ?>" method="post" class="register validate">
                    <input type="hidden" name="_token" value="<?php t($_view['token']) ?>" class="token">
                    <input type="hidden" name="id" value="<?php t($_view['order_address']['id']) ?>">
                    <div class="form-group mb-2">
                        <label class="fw-bold">名前 姓 <span class="badge text-bg-danger">必須</span></label>
                        <input type="text" name="name_01" value="<?php t($_view['order_address']['name_01']) ?>" class="form-control">
                    </div>
                    <div class="form-group mb-2">
                        <label class="fw-bold">名前 名 <span class="badge text-bg-danger">必須</span></label>
                        <input type="text" name="name_02" value="<?php t($_view['order_address']['name_02']) ?>" class="form-control">
                    </div>
                    <div class="form-group mb-2">
                        <label class="fw-bold">カナ 姓</label>
                        <input type="text" name="kana_01" value="<?php t($_view['order_address']['kana_01']) ?>" class="form-control">
                    </div>
                    <div class="form-group mb-2">
                        <label class="fw-bold">カナ 名</label>
                        <input type="text" name="kana_02" value="<?php t($_view['order_address']['kana_02']) ?>" class="form-control">
                    </div>
                    <div class="form-group mb-2">
                        <label class="fw-bold">郵便番号 <span class="badge text-bg-danger">必須</span></label>
                        <input type="text" name="zipcode" value="<?php t($_view['order_address']['zipcode']) ?>" class="form-control" style="width: 200px;">
                    </div>
                    <div class="form-group mb-2">
                        <label class="fw-bold">都道府県 <span class="badge text-bg-danger">必須</span></label>
                        <input type="text" name="prefecture" value="<?php t($_view['order_address']['prefecture']) ?>" class="form-control" style="width: 200px;">
                    </div>
                    <div class="form-group mb-2">
                        <label class="fw-bold">住所 1 <span class="badge text-bg-danger">必須</span></label>
                        <input type="text" name="address_01" value="<?php t($_view['order_address']['address_01']) ?>" class="form-control">
                    </div>
                    <div class="form-group mb-2">
                        <label class="fw-bold">住所 2</label>
                        <input type="text" name="address_02" value="<?php t($_view['order_address']['address_02']) ?>" class="form-control">
                    </div>
                    <div class="form-group mb-2">
                        <label class="fw-bold">電話番号 <span class="badge text-bg-danger">必須</span></label>
                        <input type="text" name="telephone" value="<?php t($_view['order_address']['telephone']) ?>" class="form-control" style="width: 200px;">
                    </div>
                    <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary px-4">登録</button>
                    </div>
                </form>
            </div>
        </div>

        <?php if (!empty($_GET['id'])) : ?>
        <div class="card shadow-sm mb-3">
            <div class="card-header">
                削除
            </div>
            <div class="card-body">
                <form action="<?php t(MAIN_FILE) ?>/auth/address_delete" method="post" class="delete">
                    <input type="hidden" name="_token" value="<?php t($_view['token']) ?>" class="token">
                    <input type="hidden" name="id" value="<?php t($_view['order_address']['id']) ?>">
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
        <a href="<?php t(MAIN_FILE) ?>/auth/address">住所一覧に戻る</a>
    </div>

<?php import('app/views/auth/footer.php') ?>
