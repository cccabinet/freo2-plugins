<?php

// 設定ファイルを読み込み
import('app/config.php');
import('plugins/mcp/config.php');

// コードカバレッジの記録を開始
if (!isset($_GET['_test'])) {
    service('coverage.php');
    service_coverage_start();
}

// ライブラリを読み込み（プラグインのモデルとサービスは自動で読み込まれない）
model('entries.php');
model('fields.php');
model('field_sets.php');
model('categories.php');
model('category_sets.php');
model('attribute_sets.php');
model('attributes.php');
model('comments.php');
model('menus.php');
model('widgets.php');
model('logs.php');
model('types.php');
model('users.php');
model('authorities.php');
import('app/setting.php');
import('libs/modules/directory.php');
import('plugins/mcp/app/models/mcp_tokens.php');
import('plugins/mcp/app/services/mcp.php');

// リクエスト情報を用意
$_SERVER['REMOTE_ADDR']     = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'freo/2';

// 下書きが参照する設定を固定する（設定によって結果が変わらないようにする）
$setting_backup = [];
$setting_values = [
    'title'               => 'テストサイト',
    'description'         => 'テストの概要',
    'entry_text_type'     => 'wysiwyg',
    'entry_default_code'  => 'YmdHis',
    'entry_use_approve'   => 0,
    'page_text_type'      => 'html',
    'page_default_code'   => '',
    'page_use_approve'    => 1,
    'entry_use_pictures'  => 1,
    'entry_use_thumbnail' => 1,
    'page_use_pictures'   => 0,
    'page_use_thumbnail'  => 0,
];
foreach ($setting_values as $key => $value) {
    $setting_backup[$key]    = isset($GLOBALS['setting'][$key]) ? $GLOBALS['setting'][$key] : null;
    $GLOBALS['setting'][$key] = $value;
}

// 既存データ削除（ツールが中でトランザクションを使うので、テスト全体はトランザクションで囲まず、最後にも削除する）
db_query('TRUNCATE TABLE ' . DATABASE_PREFIX . 'mcp_tokens;');
db_query('TRUNCATE TABLE ' . DATABASE_PREFIX . 'entries;');
db_query('TRUNCATE TABLE ' . DATABASE_PREFIX . 'fields;');
db_query('TRUNCATE TABLE ' . DATABASE_PREFIX . 'field_sets;');
db_query('TRUNCATE TABLE ' . DATABASE_PREFIX . 'categories;');
db_query('TRUNCATE TABLE ' . DATABASE_PREFIX . 'category_sets;');
db_query('TRUNCATE TABLE ' . DATABASE_PREFIX . 'attribute_sets;');
db_query('TRUNCATE TABLE ' . DATABASE_PREFIX . 'attributes;');
db_query('TRUNCATE TABLE ' . DATABASE_PREFIX . 'comments;');
db_query('TRUNCATE TABLE ' . DATABASE_PREFIX . 'menus;');
db_query('TRUNCATE TABLE ' . DATABASE_PREFIX . 'logs;');

// 認証の結果（ツールは範囲と権限だけを見る。投稿者と管理者）
$auth_read  = [
    'token'     => [
        'scope' => 'read',
    ],
    'authority' => [
        'power' => 2,
    ],
];
$auth_draft = [
    'token'     => [
        'scope' => 'draft',
    ],
    'authority' => [
        'power' => 2,
    ],
];
$auth_admin = [
    'token'     => [
        'scope' => 'read',
    ],
    'authority' => [
        'power' => 3,
    ],
];

// 2026-07-28 の版のリクエスト
$modern_version = $GLOBALS['plugin']['mcp']['versions'][0];
$modern_meta    = [
    'io.modelcontextprotocol/protocolVersion'    => $modern_version,
    'io.modelcontextprotocol/clientCapabilities' => [],
];

// ヘッダーの取得テスト
{
    // 確認
    $headers = service_mcp_headers([
        'HTTP_AUTHORIZATION'        => 'Bearer abc',
        'HTTP_MCP_PROTOCOL_VERSION' => '2025-11-25',
        'REQUEST_METHOD'            => 'POST',
    ]);

    // 結果
    test_equals('headers authorization', $headers['authorization'], 'Bearer abc');
    test_equals('headers lower case and hyphen', $headers['mcp-protocol-version'], '2025-11-25');
    test_array_not_haskey('headers without HTTP_', $headers, 'request-method');

    // 確認（CGIで REDIRECT_ 付きになったとき）
    $headers = service_mcp_headers([
        'REDIRECT_HTTP_AUTHORIZATION' => 'Bearer def',
    ]);

    // 結果
    test_equals('headers redirect authorization', $headers['authorization'], 'Bearer def');

    // 確認（両方あれば、REDIRECT_ の無いほうを使う）
    $headers = service_mcp_headers([
        'HTTP_AUTHORIZATION'          => 'Bearer abc',
        'REDIRECT_HTTP_AUTHORIZATION' => 'Bearer def',
    ]);

    // 結果
    test_equals('headers authorization priority', $headers['authorization'], 'Bearer abc');
}

// Bearerトークンの取得テスト
{
    // 結果
    test_equals('bearer', service_mcp_bearer(['authorization' => 'Bearer abc123']), 'abc123');
    test_equals('bearer case insensitive', service_mcp_bearer(['authorization' => 'bearer abc123']), 'abc123');
    test_equals('bearer none', service_mcp_bearer([]), null);
    test_equals('bearer basic', service_mcp_bearer(['authorization' => 'Basic YWJjOmRlZg==']), null);
    test_equals('bearer empty', service_mcp_bearer(['authorization' => 'Bearer ']), null);
    test_equals('bearer space in token', service_mcp_bearer(['authorization' => 'Bearer abc def']), null);
}

// Originの確認テスト
{
    // 結果
    test_equals('origin none', service_mcp_origin([], 'http://example.com/freo2'), true);
    test_equals('origin same', service_mcp_origin(['origin' => 'http://example.com'], 'http://example.com/freo2'), true);
    test_equals('origin same case', service_mcp_origin(['origin' => 'HTTP://EXAMPLE.COM'], 'http://example.com/freo2'), true);
    test_equals('origin same port', service_mcp_origin(['origin' => 'http://example.com:8080'], 'http://example.com:8080/'), true);
    test_equals('origin other host', service_mcp_origin(['origin' => 'http://evil.example'], 'http://example.com/freo2'), false);
    test_equals('origin other scheme', service_mcp_origin(['origin' => 'https://example.com'], 'http://example.com/freo2'), false);
    test_equals('origin other port', service_mcp_origin(['origin' => 'http://example.com:8080'], 'http://example.com/freo2'), false);
    test_equals('origin invalid url', service_mcp_origin(['origin' => 'http://example.com'], 'example.com'), false);
}

// ヘッダーの値の取得テスト
{
    // 結果
    test_equals('header value plain', service_mcp_header_value('get_entry'), 'get_entry');
    test_equals('header value base64', service_mcp_header_value('=?base64?' . base64_encode('ツール') . '?='), 'ツール');
    test_equals('header value invalid base64', service_mcp_header_value('=?base64?@@@?='), '=?base64?@@@?=');
    test_equals('header value null', service_mcp_header_value(null), null);
}

// トークンの認証テスト
{
    // データ
    $test_tokens = [
        'valid'   => [
            'user_id' => 1,
            'expire'  => null,
        ],
        'future'  => [
            'user_id' => 1,
            'expire'  => '2099-12-31 23:59:59',
        ],
        'expired' => [
            'user_id' => 1,
            'expire'  => '2000-01-01 00:00:00',
        ],
        'deleted' => [
            'user_id' => 1,
            'expire'  => null,
        ],
        'nouser'  => [
            'user_id' => 99999,
            'expire'  => null,
        ],
    ];
    foreach ($test_tokens as $name => $test_token) {
        service_mcp_token_insert([
            'values' => [
                'user_id' => $test_token['user_id'],
                'name'    => $name,
                'token'   => service_mcp_token_hash('mcpauth' . $name),
                'scope'   => 'read',
                'expire'  => $test_token['expire'],
            ],
        ]);
    }
    service_mcp_token_delete([
        'where' => 'name = ' . db_escape('deleted'),
    ]);

    // 確認
    $auth = service_mcp_authenticate('mcpauthvalid');

    // 結果
    test_not_equals('authenticate valid', $auth, null);
    test_equals('authenticate valid user', intval($auth['user']['id']), 1);
    test_equals('authenticate valid scope', $auth['token']['scope'], 'read');
    test_equals('authenticate valid power', intval($auth['authority']['power']), 3);

    // 結果（最終利用日時は記録し、更新日時は変えない）
    $mcp_tokens = model('select_mcp_tokens', [
        'where' => 'name = ' . db_escape('valid'),
    ]);

    test_not_equals('authenticate accessed', $mcp_tokens[0]['accessed'], null);
    test_equals('authenticate modified', $mcp_tokens[0]['modified'], $mcp_tokens[0]['created']);

    // 結果（認証できないもの）
    test_not_equals('authenticate future expire', service_mcp_authenticate('mcpauthfuture'), null);
    test_equals('authenticate expired', service_mcp_authenticate('mcpauthexpired'), null);
    test_equals('authenticate deleted', service_mcp_authenticate('mcpauthdeleted'), null);
    test_equals('authenticate user not found', service_mcp_authenticate('mcpauthnouser'), null);
    test_equals('authenticate wrong token', service_mcp_authenticate('mcpauthwrong'), null);
    test_equals('authenticate null', service_mcp_authenticate(null), null);
    test_equals('authenticate empty', service_mcp_authenticate(''), null);

    // 確認（権限の下限を上げると、管理者でも認証できない）
    $power = $GLOBALS['plugin']['mcp']['power'];
    $GLOBALS['plugin']['mcp']['power'] = 4;

    $auth = service_mcp_authenticate('mcpauthvalid');

    $GLOBALS['plugin']['mcp']['power'] = $power;

    // 結果
    test_equals('authenticate power', $auth, null);

    // 結果（ハッシュだけを保存していること）
    $mcp_tokens = model('select_mcp_tokens', [
        'where' => 'name = ' . db_escape('valid'),
    ]);

    test_equals('token hash length', strlen($mcp_tokens[0]['token']), 64);
    test_not_equals('token hash not plain', $mcp_tokens[0]['token'], 'mcpauthvalid');
}

