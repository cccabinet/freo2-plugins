<?php

import('plugins/mcp/app/services/mcp.php');

// エラーをHTMLではなくJSONで返す
$_REQUEST['_type'] = 'json';

// セッションは使わない（AIのクライアントはクッキーを返さないので、リクエストのたびにセッションが残らないようにする）
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}
header_remove('Set-Cookie');
$_SESSION = [];

// POSTだけを受ける（SSEのGETと、セッションを終えるDELETEは使わない）
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    service_mcp_respond(405, null, ['Allow: POST']);
}

$headers = service_mcp_headers();

// アクセス元（ブラウザから、ほかのサイトを経由して送らせない）
if (!service_mcp_origin($headers, $GLOBALS['config']['http_url'])) {
    service_mcp_respond(403, service_mcp_error(null, -32600, 'Forbidden')['body']);
}

// トークンの認証
$auth = service_mcp_authenticate(service_mcp_bearer($headers));
if ($auth === null) {
    service_mcp_respond(401, service_mcp_error(null, -32001, 'Unauthorized')['body'], ['WWW-Authenticate: Bearer']);
}

// 操作ログ・登録者に使うユーザーと権限（画面からログインしたときと同じ形にする）
$_SESSION['auth']['user'] = [
    'id'   => $auth['user']['id'],
    'time' => localdate(),
];
$GLOBALS['authority'] = $auth['authority'];

// メッセージを解析
$message = json_decode(file_get_contents('php://input'), true);
if ($message === null) {
    service_mcp_respond(400, service_mcp_error(null, -32700, 'Parse error')['body']);
}

// 応答の前に error() などで終了したときは、JSON-RPCのエラーにする
service_mcp_guard(is_array($message) && isset($message['id']) ? $message['id'] : null);

// 処理
$response = service_mcp_handle($message, $headers, $auth);

service_mcp_respond($response['status'], $response['body']);
