<?php
$customer_name = trim(($_SESSION['post']['order_record']['name_01'] ?? '') . ' ' . ($_SESSION['post']['order_record']['name_02'] ?? '')) ?: $_SESSION['post']['order_record']['email'];
?>
<?php e($GLOBALS['setting']['mail_body_begin']) ?>

<?php e($customer_name) ?> 様

この度はご注文いただき、誠にありがとうございます。
以下の内容でご注文を承りました。

- - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

【お客様】
<?php e($customer_name) ?><?php e("\n") ?>

【メールアドレス】
<?php e($_SESSION['post']['order_record']['email']) ?><?php e("\n") ?>

【ご注文内容】
<?php foreach ($_view['items'] as $item) : ?>
<?php e($item['title']) ?>（<?php e($item['spec_name']) ?>）
<?php e(number_format($item['price'])) ?>円 × <?php e($item['quantity']) ?> = <?php e(number_format($item['subtotal'])) ?>円<?php e("\n") ?>
<?php endforeach ?>
商品合計：<?php e(number_format($_view['subtotal'])) ?>円<?php e("\n") ?>
送料：<?php e(number_format($_view['delivery_cost'])) ?>円<?php e("\n") ?>
支払手数料：<?php e(number_format($_view['payment_fee'])) ?>円<?php e("\n") ?>
合計金額：<?php e(number_format($_view['total'])) ?>円<?php e("\n") ?>

【お支払い方法】
<?php e($_view['order_payment']['name']) ?><?php e("\n") ?>

<?php if ($_SESSION['post']['order_record']['provide'] === 'delivery') : ?>
【配送方法】
<?php e($_view['order_delivery']['name'] ?? '') ?><?php e("\n") ?>

【お届け先】
〒<?php e($_SESSION['post']['order_record']['zipcode']) ?><?php e("\n") ?>
<?php e($_SESSION['post']['order_record']['prefecture']) ?><?php e($_SESSION['post']['order_record']['address_01']) ?><?php e("\n") ?>
<?php e($_SESSION['post']['order_record']['address_02']) ?><?php e("\n") ?>
<?php e($customer_name) ?> 様<?php e("\n") ?>
電話番号：<?php e($_SESSION['post']['order_record']['telephone']) ?><?php e("\n") ?>

<?php endif ?>
<?php if (!empty($_SESSION['post']['order_record']['message'])) : ?>
【お問い合わせ内容】
<?php e($_SESSION['post']['order_record']['message']) ?><?php e("\n") ?>

<?php endif ?>
- - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

商品の発送・お問い合わせ対応まで、今しばらくお待ちくださいませ。

<?php e($GLOBALS['setting']['mail_body_end']) ?>