// JSON-RPCの形の確認テスト
{
    // 確認
    $response = service_mcp_handle(['jsonrpc' => '1.0', 'id' => 1, 'method' => 'ping'], [], $auth_read);

    // 結果
    test_equals('invalid jsonrpc status', $response['status'], 400);
    test_equals('invalid jsonrpc code', $response['body']['error']['code'], -32600);

    // 確認
    $response = service_mcp_handle(['jsonrpc' => '2.0', 'id' => 1], [], $auth_read);

    // 結果
    test_equals('no method status', $response['status'], 400);

    // 確認（まとめて送る形式は受けない）
    $response = service_mcp_handle([['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping']], [], $auth_read);

    // 結果
    test_equals('batch status', $response['status'], 400);
}

// 以前の版のテスト
{
    // 確認（対応している版を求められたら、同じ版を返す）
    $response = service_mcp_handle([
        'jsonrpc' => '2.0',
        'id'      => 1,
        'method'  => 'initialize',
        'params'  => [
            'protocolVersion' => '2025-06-18',
        ],
    ], [], $auth_read);

    // 結果
    test_equals('initialize status', $response['status'], 200);
    test_equals('initialize version', $response['body']['result']['protocolVersion'], '2025-06-18');
    test_equals('initialize server name', $response['body']['result']['serverInfo']['name'], 'freo2');
    test_array_haskey('initialize tools capability', $response['body']['result']['capabilities'], 'tools');

    // 確認（対応していない版なら、既定の版を返す）
    $response = service_mcp_handle([
        'jsonrpc' => '2.0',
        'id'      => 1,
        'method'  => 'initialize',
        'params'  => [
            'protocolVersion' => '2099-01-01',
        ],
    ], [], $auth_read);

    // 結果
    test_equals('initialize unsupported version', $response['body']['result']['protocolVersion'], $GLOBALS['plugin']['mcp']['legacy_versions'][0]);

    // 確認（通知は本文なし）
    $response = service_mcp_handle([
        'jsonrpc' => '2.0',
        'method'  => 'notifications/initialized',
    ], ['mcp-protocol-version' => '2025-11-25'], $auth_read);

    // 結果
    test_equals('legacy notification status', $response['status'], 202);
    test_equals('legacy notification body', $response['body'], null);

    // 確認
    $response = service_mcp_handle([
        'jsonrpc' => '2.0',
        'id'      => 2,
        'method'  => 'ping',
    ], ['mcp-protocol-version' => '2025-11-25'], $auth_read);

    // 結果
    test_equals('legacy ping status', $response['status'], 200);
    test_equals('legacy ping id', $response['body']['id'], 2);

    // 確認（版のヘッダーが無ければ 2025-03-26 とみなす）
    $response = service_mcp_handle([
        'jsonrpc' => '2.0',
        'id'      => 3,
        'method'  => 'ping',
    ], [], $auth_read);

    // 結果
    test_equals('legacy no version header', $response['status'], 200);

    // 確認
    $response = service_mcp_handle([
        'jsonrpc' => '2.0',
        'id'      => 4,
        'method'  => 'ping',
    ], ['mcp-protocol-version' => '2099-01-01'], $auth_read);

    // 結果
    test_equals('legacy unsupported version status', $response['status'], 400);
    test_equals('legacy unsupported version code', $response['body']['error']['code'], -32600);

    // 確認（無いメソッドは HTTP 200のJSON-RPCのエラー）
    $response = service_mcp_handle([
        'jsonrpc' => '2.0',
        'id'      => 5,
        'method'  => 'resources/list',
    ], ['mcp-protocol-version' => '2025-11-25'], $auth_read);

    // 結果
    test_equals('legacy method not found status', $response['status'], 200);
    test_equals('legacy method not found code', $response['body']['error']['code'], -32601);

    // 確認（server/discover は 2026-07-28 の版だけ）
    $response = service_mcp_handle([
        'jsonrpc' => '2.0',
        'id'      => 6,
        'method'  => 'server/discover',
    ], ['mcp-protocol-version' => '2025-11-25'], $auth_read);

    // 結果
    test_equals('legacy server/discover', $response['body']['error']['code'], -32601);
}

// 2026-07-28 の版のテスト
{
    // 確認
    $response = service_mcp_handle([
        'jsonrpc' => '2.0',
        'id'      => 1,
        'method'  => 'server/discover',
        'params'  => [
            '_meta' => $modern_meta,
        ],
    ], [
        'mcp-protocol-version' => $modern_version,
        'mcp-method'           => 'server/discover',
    ], $auth_read);

    // 結果
    test_equals('discover status', $response['status'], 200);
    test_equals('discover versions', $response['body']['result']['supportedVersions'], $GLOBALS['plugin']['mcp']['versions']);
    test_equals('discover server name', $response['body']['result']['_meta']['io.modelcontextprotocol/serverInfo']['name'], 'freo2');

    // 確認（通知は本文なし）
    $response = service_mcp_handle([
        'jsonrpc' => '2.0',
        'method'  => 'notifications/cancelled',
        'params'  => [
            '_meta' => $modern_meta,
        ],
    ], [
        'mcp-protocol-version' => $modern_version,
    ], $auth_read);

    // 結果
    test_equals('modern notification status', $response['status'], 202);

    // 確認（対応していない版）
    $response = service_mcp_handle([
        'jsonrpc' => '2.0',
        'id'      => 2,
        'method'  => 'tools/list',
        'params'  => [
            '_meta' => [
                'io.modelcontextprotocol/protocolVersion'    => '2099-01-01',
                'io.modelcontextprotocol/clientCapabilities' => [],
            ],
        ],
    ], [
        'mcp-protocol-version' => '2099-01-01',
        'mcp-method'           => 'tools/list',
    ], $auth_read);

    // 結果
    test_equals('modern unsupported version status', $response['status'], 400);
    test_equals('modern unsupported version code', $response['body']['error']['code'], -32022);

    // 確認（版のヘッダーが本文と違う）
    $response = service_mcp_handle([
        'jsonrpc' => '2.0',
        'id'      => 3,
        'method'  => 'tools/list',
        'params'  => [
            '_meta' => $modern_meta,
        ],
    ], [
        'mcp-protocol-version' => '2025-11-25',
        'mcp-method'           => 'tools/list',
    ], $auth_read);

    // 結果
    test_equals('modern version header mismatch status', $response['status'], 400);
    test_equals('modern version header mismatch code', $response['body']['error']['code'], -32020);

    // 確認（メソッドのヘッダーが本文と違う）
    $response = service_mcp_handle([
        'jsonrpc' => '2.0',
        'id'      => 4,
        'method'  => 'tools/list',
        'params'  => [
            '_meta' => $modern_meta,
        ],
    ], [
        'mcp-protocol-version' => $modern_version,
        'mcp-method'           => 'tools/call',
    ], $auth_read);

    // 結果
    test_equals('modern method header mismatch', $response['body']['error']['code'], -32020);

    // 確認（ツール名のヘッダーが本文と違う）
    $response = service_mcp_handle([
        'jsonrpc' => '2.0',
        'id'      => 5,
        'method'  => 'tools/call',
        'params'  => [
            'name'      => 'list_types',
            'arguments' => [],
            '_meta'     => $modern_meta,
        ],
    ], [
        'mcp-protocol-version' => $modern_version,
        'mcp-method'           => 'tools/call',
        'mcp-name'             => 'get_entry',
    ], $auth_read);

    // 結果
    test_equals('modern name header mismatch', $response['body']['error']['code'], -32020);

    // 確認（ツール名のヘッダーが base64 の形）
    $response = service_mcp_handle([
        'jsonrpc' => '2.0',
        'id'      => 6,
        'method'  => 'tools/call',
        'params'  => [
            'name'      => 'list_types',
            'arguments' => [],
            '_meta'     => $modern_meta,
        ],
    ], [
        'mcp-protocol-version' => $modern_version,
        'mcp-method'           => 'tools/call',
        'mcp-name'             => '=?base64?' . base64_encode('list_types') . '?=',
    ], $auth_read);

    // 結果
    test_equals('modern name header base64', $response['status'], 200);
    test_equals('modern tools/call result type', $response['body']['result']['resultType'], 'complete');
    test_equals('modern tools/call is not error', $response['body']['result']['isError'], false);

    // 確認（clientCapabilitiesが無い）
    $response = service_mcp_handle([
        'jsonrpc' => '2.0',
        'id'      => 7,
        'method'  => 'tools/list',
        'params'  => [
            '_meta' => [
                'io.modelcontextprotocol/protocolVersion' => $modern_version,
            ],
        ],
    ], [
        'mcp-protocol-version' => $modern_version,
        'mcp-method'           => 'tools/list',
    ], $auth_read);

    // 結果
    test_equals('modern no capabilities status', $response['status'], 400);
    test_equals('modern no capabilities code', $response['body']['error']['code'], -32602);

    // 確認（無いメソッドは404）
    $response = service_mcp_handle([
        'jsonrpc' => '2.0',
        'id'      => 8,
        'method'  => 'resources/list',
        'params'  => [
            '_meta' => $modern_meta,
        ],
    ], [
        'mcp-protocol-version' => $modern_version,
        'mcp-method'           => 'resources/list',
    ], $auth_read);

    // 結果
    test_equals('modern method not found status', $response['status'], 404);
    test_equals('modern method not found code', $response['body']['error']['code'], -32601);

    // 確認（tools/list は、範囲ごとにキャッシュさせる）
    $response = service_mcp_handle([
        'jsonrpc' => '2.0',
        'id'      => 9,
        'method'  => 'tools/list',
        'params'  => [
            '_meta' => $modern_meta,
        ],
    ], [
        'mcp-protocol-version' => $modern_version,
        'mcp-method'           => 'tools/list',
    ], $auth_read);

    // 結果
    test_equals('modern tools/list cache scope', $response['body']['result']['cacheScope'], 'private');
}

