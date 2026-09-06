<?php /** @var array $_view */ ?>
<?php import('app/views/header.php') ?>

    <div id="order">
        <h2 class="h3 mt-4 mb-3"><?php h($_view['title']) ?></h2>
        <?php e($GLOBALS['plugin']['order']['setting']['text_order_complete']) ?>
        <p><a href="<?php t(MAIN_FILE) ?>/" class="btn btn-secondary"><?php h($GLOBALS['string']['text_goto_home']) ?></a></p>
    </div>

    <?php e($_view['widget_sets']['public_page']) ?>

<?php import('app/views/footer.php') ?>