// ツールの一覧テスト
{
    // 確認
    $tools_read  = array_column(service_mcp_tools($auth_read), 'name');
    $tools_draft = array_column(service_mcp_tools($auth_draft), 'name');
    $tools_admin = array_column(service_mcp_tools($auth_admin), 'name');

    // 結果
    $test_tools = ['list_types', 'search_entries', 'get_entry', 'list_categories', 'list_fields', 'list_comments', 'list_media', 'get_site', 'list_attributes'];
    test_equals('tools read', $tools_read, $test_tools);
    test_equals('tools draft', $tools_draft, array_merge($test_tools, ['create_draft', 'update_draft']));
    test_equals('tools admin', $tools_admin, array_merge($test_tools, ['list_menus', 'list_widgets']));

    // 確認（管理者以外のトークンでは、管理者だけのツールを呼べない）
    $response = service_mcp_dispatch(1, 'tools/call', [
        'name' => 'list_menus',
    ], $auth_draft, false);

    // 結果
    test_equals('tools/call admin tool with author', $response['body']['error']['code'], -32602);

    // 確認（範囲が read のトークンでは、下書きのツールを呼べない）
    $response = service_mcp_dispatch(1, 'tools/call', [
        'name'      => 'create_draft',
        'arguments' => [
            'type'  => 'entry',
            'title' => 'テスト',
        ],
    ], $auth_read, false);

    // 結果
    test_equals('tools/call draft with read scope', $response['body']['error']['code'], -32602);

    // 確認（無いツール）
    $response = service_mcp_dispatch(1, 'tools/call', [
        'name' => 'delete_entry',
    ], $auth_draft, false);

    // 結果
    test_equals('tools/call unknown tool', $response['body']['error']['code'], -32602);

    // 確認（ツール自体の失敗はisError）
    $response = service_mcp_dispatch(1, 'tools/call', [
        'name'      => 'get_entry',
        'arguments' => [],
    ], $auth_read, false);

    // 結果
    test_equals('tools/call tool error', $response['body']['result']['isError'], true);
    test_array_not_haskey('tools/call tool error structured', $response['body']['result'], 'structuredContent');
}

// 前提データ（フィールドとカテゴリー）
db_insert([
    'insert_into' => DATABASE_PREFIX . 'fields',
    'values'      => [
        'created'    => localdate('Y-m-d H:i:s'),
        'modified'   => localdate('Y-m-d H:i:s'),
        'type_id'    => 1,
        'name'       => 'テスト一行',
        'kind'       => 'text',
        'validation' => 'required',
        'initial'    => '初期値',
        'sort'       => 1,
    ],
]);
db_insert([
    'insert_into' => DATABASE_PREFIX . 'fields',
    'values'      => [
        'created'  => localdate('Y-m-d H:i:s'),
        'modified' => localdate('Y-m-d H:i:s'),
        'type_id'  => 1,
        'name'     => 'テストチェック',
        'kind'     => 'checkbox',
        'choices'  => "A\nB\nC",
        'sort'     => 2,
    ],
]);
db_insert([
    'insert_into' => DATABASE_PREFIX . 'fields',
    'values'      => [
        'created'  => localdate('Y-m-d H:i:s'),
        'modified' => localdate('Y-m-d H:i:s'),
        'type_id'  => 1,
        'name'     => 'テスト画像',
        'kind'     => 'image',
        'sort'     => 3,
    ],
]);
db_insert([
    'insert_into' => DATABASE_PREFIX . 'categories',
    'values'      => [
        'created'  => localdate('Y-m-d H:i:s'),
        'modified' => localdate('Y-m-d H:i:s'),
        'type_id'  => 1,
        'code'     => 'mcpcat1',
        'name'     => 'テストカテゴリー1',
        'sort'     => 1,
    ],
]);
db_insert([
    'insert_into' => DATABASE_PREFIX . 'categories',
    'values'      => [
        'created'  => localdate('Y-m-d H:i:s'),
        'modified' => localdate('Y-m-d H:i:s'),
        'type_id'  => 2,
        'code'     => 'mcpcat2',
        'name'     => 'テストカテゴリー2',
        'sort'     => 1,
    ],
]);

// 読み取りのツールのテスト
{
    // 確認
    $output = service_mcp_tool_list_types([], $auth_read);

    // 結果
    test_equals('list_types', array_slice(array_column($output['data']['types'], 'code'), 0, 2), ['entry', 'page']);

    // 確認
    $output = service_mcp_tool_list_categories(['type' => 'entry'], $auth_read);

    // 結果（ほかの型のカテゴリーは出ない）
    test_equals('list_categories', array_column($output['data']['categories'], 'code'), ['mcpcat1']);

    // 結果（入力の誤り）
    $output = service_mcp_tool_list_categories([], $auth_read);
    test_not_equals('list_categories no type', $output['error'], null);

    $output = service_mcp_tool_list_categories(['type' => 'nothing'], $auth_read);
    test_contains('list_categories unknown type', $output['error'], 'nothing');

    // 確認
    $output = service_mcp_tool_list_fields(['type' => 'entry'], $auth_read);
    $fields = $output['data']['fields'];

    // 結果
    test_equals('list_fields count', count($fields), 3);
    test_equals('list_fields required', $fields[0]['required'], true);
    test_equals('list_fields initial', $fields[0]['initial'], '初期値');
    test_equals('list_fields choices', $fields[1]['choices'], ['A', 'B', 'C']);
    test_equals('list_fields choices only for select', $fields[0]['choices'], []);
    test_equals('list_fields media', array_column($fields, 'media'), [false, false, true]);

    $field_ids = array_column($fields, 'id');

    // 結果（入力の誤り）
    $output = service_mcp_tool_list_fields(['type' => 'nothing'], $auth_read);
    test_not_equals('list_fields unknown type', $output['error'], null);

    // 結果（search_entries の入力の誤り）
    $output = service_mcp_tool_search_entries(['limit' => 0], $auth_read);
    test_not_equals('search_entries limit min', $output['error'], null);

    $output = service_mcp_tool_search_entries(['limit' => $GLOBALS['plugin']['mcp']['search_limit'] + 1], $auth_read);
    test_not_equals('search_entries limit max', $output['error'], null);

    $output = service_mcp_tool_search_entries(['limit' => '10'], $auth_read);
    test_not_equals('search_entries limit string', $output['error'], null);

    $output = service_mcp_tool_search_entries(['offset' => -1], $auth_read);
    test_not_equals('search_entries offset', $output['error'], null);

    $output = service_mcp_tool_search_entries(['public' => 'secret'], $auth_read);
    test_not_equals('search_entries public', $output['error'], null);

    $output = service_mcp_tool_search_entries(['type' => 'nothing'], $auth_read);
    test_not_equals('search_entries unknown type', $output['error'], null);

    $output = service_mcp_tool_search_entries(['keyword' => 1], $auth_read);
    test_not_equals('search_entries keyword not string', $output['error'], null);

    // 結果（get_entry の入力の誤り）
    $output = service_mcp_tool_get_entry(['type' => 'entry'], $auth_read);
    test_not_equals('get_entry type only', $output['error'], null);

    $output = service_mcp_tool_get_entry(['id' => 0], $auth_read);
    test_not_equals('get_entry id zero', $output['error'], null);

    $output = service_mcp_tool_get_entry(['id' => 99999], $auth_read);
    test_equals('get_entry not found', $output['error'], 'エントリーが見つかりません。');
}

// 下書きの作成テスト（入力の誤り）
{
    // データ
    $test_errors = [
        'type not draft'       => [
            'type'  => 'gallery',
            'title' => 'テスト',
        ],
        'no type'              => [
            'title' => 'テスト',
        ],
        'no code'              => [
            'type'  => 'page',
            'title' => 'テスト',
        ],
        'empty title'          => [
            'type'  => 'entry',
            'title' => '',
        ],
        'title not string'     => [
            'type'  => 'entry',
            'title' => 1,
        ],
        'title too long'       => [
            'type'  => 'entry',
            'title' => str_repeat('あ', 101),
        ],
        'bad code'             => [
            'type'  => 'entry',
            'title' => 'テスト',
            'code'  => 'あ',
        ],
        'bad text_type'        => [
            'type'      => 'entry',
            'title'     => 'テスト',
            'text_type' => 'pdf',
        ],
        'bad datetime'         => [
            'type'     => 'entry',
            'title'    => 'テスト',
            'datetime' => '2026/11/11',
        ],
        'invalid datetime'     => [
            'type'     => 'entry',
            'title'    => 'テスト',
            'datetime' => '2026-11-11 25:00',
        ],
        'unknown category'     => [
            'type'       => 'entry',
            'title'      => 'テスト',
            'categories' => ['nothing'],
        ],
        'other type category'  => [
            'type'       => 'entry',
            'title'      => 'テスト',
            'categories' => ['mcpcat2'],
        ],
        'categories not array' => [
            'type'       => 'entry',
            'title'      => 'テスト',
            'categories' => 'mcpcat1',
        ],
        'required field'       => [
            'type'   => 'entry',
            'title'  => 'テスト',
            'fields' => [
                $field_ids[0] => '',
            ],
        ],
        'unknown field'        => [
            'type'   => 'entry',
            'title'  => 'テスト',
            'fields' => [
                99999 => 'a',
            ],
        ],
        'image field no media' => [
            'type'   => 'entry',
            'title'  => 'テスト',
            'fields' => [
                $field_ids[2] => 'a.jpg',
            ],
        ],
        'bad choice'           => [
            'type'   => 'entry',
            'title'  => 'テスト',
            'fields' => [
                $field_ids[1] => ['A', 'Z'],
            ],
        ],
        'field not string'     => [
            'type'   => 'entry',
            'title'  => 'テスト',
            'fields' => [
                $field_ids[1] => [1],
            ],
        ],
    ];

    foreach ($test_errors as $name => $arguments) {
        // 確認
        $output = service_mcp_tool_create_draft($arguments, $auth_draft);

        // 結果
        test_not_equals('create_draft ' . $name, $output['error'], null);
    }

    // 結果（何も登録されていないこと）
    $entries = model('select_entries', []);

    test_equals('create_draft error not inserted', count($entries), 0);
}

// 下書きの作成テスト
{
    // データ
    $arguments = [
        'type'       => 'entry',
        'title'      => 'テスト下書き',
        'code'       => 'mcp_draft',
        'text'       => '<p>本文</p>',
        'datetime'   => '2026-11-11 10:00:30',
        'categories' => ['mcpcat1', 'mcpcat1'],
        'fields'     => [
            $field_ids[1] => ['A', 'C'],
        ],
    ];

    // 確認
    $output = service_mcp_tool_create_draft($arguments, $auth_draft);

    // 結果
    test_equals('create_draft error', $output['error'], null);
    test_equals('create_draft public', $output['data']['entry']['public'], 'none');
    test_equals('create_draft approved', $output['data']['entry']['approved'], true);
    test_contains('create_draft edit_url', $output['data']['entry']['edit_url'], '/admin/entry_form?id=' . $output['data']['entry']['id']);

    $draft_id = $output['data']['entry']['id'];

    // 結果（登録された内容）
    $entries = model('select_entries', [
        'where' => 'entries.id = ' . intval($draft_id),
    ], [
        'associate' => true,
    ]);
    $entry = $entries[0];

    test_equals('create_draft title', $entry['title'], 'テスト下書き');
    test_equals('create_draft code', $entry['code'], 'mcp_draft');
    test_equals('create_draft text_type default', $entry['text_type'], 'wysiwyg');
    test_equals('create_draft datetime', $entry['datetime'], '2026-11-11 10:00:00');
    test_equals('create_draft comment', $entry['comment'], 'closed');
    test_equals('create_draft categories (unique)', count($entry['category_sets']), 1);
    test_equals('create_draft field initial', $entry['field_sets'][$field_ids[0]], '初期値');
    test_equals('create_draft field checkbox', $entry['field_sets'][$field_ids[1]], "A\nC");
    test_array_not_haskey('create_draft field image', $entry['field_sets'], $field_ids[2]);

    // 結果（操作ログが記録されること）
    $logs = model('select_logs', [
        'where' => 'model = ' . db_escape('entries') . ' AND exec = ' . db_escape('insert'),
    ]);

    test_equals('create_draft log', count($logs), 1);

    // 確認（コードの重複）
    $output = service_mcp_tool_create_draft([
        'type'  => 'entry',
        'title' => 'テスト',
        'code'  => 'mcp_draft',
    ], $auth_draft);

    // 結果
    test_equals('create_draft duplicate code', $output['error'], '入力されたコードはすでに使用されています。');

    // 確認（コードを省略すると、設定の既定のコード）
    $output = service_mcp_tool_create_draft([
        'type'  => 'entry',
        'title' => 'テスト既定のコード',
    ], $auth_draft);

    // 結果
    test_equals('create_draft default code', $output['error'], null);
    test_regexp('create_draft default code format', $output['data']['entry']['code'], '^\d{14}$');

    // 確認（ページ。承認は設定に従い、本文形式も型ごとの設定）
    $output = service_mcp_tool_create_draft([
        'type'  => 'page',
        'title' => 'テストページ',
        'code'  => 'mcp/page',
    ], $auth_draft);

    // 結果
    test_equals('create_draft page', $output['error'], null);
    test_equals('create_draft page approved', $output['data']['entry']['approved'], false);
    test_contains('create_draft page edit_url', $output['data']['entry']['edit_url'], '/admin/page_form?id=');

    $entries = model('select_entries', [
        'where' => 'entries.id = ' . intval($output['data']['entry']['id']),
    ]);

    test_equals('create_draft page text_type', $entries[0]['text_type'], 'html');

    $page_id = $output['data']['entry']['id'];
}

// コメントの一覧テスト
{
    // データ（下書きへのコメント2件と、お問い合わせのやりとり1件）
    $test_comments = [
        [
            'entry_id'   => $draft_id,
            'contact_id' => null,
            'user_id'    => null,
            'approved'   => 1,
            'name'       => 'テスト太郎',
            'message'    => '承認済みのコメント',
            'created'    => '2026-11-12 10:00:00',
        ],
        [
            'entry_id'   => $draft_id,
            'contact_id' => null,
            'user_id'    => 1,
            'approved'   => 0,
            'name'       => 'テスト花子',
            'message'    => '承認待ちのコメント',
            'created'    => '2026-11-12 11:00:00',
        ],
        [
            'entry_id'   => null,
            'contact_id' => 1,
            'user_id'    => null,
            'approved'   => 1,
            'name'       => 'お問い合わせの人',
            'message'    => 'お問い合わせのやりとり',
            'created'    => '2026-11-12 12:00:00',
        ],
    ];
    foreach ($test_comments as $test_comment) {
        db_insert([
            'insert_into' => DATABASE_PREFIX . 'comments',
            'values'      => $test_comment + [
                'modified' => $test_comment['created'],
            ],
        ]);
    }

    // 確認
    $output = service_mcp_tool_list_comments([], $auth_read);

    // 結果（お問い合わせのやりとりは返さない。新しい順）
    test_equals('list_comments error', $output['error'], null);
    test_equals('list_comments total', $output['data']['total'], 2);
    test_equals('list_comments order', array_column($output['data']['comments'], 'message'), ['承認待ちのコメント', '承認済みのコメント']);
    test_equals('list_comments entry', $output['data']['comments'][0]['entry']['title'], 'テスト下書き');
    test_equals('list_comments entry type', $output['data']['comments'][0]['entry']['type'], 'entry');
    test_equals('list_comments approved', $output['data']['comments'][0]['approved'], false);
    test_equals('list_comments registered', array_column($output['data']['comments'], 'registered'), [true, false]);

    // 確認（絞り込み）
    $output = service_mcp_tool_list_comments(['approved' => false], $auth_read);
    test_equals('list_comments approved false', array_column($output['data']['comments'], 'message'), ['承認待ちのコメント']);

    $output = service_mcp_tool_list_comments(['approved' => true], $auth_read);
    test_equals('list_comments approved true', array_column($output['data']['comments'], 'message'), ['承認済みのコメント']);

    $output = service_mcp_tool_list_comments(['keyword' => '承認済み'], $auth_read);
    test_equals('list_comments keyword', $output['data']['total'], 1);

    $output = service_mcp_tool_list_comments(['entry_id' => $page_id], $auth_read);
    test_equals('list_comments other entry', $output['data']['total'], 0);

    $output = service_mcp_tool_list_comments(['limit' => 1, 'offset' => 1], $auth_read);
    test_equals('list_comments limit offset', array_column($output['data']['comments'], 'message'), ['承認済みのコメント']);
    test_equals('list_comments limit offset total', $output['data']['total'], 2);

    // 結果（入力の誤り）
    $output = service_mcp_tool_list_comments(['approved' => 1], $auth_read);
    test_not_equals('list_comments approved not bool', $output['error'], null);

    $output = service_mcp_tool_list_comments(['entry_id' => 0], $auth_read);
    test_not_equals('list_comments entry_id zero', $output['error'], null);

    $output = service_mcp_tool_list_comments(['keyword' => 1], $auth_read);
    test_not_equals('list_comments keyword not string', $output['error'], null);

    $output = service_mcp_tool_list_comments(['limit' => 0], $auth_read);
    test_not_equals('list_comments limit min', $output['error'], null);

    $output = service_mcp_tool_list_comments(['offset' => -1], $auth_read);
    test_not_equals('list_comments offset', $output['error'], null);
}

// メディアの一覧テスト
{
    // データ（ストレージをファイルにして、テスト用のディレクトリに向ける）
    $config_backup = [
        'storage_type' => $GLOBALS['config']['storage_type'],
        'file_target'  => $GLOBALS['config']['file_target'],
    ];
    $GLOBALS['config']['storage_type']             = 'file';
    $GLOBALS['config']['file_target']['media']     = 'files/tests/mcp_medias/';
    $GLOBALS['config']['file_target']['thumbnail'] = 'files/tests/mcp_thumbnails/';

    $test_media      = $GLOBALS['config']['file_target']['media'];
    $test_thumbnail  = $GLOBALS['config']['file_target']['thumbnail'];
    $test_author_dir = $GLOBALS['config']['media_author_dir'];

    directory_mkdir($test_media . $test_author_dir);
    directory_mkdir($test_media . 'sub');
    directory_mkdir($test_thumbnail . 'medias');
    file_put_contents($test_media . 'photo.jpg', 'jpg');
    file_put_contents($test_media . 'memo.txt', 'text');
    file_put_contents($test_media . 'plain.gif', 'gif');
    file_put_contents($test_media . $test_author_dir . '/author.txt', 'author');
    file_put_contents($test_thumbnail . 'medias/photo.jpg', 'thumbnail');

    // 確認（管理者は最上位）
    $output = service_mcp_tool_list_media([], $auth_admin);
    $files  = $output['data']['files'];

    // 結果
    test_equals('list_media error', $output['error'], null);
    test_equals('list_media directory', $output['data']['directory'], '');
    test_equals('list_media parent', $output['data']['parent'], null);
    test_equals('list_media directories', $output['data']['directories'], [$test_author_dir, 'sub']);
    test_equals('list_media files', array_column($files, 'name'), ['memo.txt', 'photo.jpg', 'plain.gif']);
    test_equals('list_media url', $files[1]['url'], $GLOBALS['config']['storage_url'] . '/' . $test_media . 'photo.jpg');
    test_equals('list_media thumbnail', $files[1]['thumbnail_url'], $GLOBALS['config']['storage_url'] . '/' . $test_thumbnail . 'medias/photo.jpg');
    test_equals('list_media no thumbnail', $files[0]['thumbnail_url'], null);
    test_equals('list_media size', $files[1]['size'], 3);
    test_regexp('list_media modified', $files[1]['modified'], '^\d{4}-\d\d-\d\d \d\d:\d\d:\d\d$');

    // 結果（本文に挿入するHTMLは、管理画面の「挿入」ボタンと同じ）
    $test_url = $GLOBALS['config']['storage_url'] . '/' . $test_media;
    test_equals('list_media insert_html thumbnail', $files[1]['insert_html'], '<a href="' . $test_url . 'photo.jpg" target="_blank"><img src="' . $GLOBALS['config']['storage_url'] . '/' . $test_thumbnail . 'medias/photo.jpg" alt="photo.jpg"></a>');
    test_equals('list_media insert_html image', $files[2]['insert_html'], '<img src="' . $test_url . 'plain.gif" alt="plain.gif">');
    test_equals('list_media insert_html file', $files[0]['insert_html'], '<a href="' . $test_url . 'memo.txt" target="_blank">memo.txt</a>');

    // 確認（投稿者は、投稿者用のディレクトリ）
    $output = service_mcp_tool_list_media([], $auth_read);

    // 結果
    test_equals('list_media author directory', $output['data']['directory'], $test_author_dir);
    test_equals('list_media author parent', $output['data']['parent'], null);
    test_equals('list_media author files', array_column($output['data']['files'], 'name'), ['author.txt']);
    test_equals('list_media author url', $output['data']['files'][0]['url'], $GLOBALS['config']['storage_url'] . '/' . $test_media . $test_author_dir . '/author.txt');

    // 確認（下のディレクトリ）
    $output = service_mcp_tool_list_media(['directory' => 'sub'], $auth_admin);

    // 結果
    test_equals('list_media sub files', $output['data']['files'], []);
    test_equals('list_media sub parent', $output['data']['parent'], '');

    // 確認（無いディレクトリは、作らずに空の一覧）
    $output = service_mcp_tool_list_media(['directory' => 'nothing'], $auth_admin);

    // 結果
    test_equals('list_media nothing', $output['data']['files'], []);
    test_equals('list_media nothing not created', is_dir($test_media . 'nothing'), false);

    // 結果（入力の誤り）
    foreach (['../files', 'sub/..', 'sub/../..', './sub', '/sub', 'sub/', 'sub//a', 'あ'] as $test_directory) {
        $output = service_mcp_tool_list_media(['directory' => $test_directory], $auth_admin);
        test_equals('list_media invalid ' . $test_directory, $output['error'], 'directoryの指定が不正です。');
    }

    $output = service_mcp_tool_list_media(['directory' => 1], $auth_admin);
    test_not_equals('list_media directory not string', $output['error'], null);

    // 後始末
    directory_rmdir($test_media);
    directory_rmdir($test_thumbnail);

    $GLOBALS['config']['storage_type'] = $config_backup['storage_type'];
    $GLOBALS['config']['file_target']  = $config_backup['file_target'];
}

// サイトの情報テスト
{
    // 確認
    $output = service_mcp_tool_get_site([], $auth_read);
    $site   = $output['data']['site'];

    $settings = [];
    foreach ($site['settings'] as $setting) {
        $settings[$setting['id']] = $setting;
    }

    // 結果
    test_equals('get_site title', $site['title'], 'テストサイト');
    test_equals('get_site description', $site['description'], 'テストの概要');
    test_equals('get_site url', $site['url'], $GLOBALS['config']['http_url'] . '/');
    test_equals('get_site settings', array_keys($settings), $GLOBALS['plugin']['mcp']['site_settings']);
    test_equals('get_site setting name', $settings['comment_use_approve']['name'], 'コメントの承認');
    test_array_not_haskey('get_site no type setting', $settings, 'entry_use_approve');
    test_array_not_haskey('get_site no mail', $settings, 'mail_from');
}

// 型の一覧テスト（下書きを作れるかと、型の設定）
{
    // 確認
    $output = service_mcp_tool_list_types([], $auth_read);
    $types  = array_column($output['data']['types'], null, 'code');

    // 結果（本体の型は、本体の設定から）
    test_equals('list_types entry draft', $types['entry']['draft'], true);
    test_equals('list_types entry setting', $types['entry']['setting'], [
        'use_approve'   => false,
        'use_pictures'  => true,
        'use_thumbnail' => true,
        'default_code'  => 'YmdHis',
        'text_type'     => 'wysiwyg',
    ]);
    test_equals('list_types page approve', $types['page']['setting']['use_approve'], true);
    test_equals('list_types page pictures', $types['page']['setting']['use_pictures'], false);
}

// 管理者だけのツールと、属性のテスト
{
    // データ
    db_insert([
        'insert_into' => DATABASE_PREFIX . 'menus',
        'values'      => [
            'created'  => localdate('Y-m-d H:i:s'),
            'modified' => localdate('Y-m-d H:i:s'),
            'enabled'  => 0,
            'title'    => 'テストメニュー2',
            'url'      => '/page/test2',
            'sort'     => 2,
        ],
    ]);
    db_insert([
        'insert_into' => DATABASE_PREFIX . 'menus',
        'values'      => [
            'created'  => localdate('Y-m-d H:i:s'),
            'modified' => localdate('Y-m-d H:i:s'),
            'enabled'  => 1,
            'title'    => 'テストメニュー1',
            'url'      => '/page/test1',
            'sort'     => 1,
        ],
    ]);
    db_insert([
        'insert_into' => DATABASE_PREFIX . 'attributes',
        'values'      => [
            'created'    => localdate('Y-m-d H:i:s'),
            'modified'   => localdate('Y-m-d H:i:s'),
            'name'       => 'テスト属性',
            'filterable' => 1,
            'sort'       => 1,
        ],
    ]);
    $test_attribute_id = db_last_insert_id();

    // 確認
    $output = service_mcp_tool_list_menus([], $auth_admin);

    // 結果（並び順で、無効のものも返す）
    test_equals('list_menus', array_column($output['data']['menus'], 'title'), ['テストメニュー1', 'テストメニュー2']);
    test_equals('list_menus enabled', array_column($output['data']['menus'], 'enabled'), [true, false]);

    // 確認
    $output = service_mcp_tool_list_widgets([], $auth_admin);

    // 結果（前提データのウィジェットがある）
    test_contains('list_widgets', implode(',', array_column($output['data']['widgets'], 'code')), 'public_home');

    // 確認（属性は、エントリーの入力画面で投稿者も選べるので、投稿者のトークンでも読める）
    $output = service_mcp_tool_list_attributes([], $auth_read);

    // 結果
    test_equals('list_attributes', $output['data']['attributes'], [
        [
            'id'         => intval($test_attribute_id),
            'name'       => 'テスト属性',
            'filterable' => true,
            'memo'       => null,
        ],
    ]);

    // 確認（エントリーの属性は get_entry で返す）
    db_insert([
        'insert_into' => DATABASE_PREFIX . 'attribute_sets',
        'values'      => [
            'attribute_id' => $test_attribute_id,
            'entry_id'     => $draft_id,
        ],
    ]);
    $output = service_mcp_tool_get_entry(['id' => $draft_id], $auth_read);

    // 結果
    test_equals('get_entry attributes', $output['data']['entry']['attributes'], [
        [
            'id'   => intval($test_attribute_id),
            'name' => 'テスト属性',
        ],
    ]);

    // 確認（属性の無いエントリー）
    $output = service_mcp_tool_get_entry(['id' => $page_id], $auth_read);

    // 結果
    test_equals('get_entry no attributes', $output['data']['entry']['attributes'], []);
}

// 画像のフィールドの値（アップロードで登録される。MCPからは変えない）
db_insert([
    'insert_into' => DATABASE_PREFIX . 'field_sets',
    'values'      => [
        'field_id' => $field_ids[2],
        'entry_id' => $draft_id,
        'text'     => 'picture.jpg',
    ],
]);

// 下書きの更新テスト（入力の誤り）
{
    // データ
    $entries  = model('select_entries', [
        'where' => 'id = ' . intval($draft_id),
    ]);
    $modified = $entries[0]['modified'];

    // 結果
    $output = service_mcp_tool_update_draft(['modified' => $modified], $auth_draft);
    test_not_equals('update_draft no id', $output['error'], null);

    $output = service_mcp_tool_update_draft(['id' => $draft_id], $auth_draft);
    test_not_equals('update_draft no modified', $output['error'], null);

    $output = service_mcp_tool_update_draft(['id' => 99999, 'modified' => $modified], $auth_draft);
    test_equals('update_draft not found', $output['error'], 'エントリーが見つかりません。');

    $output = service_mcp_tool_update_draft(['id' => $draft_id, 'modified' => '2000-01-01 00:00:00', 'title' => 'テスト'], $auth_draft);
    test_contains('update_draft modified mismatch', $output['error'], 'get_entry');

    $output = service_mcp_tool_update_draft(['id' => $draft_id, 'modified' => $modified, 'title' => ''], $auth_draft);
    test_equals('update_draft empty title', $output['error'], 'タイトルが入力されていません。');

    $output = service_mcp_tool_update_draft(['id' => $draft_id, 'modified' => $modified, 'fields' => [$field_ids[2] => 'b.jpg']], $auth_draft);
    test_contains('update_draft image field no media', $output['error'], '見つかりません');
}

// 下書きの更新テスト（指定した項目だけ変わる）
{
    // データ
    $entries  = model('select_entries', [
        'where' => 'id = ' . intval($draft_id),
    ]);
    $modified = $entries[0]['modified'];

    // 確認
    $output = service_mcp_tool_update_draft([
        'id'       => $draft_id,
        'modified' => $modified,
        'title'    => 'テスト下書き（更新）',
    ], $auth_draft);

    // 結果
    test_equals('update_draft title only', $output['error'], null);

    $entries = model('select_entries', [
        'where' => 'entries.id = ' . intval($draft_id),
    ], [
        'associate' => true,
    ]);
    $entry = $entries[0];

    test_equals('update_draft title', $entry['title'], 'テスト下書き（更新）');
    test_equals('update_draft keep text', $entry['text'], '<p>本文</p>');
    test_equals('update_draft keep datetime', $entry['datetime'], '2026-11-11 10:00:00');
    test_equals('update_draft keep categories', count($entry['category_sets']), 1);
    test_equals('update_draft keep field', $entry['field_sets'][$field_ids[1]], "A\nC");
    test_equals('update_draft keep image field', $entry['field_sets'][$field_ids[2]], 'picture.jpg');
    test_equals('update_draft keep public', $entry['public'], 'none');

    // 結果（操作ログが記録されること）
    $logs = model('select_logs', [
        'where' => 'model = ' . db_escape('entries') . ' AND exec = ' . db_escape('update'),
    ]);

    test_equals('update_draft log', count($logs), 1);
}

// 下書きの更新テスト（カテゴリー・フィールド・本文を変える）
{
    // データ
    $entries  = model('select_entries', [
        'where' => 'id = ' . intval($draft_id),
    ]);
    $modified = $entries[0]['modified'];

    // 確認
    $output = service_mcp_tool_update_draft([
        'id'         => $draft_id,
        'modified'   => $modified,
        'code'       => 'mcp_draft2',
        'text'       => '',
        'categories' => [],
        'fields'     => [
            $field_ids[0] => '変更',
            $field_ids[1] => null,
        ],
    ], $auth_draft);

    // 結果
    test_equals('update_draft change', $output['error'], null);

    $entries = model('select_entries', [
        'where' => 'entries.id = ' . intval($draft_id),
    ], [
        'associate' => true,
    ]);
    $entry = $entries[0];

    test_equals('update_draft code', $entry['code'], 'mcp_draft2');
    test_equals('update_draft empty text', $entry['text'], null);
    test_equals('update_draft empty categories', $entry['category_sets'], []);
    test_equals('update_draft field', $entry['field_sets'][$field_ids[0]], '変更');
    test_array_not_haskey('update_draft empty field', $entry['field_sets'], $field_ids[1]);

    // 結果（画像のフィールドの値が残り、重複しないこと）
    $field_sets = model('select_field_sets', [
        'where' => 'entry_id = ' . intval($draft_id) . ' AND field_id = ' . intval($field_ids[2]),
    ]);

    test_equals('update_draft image field not duplicated', count($field_sets), 1);
    test_equals('update_draft image field value', $field_sets[0]['text'], 'picture.jpg');
}

// 下書きの更新テスト（非公開でないエントリー・対象外の型）
{
    // データ
    db_update([
        'update' => DATABASE_PREFIX . 'entries',
        'set'    => [
            'public' => 'all',
        ],
        'where'  => 'id = ' . intval($page_id),
    ]);
    $entries = model('select_entries', [
        'where' => 'id = ' . intval($page_id),
    ]);

    // 確認
    $output = service_mcp_tool_update_draft([
        'id'       => $page_id,
        'modified' => $entries[0]['modified'],
        'title'    => 'テスト',
    ], $auth_draft);

    // 結果
    test_equals('update_draft public', $output['error'], '非公開のエントリーだけ更新できます。');

    // データ（下書きを作れない型のエントリー）
    db_update([
        'update' => DATABASE_PREFIX . 'entries',
        'set'    => [
            'public'  => 'none',
            'type_id' => 4,
        ],
        'where'  => 'id = ' . intval($page_id),
    ]);

    // 確認
    $output = service_mcp_tool_update_draft([
        'id'       => $page_id,
        'modified' => $entries[0]['modified'],
        'title'    => 'テスト',
    ], $auth_draft);

    // 結果
    test_equals('update_draft type', $output['error'], 'この型のエントリーは更新できません。');
}

// 下書きの画像・公開期間・属性・コメントの受付テスト
{
    // データ（ストレージをファイルにして、テスト用のディレクトリに向ける）
    $config_backup = [
        'storage_type' => $GLOBALS['config']['storage_type'],
        'file_target'  => $GLOBALS['config']['file_target'],
    ];
    $GLOBALS['config']['storage_type']         = 'file';
    $GLOBALS['config']['file_target']['media'] = 'files/tests/mcp_medias/';
    $GLOBALS['config']['file_target']['entry'] = 'files/tests/mcp_entries/';
    $GLOBALS['config']['file_target']['field'] = 'files/tests/mcp_fields/';

    $test_media = $GLOBALS['config']['file_target']['media'];
    $test_entry = $GLOBALS['config']['file_target']['entry'];
    $test_field = $GLOBALS['config']['file_target']['field'];

    directory_mkdir($test_media . 'sub');
    file_put_contents($test_media . 'a.jpg', 'a');
    file_put_contents($test_media . 'b.png', 'b');
    file_put_contents($test_media . 'thumb.jpg', 'thumb');
    file_put_contents($test_media . 'doc.pdf', 'pdf');
    file_put_contents($test_media . 'sub/a.jpg', 'sub-a');

    // ファイルのフィールド
    db_insert([
        'insert_into' => DATABASE_PREFIX . 'fields',
        'values'      => [
            'created'  => localdate('Y-m-d H:i:s'),
            'modified' => localdate('Y-m-d H:i:s'),
            'type_id'  => 1,
            'name'     => 'テストファイル',
            'kind'     => 'file',
            'sort'     => 4,
        ],
    ]);
    $test_file_field_id  = intval(db_last_insert_id());
    $test_image_field_id = intval($field_ids[2]);

    // 確認（作成）
    $output = service_mcp_tool_create_draft([
        'type'         => 'entry',
        'title'        => 'テスト画像付き',
        'code'         => 'mcp_media',
        'thumbnail'    => 'thumb.jpg',
        'pictures'     => ['a.jpg', 'b.png'],
        'attributes'   => [intval($test_attribute_id)],
        'public_begin' => '2026-12-01 09:00',
        'public_end'   => '2026-12-31 18:00',
        'comment'      => 'opened',
        'fields'       => [
            $test_image_field_id => 'sub/a.jpg',
            $test_file_field_id  => 'doc.pdf',
        ],
    ], $auth_draft);

    // 結果
    test_equals('draft media create error', $output['error'], null);

    $media_id = $output['data']['entry']['id'];
    $entries  = model('select_entries', [
        'where' => 'entries.id = ' . intval($media_id),
    ], [
        'associate' => true,
    ]);
    $entry = $entries[0];

    test_equals('draft media public', $entry['public'], 'none');
    test_equals('draft media thumbnail', $entry['thumbnail'], 'thumb.jpg');
    test_equals('draft media pictures', $entry['pictures'], "a.jpg\nb.png");
    test_equals('draft media public_begin', $entry['public_begin'], '2026-12-01 09:00:00');
    test_equals('draft media public_end', $entry['public_end'], '2026-12-31 18:00:00');
    test_equals('draft media comment', $entry['comment'], 'opened');
    test_equals('draft media attributes', array_map('intval', array_column($entry['attribute_sets'], 'attribute_id')), [intval($test_attribute_id)]);
    test_equals('draft media field image', $entry['field_sets'][$test_image_field_id], 'a.jpg');
    test_equals('draft media field file', $entry['field_sets'][$test_file_field_id], 'doc.pdf');
    test_equals('draft media thumbnail file', file_get_contents($test_entry . $media_id . '/thumb.jpg'), 'thumb');
    test_equals('draft media picture file', file_get_contents($test_entry . $media_id . '/b.png'), 'b');
    test_equals('draft media field image file', file_get_contents($test_field . $media_id . '_' . $test_image_field_id . '/a.jpg'), 'sub-a');
    test_equals('draft media field file file', file_get_contents($test_field . $media_id . '_' . $test_file_field_id . '/doc.pdf'), 'pdf');
    test_equals('draft media source remains', is_file($test_media . 'a.jpg'), true);

    // 確認（入力の誤り。何も登録されないこと）
    $test_errors = [
        'thumbnail not found'      => [
            'thumbnail' => 'nothing.jpg',
        ],
        'thumbnail not image'      => [
            'thumbnail' => 'doc.pdf',
        ],
        'thumbnail invalid path'   => [
            'thumbnail' => '../a.jpg',
        ],
        'thumbnail not string'     => [
            'thumbnail' => 1,
        ],
        'pictures duplicate'       => [
            'pictures' => ['a.jpg', 'sub/a.jpg'],
        ],
        'pictures not found'       => [
            'pictures' => ['a.jpg', 'nothing.jpg'],
        ],
        'pictures not array'       => [
            'pictures' => 'a.jpg',
        ],
        'thumbnail as picture'     => [
            'thumbnail' => 'a.jpg',
            'pictures'  => ['a.jpg'],
        ],
        'field image not image'    => [
            'fields' => [
                $test_image_field_id => 'doc.pdf',
            ],
        ],
        'field file not string'    => [
            'fields' => [
                $test_file_field_id => ['doc.pdf'],
            ],
        ],
        'attributes not found'     => [
            'attributes' => [99999],
        ],
        'attributes not integer'   => [
            'attributes' => ['1'],
        ],
        'public_begin format'      => [
            'public_begin' => '2026/12/01',
        ],
        'comment invalid'          => [
            'comment' => 'always',
        ],
    ];
    $entry_count = count(model('select_entries', []));
    foreach ($test_errors as $title => $arguments) {
        $output = service_mcp_tool_create_draft($arguments + [
            'type'  => 'entry',
            'title' => 'テスト誤り',
            'code'  => 'mcp_error',
        ], $auth_draft);

        test_not_equals('draft media ' . $title, $output['error'], null);
    }
    test_equals('draft media errors not created', count(model('select_entries', [])), $entry_count);

    // 確認（設定で使わない型）
    $output = service_mcp_tool_create_draft([
        'type'     => 'page',
        'title'    => 'テストページ画像',
        'code'     => 'mcp/page_media',
        'pictures' => ['a.jpg'],
    ], $auth_draft);
    test_equals('draft media page pictures disabled', $output['error'], 'この型は画像を使わない設定です。');

    $output = service_mcp_tool_create_draft([
        'type'      => 'page',
        'title'     => 'テストページ画像',
        'code'      => 'mcp/page_media',
        'thumbnail' => 'a.jpg',
    ], $auth_draft);
    test_equals('draft media page thumbnail disabled', $output['error'], 'この型はサムネイルを使わない設定です。');

    // 確認（サムネイルだけ変えると、画像はそのまま残る）
    $entries = model('select_entries', [
        'where' => 'id = ' . intval($media_id),
    ]);
    $output  = service_mcp_tool_update_draft([
        'id'        => $media_id,
        'modified'  => $entries[0]['modified'],
        'thumbnail' => 'sub/a.jpg',
    ], $auth_draft);

    // 結果（a.jpg は画像にもあるので、上書きし合わないよう断る）
    test_contains('draft media update thumbnail same as picture', $output['error'], 'a.jpg');

    // 確認（画像を入れ替える。今の画像を残し、メディアから足す。含めなかった画像は消える）
    $output = service_mcp_tool_update_draft([
        'id'       => $media_id,
        'modified' => $entries[0]['modified'],
        'pictures' => ['b.png', 'sub/a.jpg'],
    ], $auth_draft);

    // 結果（a.jpg は消えてから、sub/a.jpg の内容で同じ名前に保存される）
    test_equals('draft media update pictures error', $output['error'], null);

    $entries = model('select_entries', [
        'where' => 'entries.id = ' . intval($media_id),
    ], [
        'associate' => true,
    ]);
    $entry = $entries[0];

    test_equals('draft media update pictures', $entry['pictures'], "b.png\na.jpg");
    test_equals('draft media update picture kept', file_get_contents($test_entry . $media_id . '/b.png'), 'b');
    test_equals('draft media update picture replaced', file_get_contents($test_entry . $media_id . '/a.jpg'), 'sub-a');
    test_equals('draft media update thumbnail kept', $entry['thumbnail'], 'thumb.jpg');

    // 確認（削除と解除。サムネイル・画像のフィールド・公開期間・属性）
    $output = service_mcp_tool_update_draft([
        'id'           => $media_id,
        'modified'     => $entry['modified'],
        'thumbnail'    => '',
        'attributes'   => [],
        'public_begin' => '',
        'comment'      => 'closed',
        'fields'       => [
            $test_image_field_id => '',
        ],
    ], $auth_draft);

    // 結果
    test_equals('draft media delete error', $output['error'], null);

    $entries = model('select_entries', [
        'where' => 'entries.id = ' . intval($media_id),
    ], [
        'associate' => true,
    ]);
    $entry = $entries[0];

    test_equals('draft media delete thumbnail', $entry['thumbnail'], null);
    test_equals('draft media delete thumbnail file', is_file($test_entry . $media_id . '/thumb.jpg'), false);
    test_equals('draft media delete keeps pictures', $entry['pictures'], "b.png\na.jpg");
    test_equals('draft media delete keeps picture file', is_file($test_entry . $media_id . '/a.jpg'), true);
    test_array_not_haskey('draft media delete field image', $entry['field_sets'], $test_image_field_id);
    test_equals('draft media delete field image file', is_file($test_field . $media_id . '_' . $test_image_field_id . '/a.jpg'), false);
    test_equals('draft media keep field file', $entry['field_sets'][$test_file_field_id], 'doc.pdf');
    test_equals('draft media delete attributes', $entry['attribute_sets'], []);
    test_equals('draft media delete public_begin', $entry['public_begin'], null);
    test_equals('draft media keep public_end', $entry['public_end'], '2026-12-31 18:00:00');
    test_equals('draft media update comment', $entry['comment'], 'closed');
    test_equals('draft media still none', $entry['public'], 'none');

    // 確認（本文にメディアのHTMLを入れる。表示されない本文の形式では断る）
    $test_html = service_mcp_media_html('b.png', $GLOBALS['config']['storage_url'] . '/' . $test_media . 'b.png', null);

    $output = service_mcp_tool_update_draft([
        'id'        => $media_id,
        'modified'  => $entry['modified'],
        'text'      => "<p>前</p>\n" . $test_html . "\n<p>後</p>",
        'text_type' => 'html',
    ], $auth_draft);
    test_equals('draft media insert html', $output['error'], null);

    $entries = model('select_entries', [
        'where' => 'id = ' . intval($media_id),
    ]);
    test_contains('draft media insert html text', $entries[0]['text'], $test_html);

    // 結果（本文はそのままで、形式だけ複数行入力にするのも断る）
    $output = service_mcp_tool_update_draft([
        'id'        => $media_id,
        'modified'  => $entries[0]['modified'],
        'text_type' => 'textarea',
    ], $auth_draft);
    test_contains('draft media insert html textarea', $output['error'], '複数行入力');

    $output = service_mcp_tool_create_draft([
        'type'      => 'entry',
        'title'     => 'テスト複数行入力',
        'code'      => 'mcp_textarea',
        'text_type' => 'none',
        'text'      => $test_html,
    ], $auth_draft);
    test_not_equals('draft media insert html none', $output['error'], null);

    // 結果（メディアのHTMLでなければ、複数行入力でもタグを書ける）
    $output = service_mcp_tool_create_draft([
        'type'      => 'entry',
        'title'     => 'テスト複数行入力',
        'code'      => 'mcp_textarea',
        'text_type' => 'textarea',
        'text'      => '<img> というタグの説明',
    ], $auth_draft);
    test_equals('draft media textarea other tag', $output['error'], null);

    // 後始末
    directory_rmdir($test_media);
    directory_rmdir($test_entry);
    directory_rmdir($test_field);

    $GLOBALS['config']['storage_type'] = $config_backup['storage_type'];
    $GLOBALS['config']['file_target']  = $config_backup['file_target'];
}

// プラグインの型の下書きテスト（データの定義があれば作れる）
{
    // データ（テスト用の型。types は前提データのあるテーブルなので、この行だけを最後に消す）
    db_insert([
        'insert_into' => DATABASE_PREFIX . 'types',
        'values'      => [
            'created'  => localdate('Y-m-d H:i:s'),
            'modified' => localdate('Y-m-d H:i:s'),
            'code'     => 'mcptesttype',
            'name'     => 'テストの型',
            'sort'     => 99,
        ],
    ]);
    $plugin_backup = isset($GLOBALS['plugin']) ? $GLOBALS['plugin'] : null;

    // 確認（data を持たないプラグインの型は、読めるが下書きは作れない）
    $GLOBALS['plugin']['mcptestplugin'] = [
        'code' => 'mcptestplugin',
    ];

    $output = service_mcp_tool_list_types([], $auth_draft);
    $types  = array_column($output['data']['types'], null, 'code');

    test_equals('plugin type without data listed', $types['mcptesttype']['draft'], false);
    test_array_not_haskey('plugin type without data setting', $types['mcptesttype'], 'setting');
    test_equals('plugin type without data tools enum', in_array('mcptesttype', service_mcp_tools_draft()[0]['inputSchema']['properties']['type']['enum'], true), false);

    $output = service_mcp_tool_create_draft([
        'type'  => 'mcptesttype',
        'title' => 'テストの型の下書き',
        'code'  => 'mcp_type1',
    ], $auth_draft);
    test_contains('plugin type without data create', $output['error'], 'typeには');

    // 確認（data を持つプラグインの型。設定はプラグインの設定から読む。form が無ければ edit_url は無し）
    $GLOBALS['plugin']['mcptestplugin'] = [
        'code'            => 'mcptestplugin',
        'data'            => [
            'mcptesttype' => [
                'type'    => 'mcptesttype',
                'name'    => 'テストの型',
                'setting' => [
                    'use_approve'  => 'use_approve',
                    'default_code' => 'default_code',
                    'text_type'    => 'text_type',
                ],
            ],
        ],
        'setting'         => [
            'use_approve' => 1,
            'text_type'   => 'html',
        ],
        'setting_default' => [
            'default_code' => 'Ymd',
        ],
    ];

    $output = service_mcp_tool_list_types([], $auth_draft);
    $types  = array_column($output['data']['types'], null, 'code');

    test_equals('plugin type with data draft', $types['mcptesttype']['draft'], true);
    test_equals('plugin type with data setting', $types['mcptesttype']['setting'], [
        'use_approve'   => true,
        'use_pictures'  => false,
        'use_thumbnail' => false,
        'default_code'  => 'Ymd',
        'text_type'     => 'html',
    ]);
    test_equals('plugin type with data tools enum', in_array('mcptesttype', service_mcp_tools_draft()[0]['inputSchema']['properties']['type']['enum'], true), true);

    $output = service_mcp_tool_create_draft([
        'type'  => 'mcptesttype',
        'title' => 'テストの型の下書き',
    ], $auth_draft);

    // 結果
    test_equals('plugin type create error', $output['error'], null);
    test_equals('plugin type create approved', $output['data']['entry']['approved'], false);
    test_equals('plugin type create public', $output['data']['entry']['public'], 'none');
    test_equals('plugin type create edit_url', $output['data']['entry']['edit_url'], null);
    test_regexp('plugin type create default code', $output['data']['entry']['code'], '^\d{8}$');

    $plugin_type_id = $output['data']['entry']['id'];
    $entries        = model('select_entries', [
        'where' => 'id = ' . intval($plugin_type_id),
    ]);
    test_equals('plugin type create text_type', $entries[0]['text_type'], 'html');

    // 結果（設定で使わない画像は断る）
    $output = service_mcp_tool_update_draft([
        'id'       => $plugin_type_id,
        'modified' => $entries[0]['modified'],
        'pictures' => [],
    ], $auth_draft);
    test_equals('plugin type pictures disabled', $output['error'], 'この型は画像を使わない設定です。');

    // 結果（更新できる）
    $output = service_mcp_tool_update_draft([
        'id'       => $plugin_type_id,
        'modified' => $entries[0]['modified'],
        'title'    => 'テストの型の下書き（更新）',
    ], $auth_draft);
    test_equals('plugin type update', $output['error'], null);

    // 結果（data を外すと、作った下書きも更新できない）
    unset($GLOBALS['plugin']['mcptestplugin']);

    $entries = model('select_entries', [
        'where' => 'id = ' . intval($plugin_type_id),
    ]);
    $output  = service_mcp_tool_update_draft([
        'id'       => $plugin_type_id,
        'modified' => $entries[0]['modified'],
        'title'    => 'テスト',
    ], $auth_draft);
    test_equals('plugin type update without data', $output['error'], 'この型のエントリーは更新できません。');

    // 後始末
    if ($plugin_backup === null) {
        unset($GLOBALS['plugin']);
    } else {
        $GLOBALS['plugin'] = $plugin_backup;
    }
    db_query('DELETE FROM ' . DATABASE_PREFIX . 'types WHERE code = ' . db_escape('mcptesttype') . ';');
}

// 既存データ削除
db_query('TRUNCATE TABLE ' . DATABASE_PREFIX . 'mcp_tokens;');
db_query('TRUNCATE TABLE ' . DATABASE_PREFIX . 'entries;');
db_query('TRUNCATE TABLE ' . DATABASE_PREFIX . 'fields;');
db_query('TRUNCATE TABLE ' . DATABASE_PREFIX . 'field_sets;');
db_query('TRUNCATE TABLE ' . DATABASE_PREFIX . 'categories;');
db_query('TRUNCATE TABLE ' . DATABASE_PREFIX . 'category_sets;');
db_query('TRUNCATE TABLE ' . DATABASE_PREFIX . 'attribute_sets;');
db_query('TRUNCATE TABLE ' . DATABASE_PREFIX . 'attributes;');
db_query('TRUNCATE TABLE ' . DATABASE_PREFIX . 'comments;');
db_query('TRUNCATE TABLE ' . DATABASE_PREFIX . 'menus;');
db_query('TRUNCATE TABLE ' . DATABASE_PREFIX . 'logs;');

// 設定を元に戻す
foreach ($setting_backup as $key => $value) {
    if ($value === null) {
        unset($GLOBALS['setting'][$key]);
    } else {
        $GLOBALS['setting'][$key] = $value;
    }
}

// コードカバレッジの記録を終了
if (!isset($_GET['_test'])) {
    $coverages = service_coverage_end();

    service_coverage_output($coverages, [
        'plugins/mcp/app/services/mcp.php',
    ]);
}
