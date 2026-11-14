<?php

import('app/services/entry.php');
import('app/services/storage.php');
import('app/services/media.php');
import('plugins/mcp/app/services/mcp_token.php');

/**
 * 応答して終了
 *
 * @param int        $status
 * @param array|null $body
 * @param array      $headers
 *
 * @return void
 */
function service_mcp_respond($status, $body, $headers = [])
{
    // service_mcp_guard() の出力を捨てて、応答したことを記録する
    $GLOBALS['mcp_responded'] = true;
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    http_response_code($status);
    header('Cache-Control: no-store');

    foreach ($headers as $header) {
        header($header);
    }

    if ($body !== null) {
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    exit;
}

/**
 * リクエストヘッダーの取得
 *
 * 名前は小文字にそろえる（ヘッダー名は大文字・小文字を区別しないため）
 *
 * @param array|null $server
 *
 * @return array
 */
function service_mcp_headers($server = null)
{
    $headers = [];

    if ($server === null) {
        $server = $_SERVER;

        // mod_php ではAuthorizationが $_SERVER に入らないことがあるので、取得できれば補う
        if (function_exists('getallheaders')) {
            foreach (getallheaders() as $name => $value) {
                $headers[strtolower($name)] = $value;
            }
        }
    }

    foreach ($server as $key => $value) {
        if (strpos($key, 'HTTP_') === 0) {
            $headers[strtolower(str_replace('_', '-', substr($key, 5)))] = $value;
        }
    }

    // CGIで動かしているときは、.htaccess で渡したAuthorizationが REDIRECT_ 付きになることがある
    if (!isset($headers['authorization']) && isset($server['REDIRECT_HTTP_AUTHORIZATION'])) {
        $headers['authorization'] = $server['REDIRECT_HTTP_AUTHORIZATION'];
    }

    return $headers;
}

/**
 * Authorizationヘッダーからトークンを取得
 *
 * @param array $headers
 *
 * @return string|null
 */
function service_mcp_bearer($headers)
{
    if (empty($headers['authorization'])) {
        return null;
    }
    if (!preg_match('/^Bearer +([A-Za-z0-9\-._~+\/]+=*)$/i', trim($headers['authorization']), $matches)) {
        return null;
    }

    return $matches[1];
}

/**
 * Originヘッダーの確認
 *
 * 付いていなければ通す（AIのクライアントは通常付けない）。付いていれば、サイト自身からのものだけを通す
 *
 * @param array  $headers
 * @param string $http_url
 *
 * @return bool
 */
function service_mcp_origin($headers, $http_url)
{
    if (!isset($headers['origin'])) {
        return true;
    }

    $url = parse_url($http_url);
    if (empty($url['scheme']) || empty($url['host'])) {
        return false;
    }

    $origin = $url['scheme'] . '://' . $url['host'] . (isset($url['port']) ? ':' . $url['port'] : '');

    return strtolower($headers['origin']) === strtolower($origin);
}

/**
 * トークンの認証
 *
 * トークンが有効で、ユーザーが有効かつ権限が足りていれば、トークン・ユーザー・権限を返す
 *
 * @param string|null $token
 *
 * @return array|null
 */
function service_mcp_authenticate($token)
{
    if ($token === null || $token === '') {
        return null;
    }

    // トークンを取得
    $mcp_tokens = model('select_mcp_tokens', [
        'where' => [
            'token = :token AND (expire IS NULL OR expire > :now)',
            [
                'token' => service_mcp_token_hash($token),
                'now'   => localdate('Y-m-d H:i:s'),
            ],
        ],
    ]);
    if (empty($mcp_tokens)) {
        return null;
    }

    // ユーザーを取得
    $users = model('select_users', [
        'where' => [
            'id = :id AND enabled = 1',
            [
                'id' => $mcp_tokens[0]['user_id'],
            ],
        ],
    ]);
    if (empty($users)) {
        return null;
    }

    // 権限を取得
    $authorities = model('select_authorities', [
        'where' => [
            'id = :id',
            [
                'id' => $users[0]['authority_id'],
            ],
        ],
    ]);
    if (empty($authorities) || $authorities[0]['power'] < $GLOBALS['plugin']['mcp']['power']) {
        return null;
    }

    // 最終利用日時を記録（更新日時は変えない）
    model('update_mcp_tokens', [
        'set'   => [
            'accessed' => localdate('Y-m-d H:i:s'),
            'modified' => false,
        ],
        'where' => [
            'id = :id',
            [
                'id' => $mcp_tokens[0]['id'],
            ],
        ],
    ]);

    return [
        'token'     => $mcp_tokens[0],
        'user'      => $users[0],
        'authority' => $authorities[0],
    ];
}

/**
 * JSON-RPCの結果
 *
 * @param mixed $id
 * @param array $result
 *
 * @return array
 */
function service_mcp_result($id, $result)
{
    return [
        'status' => 200,
        'body'   => [
            'jsonrpc' => '2.0',
            'id'      => $id,
            'result'  => $result,
        ],
    ];
}

/**
 * JSON-RPCのエラー
 *
 * @param mixed      $id
 * @param int        $code
 * @param string     $message
 * @param array|null $data
 * @param int        $status
 *
 * @return array
 */
function service_mcp_error($id, $code, $message, $data = null, $status = 200)
{
    $error = [
        'code'    => $code,
        'message' => $message,
    ];
    if ($data !== null) {
        $error['data'] = $data;
    }

    return [
        'status' => $status,
        'body'   => [
            'jsonrpc' => '2.0',
            'id'      => $id,
            'error'   => $error,
        ],
    ];
}

/**
 * Mcp-Name などのヘッダーの値を取得
 *
 * =?base64?...?= の形なら、デコードして返す
 *
 * @param string|null $value
 *
 * @return string|null
 */
function service_mcp_header_value($value)
{
    if ($value === null) {
        return null;
    }
    if (preg_match('/^=\?base64\?([A-Za-z0-9+\/]*=*)\?=$/', $value, $matches)) {
        $decoded = base64_decode($matches[1], true);

        return $decoded === false ? null : $decoded;
    }

    return $value;
}

/**
 * サーバーの情報
 *
 * @return array
 */
function service_mcp_server_info()
{
    return [
        'name'    => 'freo2',
        'version' => $GLOBALS['plugin']['mcp']['version'],
    ];
}

/**
 * サーバーの使い方の案内（AIに渡す）
 *
 * @return string
 */
function service_mcp_instructions()
{
    return 'freo2（CMS）のコンテンツを読むためのサーバーです。list_typesで型を確かめてから、search_entriesで探し、get_entryで本文を取得してください。'
         . 'サイトの趣旨や設定はget_site、コメントはlist_comments、本文に使える画像などはlist_mediaで確かめられます（本文に入れるときはinsert_htmlを使います）。'
         . '属性はlist_attributesで確かめられます。'
         . 'トークンの範囲によっては、create_draftとupdate_draftで非公開の下書きを作れます。公開は、人が管理画面で内容を確かめてから行います。';
}

/**
 * 応答の前に終了したときの備え
 *
 * サービスの error() やPHPのエラーで終了したときに、出力を捨ててJSON-RPCのエラーに包む
 *
 * @param mixed $id
 *
 * @return void
 */
function service_mcp_guard($id)
{
    $GLOBALS['mcp_responded'] = false;

    ob_start();

    register_shutdown_function(function () use ($id) {
        if ($GLOBALS['mcp_responded']) {
            return;
        }

        $output  = '';
        while (ob_get_level() > 0) {
            $output .= ob_get_clean();
        }

        // error() の出力（freo2の形のJSON）から、メッセージを取り出す
        $message = 'Internal error';
        if (preg_match('/\{"status":"ERROR".*$/s', $output, $matches)) {
            $data = json_decode($matches[0], true);
            if (isset($data['message']) && is_string($data['message'])) {
                $message = $data['message'];
            }
        }

        if (!headers_sent()) {
            http_response_code(200);
            header('Cache-Control: no-store');
            header('Content-Type: application/json; charset=utf-8');
        }

        echo json_encode(service_mcp_error($id, -32603, $message)['body'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    });
}

/**
 * メッセージの処理
 *
 * 2026-07-28 の版（毎回の _meta に版が入る）と、initialize で始める以前の版の両方に答える
 *
 * @param mixed      $message
 * @param array      $headers
 * @param array|null $auth
 *
 * @return array status（HTTPのステータス）と body（null なら本文なし）
 */
function service_mcp_handle($message, $headers, $auth)
{
    $plugin    = $GLOBALS['plugin']['mcp'];
    $supported = array_merge($plugin['versions'], $plugin['legacy_versions']);

    // JSON-RPCの形を確認（まとめて送る形式は受けない）
    if (!is_array($message) || !isset($message['jsonrpc']) || $message['jsonrpc'] !== '2.0' || !isset($message['method']) || !is_string($message['method'])) {
        return service_mcp_error(null, -32600, 'Invalid Request', null, 400);
    }

    $id           = array_key_exists('id', $message) ? $message['id'] : null;
    $notification = !array_key_exists('id', $message);
    $method       = $message['method'];
    $params       = isset($message['params']) && is_array($message['params']) ? $message['params'] : [];
    $meta         = isset($params['_meta']) && is_array($params['_meta']) ? $params['_meta'] : [];

    if ($method === 'initialize') {
        // 以前の版の初期化（求められた版に対応していれば同じ版、そうでなければ既定の版を返す）
        $requested = isset($params['protocolVersion']) ? $params['protocolVersion'] : null;

        return service_mcp_result($id, [
            'protocolVersion' => in_array($requested, $plugin['legacy_versions'], true) ? $requested : $plugin['legacy_versions'][0],
            'capabilities'    => [
                'tools' => new stdClass(),
            ],
            'serverInfo'      => service_mcp_server_info(),
            'instructions'    => service_mcp_instructions(),
        ]);
    }

    if (isset($meta['io.modelcontextprotocol/protocolVersion'])) {
        // 2026-07-28 の版
        $version = $meta['io.modelcontextprotocol/protocolVersion'];
        if (!in_array($version, $plugin['versions'], true)) {
            return service_mcp_error($id, -32022, 'Unsupported protocol version', [
                'supported' => $supported,
                'requested' => $version,
            ], 400);
        }

        $header = isset($headers['mcp-protocol-version']) ? $headers['mcp-protocol-version'] : null;
        if ($header !== $version) {
            return service_mcp_error($id, -32020, 'Header mismatch: MCP-Protocol-Version header value does not match body value', null, 400);
        }

        if ($notification) {
            return [
                'status' => 202,
                'body'   => null,
            ];
        }

        $header = isset($headers['mcp-method']) ? $headers['mcp-method'] : null;
        if ($header !== $method) {
            return service_mcp_error($id, -32020, 'Header mismatch: Mcp-Method header value does not match body value', null, 400);
        }

        if ($method === 'tools/call') {
            $header = service_mcp_header_value(isset($headers['mcp-name']) ? $headers['mcp-name'] : null);
            if ($header === null || !isset($params['name']) || $header !== $params['name']) {
                return service_mcp_error($id, -32020, 'Header mismatch: Mcp-Name header value does not match body value', null, 400);
            }
        }

        if (!isset($meta['io.modelcontextprotocol/clientCapabilities']) || !is_array($meta['io.modelcontextprotocol/clientCapabilities'])) {
            return service_mcp_error($id, -32602, 'Invalid params: io.modelcontextprotocol/clientCapabilities is required', null, 400);
        }

        return service_mcp_dispatch($id, $method, $params, $auth, true);
    }

    // 以前の版（版のヘッダーが無ければ 2025-03-26 とみなす）
    $version = isset($headers['mcp-protocol-version']) ? $headers['mcp-protocol-version'] : '2025-03-26';
    if (!in_array($version, $plugin['legacy_versions'], true)) {
        return service_mcp_error($id, -32600, 'Unsupported protocol version', [
            'supported' => $supported,
            'requested' => $version,
        ], 400);
    }

    if ($notification) {
        return [
            'status' => 202,
            'body'   => null,
        ];
    }

    return service_mcp_dispatch($id, $method, $params, $auth, false);
}

/**
 * メソッドごとの処理
 *
 * @param mixed $id
 * @param string $method
 * @param array  $params
 * @param array  $auth
 * @param bool   $modern 2026-07-28 の版なら true
 *
 * @return array
 */
function service_mcp_dispatch($id, $method, $params, $auth, $modern)
{
    if ($method === 'server/discover' && $modern) {
        return service_mcp_result($id, [
            'resultType'        => 'complete',
            'supportedVersions' => $GLOBALS['plugin']['mcp']['versions'],
            'capabilities'      => [
                'tools' => new stdClass(),
            ],
            '_meta'             => [
                'io.modelcontextprotocol/serverInfo' => service_mcp_server_info(),
            ],
            'instructions'      => service_mcp_instructions(),
            'ttlMs'             => 3600000,
            'cacheScope'        => 'public',
        ]);
    }

    if ($method === 'ping' && !$modern) {
        return service_mcp_result($id, new stdClass());
    }

    if ($method === 'tools/list') {
        $result = [
            'tools' => service_mcp_tools($auth),
        ];
        if ($modern) {
            // トークンの範囲で使えるツールが変わるので、キャッシュは同じ認証の中だけにする
            $result = ['resultType' => 'complete'] + $result + [
                'ttlMs'      => 300000,
                'cacheScope' => 'private',
            ];
        }

        return service_mcp_result($id, $result);
    }

    if ($method === 'tools/call') {
        $name      = isset($params['name']) && is_string($params['name']) ? $params['name'] : null;
        $arguments = isset($params['arguments']) && is_array($params['arguments']) ? $params['arguments'] : [];

        if (!in_array($name, array_column(service_mcp_tools($auth), 'name'), true)) {
            return service_mcp_error($id, -32602, 'Unknown tool: ' . $name);
        }

        $function = 'service_mcp_tool_' . $name;
        $output   = $function($arguments, $auth);

        if ($output['error'] !== null) {
            $result = [
                'content' => [
                    [
                        'type' => 'text',
                        'text' => $output['error'],
                    ],
                ],
                'isError' => true,
            ];
        } else {
            $result = [
                'content'           => [
                    [
                        'type' => 'text',
                        'text' => json_encode($output['data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ],
                ],
                'structuredContent' => $output['data'],
                'isError'           => false,
            ];
        }
        if ($modern) {
            $result = ['resultType' => 'complete'] + $result;
        }

        return service_mcp_result($id, $result);
    }

    return service_mcp_error($id, -32601, 'Method not found', null, $modern ? 404 : 200);
}

/**
 * ツールの定義
 *
 * @param array $auth
 *
 * @return array
 */
function service_mcp_tools($auth)
{
    $tools = service_mcp_tools_read();

    // 管理者だけが開ける画面（メニュー・ウィジェット）のツールは、管理者のトークンだけに出す
    if ($auth !== null && $auth['authority']['power'] >= 3) {
        $tools = array_merge($tools, service_mcp_tools_admin());
    }

    // 下書きのツールは、範囲が draft のトークンだけに出す
    if ($auth !== null && $auth['token']['scope'] === 'draft') {
        $tools = array_merge($tools, service_mcp_tools_draft());
    }

    return $tools;
}

/**
 * 下書きを作れる型
 *
 * データの定義（本体の app/config.php と、有効なプラグインの config.php の data）のうち、型を持つものから作る。
 * data を持たないプラグインの型は、読めるが下書きは作れない
 *
 * @return array 型のコードがキー、データの定義が値
 */
function service_mcp_draft_types()
{
    $types = [];
    foreach (app_data() as $code => $data) {
        if (!empty($data['type'])) {
            $types[$data['type']] = $data + ['code' => $code];
        }
    }

    return $types;
}

/**
 * ツールの定義（下書き）
 *
 * @return array
 */
function service_mcp_tools_draft()
{
    $draft_types = array_keys(service_mcp_draft_types());
    $text_types  = array_keys($GLOBALS['config']['option']['entry']['text_type']);
    $comments    = array_keys($GLOBALS['config']['option']['entry']['comment']);

    $properties = [
        'title'        => [
            'type'        => 'string',
            'description' => 'タイトル（100文字以内）。',
        ],
        'text'         => [
            'type'        => 'string',
            'description' => '本文。書き方はtext_typeに合わせます。画像などを入れるときは、list_mediaのinsert_htmlを入れたい位置に置きます（text_typeがtextarea・noneでは使えません）。',
        ],
        'text_type'    => [
            'type'        => 'string',
            'enum'        => $text_types,
            'description' => '本文形式。省略すると、新規では設定の既定値、更新では今の値のままです。',
        ],
        'code'         => [
            'type'        => 'string',
            'description' => 'コード（URLに使われる識別子。半角英数字・_・-・/）。型の中で重複できません。',
        ],
        'datetime'     => [
            'type'        => 'string',
            'description' => '日時（YYYY-MM-DD HH:MM）。',
        ],
        'categories'   => [
            'type'        => 'array',
            'items'       => [
                'type' => 'string',
            ],
            'description' => 'カテゴリーのコードの配列（list_categoriesで確かめる）。指定すると、今のカテゴリーと置き換えます。',
        ],
        'attributes'   => [
            'type'        => 'array',
            'items'       => [
                'type' => 'integer',
            ],
            'description' => '属性のIDの配列（list_attributesで確かめる）。公開範囲を「指定の属性に公開」にするときに使います。指定すると、今の属性と置き換えます。',
        ],
        'public_begin' => [
            'type'        => 'string',
            'description' => '公開開始日時（YYYY-MM-DD HH:MM）。空文字で解除。下書きは非公開のままで、公開範囲は人が管理画面で決めます。',
        ],
        'public_end'   => [
            'type'        => 'string',
            'description' => '公開終了日時（YYYY-MM-DD HH:MM）。空文字で解除。',
        ],
        'comment'      => [
            'type'        => 'string',
            'enum'        => $comments,
            'description' => 'コメントの受付。',
        ],
        'thumbnail'    => [
            'type'        => 'string',
            'description' => 'サムネイルにするメディアのパス（list_mediaのdirectoryとnameをつないだもの。例: images/photo.jpg）。複製して使います。空文字で削除。list_typesの型のsettingでuse_thumbnailがfalseなら使えません。',
        ],
        'pictures'     => [
            'type'        => 'array',
            'items'       => [
                'type' => 'string',
            ],
            'description' => '画像の配列。メディアのパスを指定すると複製して使い、今の画像の名前（get_entryのpictures）を指定するとその画像を残します。指定すると、この並びで置き換えます（含めなかった今の画像は削除）。list_typesの型のsettingでuse_picturesがfalseなら使えません。',
        ],
        'fields'       => [
            'type'                 => 'object',
            'additionalProperties' => [
                'type' => ['string', 'array'],
            ],
            'description'          => 'フィールドの値。キーはフィールドのID（list_fieldsで確かめる）。チェックボックスは文字列の配列。画像・ファイルのフィールド（mediaがtrue）は、メディアのパスを指定すると複製して使い、空文字で削除します。指定しなかったフィールドは、新規では初期値、更新では今の値のままです。',
        ],
    ];

    return [
        [
            'name'        => 'create_draft',
            'title'       => '下書きの作成',
            'description' => '非公開の下書きを作ります。公開範囲は非公開に固定され、公開範囲を選んで公開するのは人が管理画面で行います。公開期間・属性・コメントの受付は入れておけます。画像はlist_mediaで見つけたメディアから付けます。結果のedit_urlを人に伝えてください。',
            'inputSchema' => [
                'type'                 => 'object',
                'properties'           => [
                    'type' => [
                        'type'        => 'string',
                        'enum'        => $draft_types,
                        'description' => '型のコード（list_typesでdraftがtrueの型）。',
                    ],
                ] + $properties,
                'required'             => ['type', 'title'],
                'additionalProperties' => false,
            ],
            'annotations' => [
                'readOnlyHint'    => false,
                'destructiveHint' => false,
                'idempotentHint'  => false,
                'openWorldHint'   => false,
            ],
        ],
        [
            'name'        => 'update_draft',
            'title'       => '下書きの更新',
            'description' => '非公開のエントリーを更新します。公開範囲は変えられません。先にget_entryで取得し、そのmodifiedを指定してください（取得した後にほかの人が更新していれば、更新しません）。指定しなかった項目は今の値のままです。',
            'inputSchema' => [
                'type'                 => 'object',
                'properties'           => [
                    'id'       => [
                        'type'        => 'integer',
                        'description' => 'エントリーのID。',
                    ],
                    'modified' => [
                        'type'        => 'string',
                        'description' => 'get_entryで取得したmodified。',
                    ],
                ] + $properties,
                'required'             => ['id', 'modified'],
                'additionalProperties' => false,
            ],
            'annotations' => [
                'readOnlyHint'    => false,
                'destructiveHint' => true,
                'idempotentHint'  => true,
                'openWorldHint'   => false,
            ],
        ],
    ];
}

/**
 * ツールの定義（読み取り）
 *
 * @return array
 */
function service_mcp_tools_read()
{
    return [
        [
            'name'        => 'list_types',
            'title'       => '型の一覧',
            'description' => 'freo2の型（コンテンツの種類）の一覧を返します。エントリー（entry）・ページ（page）のほか、プラグインが追加した型もあります。search_entriesとget_entryのtypeには、ここで返すcodeを指定します。draftがtrueの型は下書きを作れ、settingに型の設定（承認・画像・サムネイルを使うか、コードと本文形式の初期値）があります。',
            'inputSchema' => [
                'type'                 => 'object',
                'additionalProperties' => false,
            ],
            'annotations' => [
                'readOnlyHint'  => true,
                'openWorldHint' => false,
            ],
        ],
        [
            'name'        => 'search_entries',
            'title'       => 'エントリーの検索',
            'description' => 'エントリー（記事・ページなど）を新しい順に検索します。管理画面と同じく、非公開のものも含みます。本文は返さないので、必要ならget_entryで取得してください。',
            'inputSchema' => [
                'type'                 => 'object',
                'properties'           => [
                    'type'     => [
                        'type'        => 'string',
                        'description' => '型のコード（list_typesで確かめる）。省略すると、すべての型から探します。',
                    ],
                    'keyword'  => [
                        'type'        => 'string',
                        'description' => 'タイトルか本文に含まれる語。',
                    ],
                    'category' => [
                        'type'        => 'string',
                        'description' => 'カテゴリーのコード。',
                    ],
                    'public'   => [
                        'type'        => 'string',
                        'enum'        => array_keys($GLOBALS['config']['option']['entry']['public']),
                        'description' => '公開範囲。all（公開）・user（登録ユーザー）・attribute（指定の属性）・password（パスワード認証）・none（非公開）。',
                    ],
                    'limit'    => [
                        'type'        => 'integer',
                        'minimum'     => 1,
                        'maximum'     => $GLOBALS['plugin']['mcp']['search_limit'],
                        'description' => '件数（初期値は20）。',
                    ],
                    'offset'   => [
                        'type'        => 'integer',
                        'minimum'     => 0,
                        'description' => '飛ばす件数（初期値は0）。',
                    ],
                ],
                'additionalProperties' => false,
            ],
            'annotations' => [
                'readOnlyHint'  => true,
                'openWorldHint' => false,
            ],
        ],
        [
            'name'        => 'get_entry',
            'title'       => 'エントリーの取得',
            'description' => 'エントリー1件の本文・カテゴリー・フィールドの値などを返します。idか、typeとcodeの組み合わせで指定します。',
            'inputSchema' => [
                'type'                 => 'object',
                'properties'           => [
                    'id'   => [
                        'type'        => 'integer',
                        'description' => 'エントリーのID。',
                    ],
                    'type' => [
                        'type'        => 'string',
                        'description' => '型のコード（codeと一緒に指定する）。',
                    ],
                    'code' => [
                        'type'        => 'string',
                        'description' => 'エントリーのコード（URLに使われる識別子）。',
                    ],
                ],
                'additionalProperties' => false,
            ],
            'annotations' => [
                'readOnlyHint'  => true,
                'openWorldHint' => false,
            ],
        ],
        [
            'name'        => 'list_categories',
            'title'       => 'カテゴリーの一覧',
            'description' => '型ごとのカテゴリーの一覧を返します。search_entriesのcategoryと、下書きのcategoriesには、ここで返すcodeを指定します。',
            'inputSchema' => [
                'type'                 => 'object',
                'properties'           => [
                    'type' => [
                        'type'        => 'string',
                        'description' => '型のコード（list_typesで確かめる）。',
                    ],
                ],
                'required'             => ['type'],
                'additionalProperties' => false,
            ],
            'annotations' => [
                'readOnlyHint'  => true,
                'openWorldHint' => false,
            ],
        ],
        [
            'name'        => 'list_fields',
            'title'       => 'フィールドの一覧',
            'description' => '型ごとのフィールド（追加の入力項目）の一覧を、種類・選択肢・必須かどうかと一緒に返します。下書きのfieldsには、ここで返すidをキーにして値を指定します。mediaがtrueのフィールド（画像・ファイル）は、値にメディアのパスを指定します。',
            'inputSchema' => [
                'type'                 => 'object',
                'properties'           => [
                    'type' => [
                        'type'        => 'string',
                        'description' => '型のコード（list_typesで確かめる）。',
                    ],
                ],
                'required'             => ['type'],
                'additionalProperties' => false,
            ],
            'annotations' => [
                'readOnlyHint'  => true,
                'openWorldHint' => false,
            ],
        ],
        [
            'name'        => 'list_comments',
            'title'       => 'コメントの一覧',
            'description' => 'エントリーへのコメントを新しい順に返します。承認待ちのものも含みます（approvedで絞り込めます）。お問い合わせのやりとりは含みません。',
            'inputSchema' => [
                'type'                 => 'object',
                'properties'           => [
                    'entry_id' => [
                        'type'        => 'integer',
                        'description' => 'エントリーのID。省略すると、すべてのエントリーへのコメントを返します。',
                    ],
                    'approved' => [
                        'type'        => 'boolean',
                        'description' => 'trueなら承認済みだけ、falseなら承認待ちだけ。省略するとどちらも返します。',
                    ],
                    'keyword'  => [
                        'type'        => 'string',
                        'description' => 'コメント内容に含まれる語。',
                    ],
                    'limit'    => [
                        'type'        => 'integer',
                        'minimum'     => 1,
                        'maximum'     => $GLOBALS['plugin']['mcp']['search_limit'],
                        'description' => '件数（初期値は20）。',
                    ],
                    'offset'   => [
                        'type'        => 'integer',
                        'minimum'     => 0,
                        'description' => '飛ばす件数（初期値は0）。',
                    ],
                ],
                'additionalProperties' => false,
            ],
            'annotations' => [
                'readOnlyHint'  => true,
                'openWorldHint' => false,
            ],
        ],
        [
            'name'        => 'list_media',
            'title'       => 'メディアの一覧',
            'description' => 'メディア（管理画面のメディア管理に登録したファイル）の一覧を、ディレクトリごとに返します。本文に画像やファイルを入れるときは、insert_html（管理画面の「挿入」ボタンと同じHTML）をそのまま使ってください。本文の形式（text_type）がtextarea・noneの本文には入れられません。画像にはサムネイルのurlもあります。',
            'inputSchema' => [
                'type'                 => 'object',
                'properties'           => [
                    'directory' => [
                        'type'        => 'string',
                        'description' => 'ディレクトリ（例: images、images/2026）。省略すると最上位（管理者以外は、投稿者用のディレクトリ）です。',
                    ],
                ],
                'additionalProperties' => false,
            ],
            'annotations' => [
                'readOnlyHint'  => true,
                'openWorldHint' => false,
            ],
        ],
        [
            'name'        => 'get_site',
            'title'       => 'サイトの情報',
            'description' => 'サイトのタイトル・概要・URLと、サイト全体の設定の一部（トップページに表示するページ、ページのURLの省略、コメントの承認）を返します。型ごとの設定はlist_typesで確かめます。',
            'inputSchema' => [
                'type'                 => 'object',
                'additionalProperties' => false,
            ],
            'annotations' => [
                'readOnlyHint'  => true,
                'openWorldHint' => false,
            ],
        ],
        [
            'name'        => 'list_attributes',
            'title'       => '属性の一覧',
            'description' => '属性（「有料会員」など、ユーザーとエントリーに付けて公開範囲を絞るもの）の一覧を返します。下書きのattributesには、ここで返すidを指定します。',
            'inputSchema' => [
                'type'                 => 'object',
                'additionalProperties' => false,
            ],
            'annotations' => [
                'readOnlyHint'  => true,
                'openWorldHint' => false,
            ],
        ],
    ];
}

/**
 * ツールの定義（読み取り。管理者だけ）
 *
 * @return array
 */
function service_mcp_tools_admin()
{
    $schema = [
        'type'                 => 'object',
        'additionalProperties' => false,
    ];
    $annotations = [
        'readOnlyHint'  => true,
        'openWorldHint' => false,
    ];

    return [
        [
            'name'        => 'list_menus',
            'title'       => 'メニューの一覧',
            'description' => '公開側のメニュー（管理画面のメニュー管理で登録したもの）を並び順で返します。無効のものも含みます。',
            'inputSchema' => $schema,
            'annotations' => $annotations,
        ],
        [
            'name'        => 'list_widgets',
            'title'       => 'ウィジェットの一覧',
            'description' => 'ウィジェット（画面の決まった位置に差し込むHTML）を、コード・タイトル・内容と一緒に返します。',
            'inputSchema' => $schema,
            'annotations' => $annotations,
        ],
    ];
}

/**
 * 文字列の引数を取得
 *
 * @param array  $arguments
 * @param string $key
 *
 * @return string|null|false 無ければ null、文字列でなければ false
 */
function service_mcp_argument_string($arguments, $key)
{
    if (!isset($arguments[$key]) || $arguments[$key] === '') {
        return null;
    }
    if (!is_string($arguments[$key])) {
        return false;
    }

    return $arguments[$key];
}

/**
 * 整数の引数を取得
 *
 * @param array    $arguments
 * @param string   $key
 * @param int|null $default
 * @param int      $min
 * @param int|null $max
 *
 * @return int|null|false 無ければ $default、範囲外や整数でなければ false
 */
function service_mcp_argument_integer($arguments, $key, $default, $min, $max = null)
{
    if (!isset($arguments[$key])) {
        return $default;
    }
    if (!is_int($arguments[$key]) || $arguments[$key] < $min || ($max !== null && $arguments[$key] > $max)) {
        return false;
    }

    return $arguments[$key];
}

/**
 * ツールの結果
 *
 * @param array|null  $data
 * @param string|null $error
 *
 * @return array
 */
function service_mcp_output($data, $error = null)
{
    return [
        'data'  => $data,
        'error' => $error,
    ];
}

/**
 * ツール: 型の一覧
 *
 * @param array $arguments
 * @param array $auth
 *
 * @return array
 */
function service_mcp_tool_list_types($arguments, $auth)
{
    $types = model('select_types', [
        'order_by' => 'sort, id',
    ]);

    // 下書きを作れる型（データの定義のあるもの）は、型の設定も返す
    $draft_types = service_mcp_draft_types();

    $results = [];
    foreach ($types as $type) {
        $result = [
            'code'  => $type['code'],
            'name'  => $type['name'],
            'memo'  => $type['memo'],
            'draft' => isset($draft_types[$type['code']]),
        ];
        if ($result['draft']) {
            $definition = $draft_types[$type['code']];

            $result['setting'] = [
                'use_approve'   => (bool)app_data_setting($definition, 'use_approve', false),
                'use_pictures'  => (bool)app_data_setting($definition, 'use_pictures', false),
                'use_thumbnail' => (bool)app_data_setting($definition, 'use_thumbnail', false),
                'default_code'  => app_data_setting($definition, 'default_code'),
                'text_type'     => app_data_setting($definition, 'text_type', 'wysiwyg'),
            ];
        }
        $results[] = $result;
    }

    return service_mcp_output([
        'types' => $results,
    ]);
}

/**
 * ツール: エントリーの検索
 *
 * @param array $arguments
 * @param array $auth
 *
 * @return array
 */
function service_mcp_tool_search_entries($arguments, $auth)
{
    $type     = service_mcp_argument_string($arguments, 'type');
    $keyword  = service_mcp_argument_string($arguments, 'keyword');
    $category = service_mcp_argument_string($arguments, 'category');
    $public   = service_mcp_argument_string($arguments, 'public');
    $limit    = service_mcp_argument_integer($arguments, 'limit', 20, 1, $GLOBALS['plugin']['mcp']['search_limit']);
    $offset   = service_mcp_argument_integer($arguments, 'offset', 0, 0);

    if ($type === false || $keyword === false || $category === false || $public === false) {
        return service_mcp_output(null, 'type・keyword・category・publicは文字列で指定してください。');
    }
    if ($limit === false) {
        return service_mcp_output(null, 'limitは1から' . $GLOBALS['plugin']['mcp']['search_limit'] . 'までの整数で指定してください。');
    }
    if ($offset === false) {
        return service_mcp_output(null, 'offsetは0以上の整数で指定してください。');
    }
    if ($public !== null && !isset($GLOBALS['config']['option']['entry']['public'][$public])) {
        return service_mcp_output(null, 'publicの値が不正です。');
    }

    // 絞り込み条件
    $queries = [];

    if ($type !== null) {
        $types = model('select_types', [
            'where' => [
                'code = :code',
                [
                    'code' => $type,
                ],
            ],
        ]);
        if (empty($types)) {
            return service_mcp_output(null, '型「' . $type . '」が見つかりません。list_typesで確かめてください。');
        }
        $queries['type_id'] = $types[0]['id'];
    }
    if ($category !== null) {
        $categories = model('select_categories', [
            'where' => [
                'code = :code' . ($type !== null ? ' AND type_id = :type_id' : ''),
                $type !== null ? [
                    'code'    => $category,
                    'type_id' => $queries['type_id'],
                ] : [
                    'code' => $category,
                ],
            ],
        ]);
        if (empty($categories)) {
            return service_mcp_output(null, 'カテゴリー「' . $category . '」が見つかりません。');
        }
        $queries['category_sets'] = array_column($categories, 'id');
    }
    if ($public !== null) {
        $queries['public'] = $public;
    }
    if ($keyword !== null) {
        $queries['keyword'] = $keyword;
    }

    $filters = model('filter_entries', $queries, [
        'associate' => true,
    ]);
    $where = $filters['where'] === '' ? 'TRUE' : $filters['where'];

    // エントリーを取得
    $entries = model('select_entries', [
        'where'    => $where,
        'order_by' => 'entries.datetime DESC, entries.id',
        'limit'    => [
            ':offset, :limit',
            [
                'offset' => $offset,
                'limit'  => $limit,
            ],
        ],
    ], [
        'associate' => true,
    ]);

    $entry_count = model('select_entries', [
        'select' => 'COUNT(DISTINCT entries.id) AS count',
        'where'  => $where,
    ], [
        'associate' => true,
    ]);

    $results = [];
    foreach ($entries as $entry) {
        $results[] = [
            'id'         => intval($entry['id']),
            'type'       => $entry['type_code'],
            'code'       => $entry['code'],
            'title'      => $entry['title'],
            'public'     => $entry['public'],
            'approved'   => (bool)$entry['approved'],
            'datetime'   => $entry['datetime'],
            'modified'   => $entry['modified'],
            'categories' => array_column($entry['category_sets'], 'category_code'),
        ];
    }

    return service_mcp_output([
        'total'   => intval($entry_count[0]['count']),
        'offset'  => $offset,
        'limit'   => $limit,
        'entries' => $results,
    ]);
}

/**
 * ツール: エントリーの取得
 *
 * @param array $arguments
 * @param array $auth
 *
 * @return array
 */
function service_mcp_tool_get_entry($arguments, $auth)
{
    $id   = service_mcp_argument_integer($arguments, 'id', null, 1);
    $type = service_mcp_argument_string($arguments, 'type');
    $code = service_mcp_argument_string($arguments, 'code');

    if ($id === false || $type === false || $code === false) {
        return service_mcp_output(null, 'idは1以上の整数、type・codeは文字列で指定してください。');
    }

    if ($id !== null) {
        $where = [
            'entries.id = :id',
            [
                'id' => $id,
            ],
        ];
    } elseif ($type !== null && $code !== null) {
        $where = [
            'types.code = :type AND entries.code = :code',
            [
                'type' => $type,
                'code' => $code,
            ],
        ];
    } else {
        return service_mcp_output(null, 'idか、typeとcodeの組み合わせを指定してください。');
    }

    // エントリーを取得
    $entries = model('select_entries', [
        'where' => $where,
    ], [
        'associate' => true,
    ]);
    if (empty($entries)) {
        return service_mcp_output(null, 'エントリーが見つかりません。');
    }
    $entry = $entries[0];

    // カテゴリー
    $categories = [];
    foreach ($entry['category_sets'] as $category_set) {
        $categories[] = [
            'code' => $category_set['category_code'],
            'name' => $category_set['category_name'],
        ];
    }

    // 属性（公開範囲が「指定の属性に公開」のときに使う）
    $attributes = [];
    foreach ($entry['attribute_sets'] as $attribute_set) {
        $attributes[] = [
            'id'   => intval($attribute_set['attribute_id']),
            'name' => $attribute_set['attribute_name'],
        ];
    }

    // フィールド
    $fields = model('select_fields', [
        'where'    => [
            'type_id = :type_id',
            [
                'type_id' => $entry['type_id'],
            ],
        ],
        'order_by' => 'sort, id',
    ]);

    $field_values = [];
    foreach ($fields as $field) {
        $field_values[] = [
            'id'    => intval($field['id']),
            'name'  => $field['name'],
            'kind'  => $field['kind'],
            'value' => isset($entry['field_sets'][$field['id']]) ? $entry['field_sets'][$field['id']] : null,
        ];
    }

    // 画像（改行区切り）
    $pictures = [];
    if ($entry['pictures'] !== null && $entry['pictures'] !== '') {
        $pictures = array_values(array_filter(explode("\n", $entry['pictures']), 'strlen'));
    }

    // パスワード認証のパスワードは返さない
    return service_mcp_output([
        'entry' => [
            'id'           => intval($entry['id']),
            'type'         => [
                'code' => $entry['type_code'],
                'name' => $entry['type_name'],
            ],
            'code'         => $entry['code'],
            'title'        => $entry['title'],
            'text'         => $entry['text'],
            'text_type'    => $entry['text_type'],
            'public'       => $entry['public'],
            'public_begin' => $entry['public_begin'],
            'public_end'   => $entry['public_end'],
            'approved'     => (bool)$entry['approved'],
            'datetime'     => $entry['datetime'],
            'comment'      => $entry['comment'],
            'thumbnail'    => $entry['thumbnail'],
            'pictures'     => $pictures,
            'categories'   => $categories,
            'attributes'   => $attributes,
            'fields'       => $field_values,
            'url'          => $GLOBALS['config']['http_url'] . service_entry_url($entry),
            'created'      => $entry['created'],
            'modified'     => $entry['modified'],
        ],
    ]);
}

/**
 * 型をコードで取得
 *
 * @param string $code
 *
 * @return array|null
 */
function service_mcp_select_type($code)
{
    $types = model('select_types', [
        'where' => [
            'code = :code',
            [
                'code' => $code,
            ],
        ],
    ]);

    return empty($types) ? null : $types[0];
}

/**
 * ツール: カテゴリーの一覧
 *
 * @param array $arguments
 * @param array $auth
 *
 * @return array
 */
function service_mcp_tool_list_categories($arguments, $auth)
{
    $type = service_mcp_argument_string($arguments, 'type');
    if ($type === null || $type === false) {
        return service_mcp_output(null, 'typeを文字列で指定してください。');
    }

    $type_data = service_mcp_select_type($type);
    if ($type_data === null) {
        return service_mcp_output(null, '型「' . $type . '」が見つかりません。list_typesで確かめてください。');
    }

    $categories = model('select_categories', [
        'where'    => [
            'type_id = :type_id',
            [
                'type_id' => $type_data['id'],
            ],
        ],
        'order_by' => 'sort, id',
    ]);

    $results = [];
    foreach ($categories as $category) {
        $results[] = [
            'code' => $category['code'],
            'name' => $category['name'],
            'memo' => $category['memo'],
        ];
    }

    return service_mcp_output([
        'categories' => $results,
    ]);
}

/**
 * ツール: フィールドの一覧
 *
 * @param array $arguments
 * @param array $auth
 *
 * @return array
 */
function service_mcp_tool_list_fields($arguments, $auth)
{
    $type = service_mcp_argument_string($arguments, 'type');
    if ($type === null || $type === false) {
        return service_mcp_output(null, 'typeを文字列で指定してください。');
    }

    $type_data = service_mcp_select_type($type);
    if ($type_data === null) {
        return service_mcp_output(null, '型「' . $type . '」が見つかりません。list_typesで確かめてください。');
    }

    $fields = model('select_fields', [
        'where'    => [
            'type_id = :type_id',
            [
                'type_id' => $type_data['id'],
            ],
        ],
        'order_by' => 'sort, id',
    ]);

    $results = [];
    foreach ($fields as $field) {
        $choices = [];
        if (in_array($field['kind'], ['select', 'radio', 'checkbox'], true) && $field['choices'] !== null) {
            $choices = array_values(array_filter(explode("\n", $field['choices']), 'strlen'));
        }

        $results[] = [
            'id'          => intval($field['id']),
            'name'        => $field['name'],
            'kind'        => $field['kind'],
            'kind_name'   => isset($GLOBALS['config']['option']['field']['kind'][$field['kind']]) ? $GLOBALS['config']['option']['field']['kind'][$field['kind']] : $field['kind'],
            'required'    => $field['validation'] === 'required',
            'choices'     => $choices,
            'initial'     => $field['initial'],
            'explanation' => $field['explanation'],
            'media'       => service_mcp_field_upload($field),
        ];
    }

    return service_mcp_output([
        'fields' => $results,
    ]);
}

/**
 * アップロードで登録するフィールドか
 *
 * @param array $field
 *
 * @return bool
 */
function service_mcp_field_upload($field)
{
    return $field['kind'] === 'image' || $field['kind'] === 'file';
}

/**
 * ツール: コメントの一覧
 *
 * お問い合わせのやりとり（contact_id のコメント）は、個人情報を含むので返さない
 *
 * @param array $arguments
 * @param array $auth
 *
 * @return array
 */
function service_mcp_tool_list_comments($arguments, $auth)
{
    $entry_id = service_mcp_argument_integer($arguments, 'entry_id', null, 1);
    $keyword  = service_mcp_argument_string($arguments, 'keyword');
    $limit    = service_mcp_argument_integer($arguments, 'limit', 20, 1, $GLOBALS['plugin']['mcp']['search_limit']);
    $offset   = service_mcp_argument_integer($arguments, 'offset', 0, 0);

    if ($entry_id === false) {
        return service_mcp_output(null, 'entry_idは1以上の整数で指定してください。');
    }
    if ($keyword === false) {
        return service_mcp_output(null, 'keywordは文字列で指定してください。');
    }
    if (isset($arguments['approved']) && !is_bool($arguments['approved'])) {
        return service_mcp_output(null, 'approvedはtrueかfalseで指定してください。');
    }
    if ($limit === false) {
        return service_mcp_output(null, 'limitは1から' . $GLOBALS['plugin']['mcp']['search_limit'] . 'までの整数で指定してください。');
    }
    if ($offset === false) {
        return service_mcp_output(null, 'offsetは0以上の整数で指定してください。');
    }

    // 絞り込み条件（削除したエントリーへのコメントも返さない）
    $wheres = ['comments.entry_id IS NOT NULL AND entries.deleted IS NULL'];
    $values = [];
    if ($entry_id !== null) {
        $wheres[]           = 'comments.entry_id = :entry_id';
        $values['entry_id'] = $entry_id;
    }
    if (isset($arguments['approved'])) {
        $wheres[]           = 'comments.approved = :approved';
        $values['approved'] = $arguments['approved'] ? 1 : 0;
    }
    if ($keyword !== null) {
        $wheres[]          = 'comments.message LIKE :keyword';
        $values['keyword'] = '%' . $keyword . '%';
    }
    $where = [
        implode(' AND ', $wheres),
        $values,
    ];

    // コメントを取得
    $comments = model('select_comments', [
        'where'    => $where,
        'order_by' => 'comments.created DESC, comments.id DESC',
        'limit'    => [
            ':offset, :limit',
            [
                'offset' => $offset,
                'limit'  => $limit,
            ],
        ],
    ], [
        'associate' => true,
    ]);

    $comment_count = model('select_comments', [
        'select' => 'COUNT(DISTINCT comments.id) AS count',
        'where'  => $where,
    ], [
        'associate' => true,
    ]);

    $results = [];
    foreach ($comments as $comment) {
        $results[] = [
            'id'         => intval($comment['id']),
            'entry'      => [
                'id'    => intval($comment['entry_id']),
                'type'  => $comment['type_code'],
                'code'  => $comment['entry_code'],
                'title' => $comment['entry_title'],
            ],
            'name'       => $comment['name'],
            'url'        => $comment['url'],
            'message'    => $comment['message'],
            'approved'   => (bool)$comment['approved'],
            'registered' => $comment['user_id'] !== null,
            'memo'       => $comment['memo'],
            'created'    => $comment['created'],
        ];
    }

    return service_mcp_output([
        'total'    => intval($comment_count[0]['count']),
        'offset'   => $offset,
        'limit'    => $limit,
        'comments' => $results,
    ]);
}

/**
 * ツール: メディアの一覧
 *
 * ディレクトリの決め方は、管理画面のメディア管理（admin/media）と同じ。ただし、ディレクトリは作らない
 *
 * @param array $arguments
 * @param array $auth
 *
 * @return array
 */
function service_mcp_tool_list_media($arguments, $auth)
{
    $directory = service_mcp_argument_string($arguments, 'directory');
    if ($directory === false) {
        return service_mcp_output(null, 'directoryは文字列で指定してください。');
    }
    if ($directory === null) {
        $directory = '';
    }
    if ($directory !== '' && (!preg_match('/^[\w\-\.]+(\/[\w\-\.]+)*$/', $directory) || preg_match('/(^|\/)\.\.?(\/|$)/', $directory))) {
        return service_mcp_output(null, 'directoryの指定が不正です。');
    }

    // 管理者以外は、最上位の代わりに投稿者用のディレクトリを開く
    if ($auth['authority']['power'] < 3 && $directory === '') {
        $directory = $GLOBALS['config']['media_author_dir'];
    }

    $key = $GLOBALS['config']['file_target']['media'] . ($directory === '' ? '' : $directory . '/');

    // ディレクトリが無ければ、空の一覧を返す
    if ($GLOBALS['config']['storage_type'] === 'file' && !is_dir($key)) {
        $medias     = [];
        $thumbnails = [];
    } else {
        $medias     = service_storage_list($key);
        $thumbnails = service_media_thumbnail_list($directory);
    }

    $base        = $GLOBALS['config']['storage_url'] . '/';
    $path        = $directory === '' ? '' : $directory . '/';
    $directories = [];
    $files       = [];
    foreach ($medias as $media) {
        if ($media['type'] === 'directory') {
            $directories[] = $path . $media['name'];
        } else {
            $modified = $media['modified'];
            if (is_object($modified)) {
                $modified = (string)$modified;
            }

            $url           = $base . $GLOBALS['config']['file_target']['media'] . $path . $media['name'];
            $thumbnail_url = in_array($media['name'], $thumbnails, true) ? $base . $GLOBALS['config']['file_target']['thumbnail'] . 'medias/' . $path . $media['name'] : null;

            $files[] = [
                'name'          => $media['name'],
                'url'           => $url,
                'thumbnail_url' => $thumbnail_url,
                'insert_html'   => service_mcp_media_html($media['name'], $url, $thumbnail_url),
                'size'          => $media['size'] === null ? null : intval($media['size']),
                'modified'      => $modified === null ? null : localdate('Y-m-d H:i:s', $modified),
            ];
        }
    }

    return service_mcp_output([
        'directory'   => $directory,
        'parent'      => $directory === '' || ($auth['authority']['power'] < 3 && $directory === $GLOBALS['config']['media_author_dir']) ? null : (strpos($directory, '/') === false ? '' : dirname($directory)),
        'directories' => $directories,
        'files'       => $files,
    ]);
}

/**
 * 本文に挿入するメディアのHTML
 *
 * 管理画面のメディアの「挿入」ボタン（js/admin.js の .insert-media）と同じタグを作る
 *
 * @param string      $name
 * @param string      $url
 * @param string|null $thumbnail_url
 *
 * @return string
 */
function service_mcp_media_html($name, $url, $thumbnail_url)
{
    $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

    if ($thumbnail_url !== null) {
        // サムネイルを表示し、クリックでオリジナルを表示する
        return '<a href="' . t($url, true) . '" target="_blank"><img src="' . t($thumbnail_url, true) . '" alt="' . t($name, true) . '"></a>';
    } elseif (in_array($extension, ['png', 'jpeg', 'jpg', 'jpe', 'gif'], true)) {
        return '<img src="' . t($url, true) . '" alt="' . t($name, true) . '">';
    } else {
        return '<a href="' . t($url, true) . '" target="_blank">' . t($name, true) . '</a>';
    }
}

/**
 * メディアのHTMLを含むか
 *
 * 複数行入力などの本文では、タグがそのまま文字で表示されるので、挿入を断るために使う
 *
 * @param string|null $text
 *
 * @return bool
 */
function service_mcp_media_html_contains($text)
{
    if ($text === null || $text === '') {
        return false;
    }

    $base = $GLOBALS['config']['storage_url'] . '/' . $GLOBALS['config']['file_target']['media'];

    return (bool)preg_match('/<(img|a)\s[^>]*(src|href)="' . preg_quote(t($base, true), '/') . '/i', $text);
}

/**
 * ツール: サイトの情報
 *
 * 設定は、記事を書くときに関係するもの（config.php の site_settings）だけを返す。メールの設定などは返さない
 *
 * @param array $arguments
 * @param array $auth
 *
 * @return array
 */
function service_mcp_tool_get_site($arguments, $auth)
{
    // 設定の名前（管理画面「設定」の項目名）
    $names = [];
    if (isset($GLOBALS['setting_contents'])) {
        foreach ($GLOBALS['setting_contents'] as $group) {
            foreach ($group as $key => $content) {
                if (is_array($content) && isset($content['name'])) {
                    $names[$key] = $content['name'];
                }
            }
        }
    }

    $settings = [];
    foreach ($GLOBALS['plugin']['mcp']['site_settings'] as $key) {
        $settings[] = [
            'id'    => $key,
            'name'  => isset($names[$key]) ? $names[$key] : $key,
            'value' => isset($GLOBALS['setting'][$key]) ? $GLOBALS['setting'][$key] : null,
        ];
    }

    return service_mcp_output([
        'site' => [
            'title'       => isset($GLOBALS['setting']['title']) ? $GLOBALS['setting']['title'] : null,
            'description' => isset($GLOBALS['setting']['description']) ? $GLOBALS['setting']['description'] : null,
            'url'         => $GLOBALS['config']['http_url'] . '/',
            'settings'    => $settings,
        ],
    ]);
}

/**
 * ツール: メニューの一覧（管理者だけ）
 *
 * @param array $arguments
 * @param array $auth
 *
 * @return array
 */
function service_mcp_tool_list_menus($arguments, $auth)
{
    $menus = model('select_menus', [
        'order_by' => 'sort, id',
    ]);

    $results = [];
    foreach ($menus as $menu) {
        $results[] = [
            'title'   => $menu['title'],
            'url'     => $menu['url'],
            'enabled' => (bool)$menu['enabled'],
            'memo'    => $menu['memo'],
        ];
    }

    return service_mcp_output([
        'menus' => $results,
    ]);
}

/**
 * ツール: ウィジェットの一覧（管理者だけ）
 *
 * @param array $arguments
 * @param array $auth
 *
 * @return array
 */
function service_mcp_tool_list_widgets($arguments, $auth)
{
    $widgets = model('select_widgets', [
        'order_by' => 'sort, id',
    ]);

    $results = [];
    foreach ($widgets as $widget) {
        $results[] = [
            'code'  => $widget['code'],
            'title' => $widget['title'],
            'text'  => $widget['text'],
            'memo'  => $widget['memo'],
        ];
    }

    return service_mcp_output([
        'widgets' => $results,
    ]);
}

/**
 * ツール: 属性の一覧
 *
 * 属性の管理画面は管理者だけだが、エントリーの入力画面では投稿者も属性を選べるので、全員に返す
 *
 * @param array $arguments
 * @param array $auth
 *
 * @return array
 */
function service_mcp_tool_list_attributes($arguments, $auth)
{
    $attributes = model('select_attributes', [
        'order_by' => 'sort, id',
    ]);

    $results = [];
    foreach ($attributes as $attribute) {
        $results[] = [
            'id'         => intval($attribute['id']),
            'name'       => $attribute['name'],
            'filterable' => (bool)$attribute['filterable'],
            'memo'       => $attribute['memo'],
        ];
    }

    return service_mcp_output([
        'attributes' => $results,
    ]);
}

/**
 * 下書きの文字列の引数を取得
 *
 * 空文字も値として扱う（本文を空にするときのため）
 *
 * @param array  $arguments
 * @param string $key
 *
 * @return string|null|false 無ければ null、文字列でなければ false
 */
function service_mcp_argument_text($arguments, $key)
{
    if (!array_key_exists($key, $arguments) || $arguments[$key] === null) {
        return null;
    }
    if (!is_string($arguments[$key])) {
        return false;
    }

    return $arguments[$key];
}

/**
 * 下書きの入力データを作成して検証
 *
 * @param array      $type
 * @param array      $arguments
 * @param array|null $entry 更新するときは今のエントリー（associate 付きで取得したもの）
 *
 * @return array 0 に入力データ、1 にエラーのメッセージ（無ければ null）
 */
function service_mcp_draft_entry($type, $arguments, $entry)
{
    // 文字列の引数
    $values = [];
    foreach (['title', 'text', 'text_type', 'code', 'datetime', 'public_begin', 'public_end', 'comment'] as $key) {
        $values[$key] = service_mcp_argument_text($arguments, $key);
        if ($values[$key] === false) {
            return [null, 'title・text・text_type・code・datetime・public_begin・public_end・commentは文字列で指定してください。'];
        }
    }

    // 日時（公開期間は、空文字で解除できる）
    foreach (['datetime', 'public_begin', 'public_end'] as $key) {
        if ($values[$key] === null || ($key !== 'datetime' && $values[$key] === '')) {
            continue;
        }
        if (!preg_match('/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2})(:\d{2})?$/', $values[$key], $matches)) {
            return [null, $key . 'はYYYY-MM-DD HH:MMの形で指定してください。'];
        }
        $values[$key] = $matches[1];
    }

    if ($entry === null) {
        // 新規（管理画面の登録画面の初期値と同じにする）
        $data = model('default_entries');

        $data['type_id']      = $type['id'];
        $data['public_begin'] = '';
        $data['public_end']   = '';
        $data['password']     = '';
        $data['text']         = '';

        // 本文形式とコードの初期値（型の設定。データの定義に設定が無ければ、本体の初期値と、初期値なし）
        $definition   = app_data_type($type['code']);
        $default_code = app_data_setting($definition, 'default_code');

        $data['text_type'] = app_data_setting($definition, 'text_type', $data['text_type']);
        $data['code']      = $default_code ? localdate($default_code) : '';

        if ($values['code'] === null && $data['code'] === '') {
            return [null, 'codeを指定してください。'];
        }
    } else {
        // 更新（今の値をもとにする）
        $entry = model('view_entries', $entry);

        $data = [
            'id'             => $entry['id'],
            'type_id'        => $entry['type_id'],
            'public_begin'   => $entry['public_begin'] === null ? '' : $entry['public_begin'],
            'public_end'     => $entry['public_end'] === null ? '' : $entry['public_end'],
            'password'       => $entry['password'] === null ? '' : $entry['password'],
            'datetime'       => $entry['datetime'],
            'code'           => $entry['code'],
            'title'          => $entry['title'],
            'text'           => $entry['text'] === null ? '' : $entry['text'],
            'text_type'      => $entry['text_type'],
            'comment'        => $entry['comment'],
            'category_sets'  => array_column($entry['category_sets'], 'category_id'),
            'attribute_sets' => array_column($entry['attribute_sets'], 'attribute_id'),
        ];
    }

    // 下書きは非公開に固定する
    $data['public'] = 'none';

    foreach ($values as $key => $value) {
        if ($value !== null) {
            $data[$key] = $value;
        }
    }

    // 複数行入力（タグがそのまま文字で表示される）と、なし（本文を表示しない）の本文には、メディアのHTMLを入れない
    if (($values['text'] !== null || $values['text_type'] !== null) && in_array($data['text_type'], ['textarea', 'none'], true) && service_mcp_media_html_contains($data['text'])) {
        return [null, '本文の形式が「' . $GLOBALS['config']['option']['entry']['text_type'][$data['text_type']] . '」なので、メディアのHTML（insert_html）は表示されません。本文に画像を入れるなら、text_typeをhtml・wysiwyg・markdownのどれかにしてください。'];
    }

    // カテゴリー
    if (array_key_exists('categories', $arguments) && $arguments['categories'] !== null) {
        if (!is_array($arguments['categories'])) {
            return [null, 'categoriesはカテゴリーのコードの配列で指定してください。'];
        }

        $categories = model('select_categories', [
            'where' => [
                'type_id = :type_id',
                [
                    'type_id' => $type['id'],
                ],
            ],
        ]);
        $category_ids = array_column($categories, 'id', 'code');

        $data['category_sets'] = [];
        foreach ($arguments['categories'] as $category) {
            if (!is_string($category)) {
                return [null, 'categoriesはカテゴリーのコードの配列で指定してください。'];
            }
            if (!isset($category_ids[$category])) {
                return [null, 'カテゴリー「' . $category . '」が見つかりません。list_categoriesで確かめてください。'];
            }
            $data['category_sets'][] = $category_ids[$category];
        }
        $data['category_sets'] = array_values(array_unique($data['category_sets']));
    }

    // 属性
    if (array_key_exists('attributes', $arguments) && $arguments['attributes'] !== null) {
        if (!is_array($arguments['attributes'])) {
            return [null, 'attributesは属性のIDの配列で指定してください。'];
        }

        $attribute_ids = array_map('intval', array_column(model('select_attributes', []), 'id'));

        $data['attribute_sets'] = [];
        foreach ($arguments['attributes'] as $attribute) {
            if (!is_int($attribute)) {
                return [null, 'attributesは属性のIDの配列で指定してください。'];
            }
            if (!in_array($attribute, $attribute_ids, true)) {
                return [null, '属性「' . $attribute . '」が見つかりません。list_attributesで確かめてください。'];
            }
            $data['attribute_sets'][] = $attribute;
        }
        $data['attribute_sets'] = array_values(array_unique($data['attribute_sets']));
    }

    // フィールド（アップロードで登録するものは、保存しても消えないので含めない。メディアからの複製は後で組み立てる）
    $fields = model('select_fields', [
        'where'    => [
            'type_id = :type_id',
            [
                'type_id' => $type['id'],
            ],
        ],
        'order_by' => 'sort, id',
    ]);
    $fields = array_column($fields, null, 'id');

    $data['field_sets'] = [];
    foreach ($fields as $field) {
        if (service_mcp_field_upload($field)) {
            continue;
        }
        if ($entry === null) {
            $data['field_sets'][$field['id']] = $field['initial'] === null ? '' : $field['initial'];
        } else {
            $data['field_sets'][$field['id']] = isset($entry['field_sets'][$field['id']]) ? $entry['field_sets'][$field['id']] : '';
        }
    }

    $field_uploads = [];
    if (array_key_exists('fields', $arguments) && $arguments['fields'] !== null) {
        if (!is_array($arguments['fields'])) {
            return [null, 'fieldsはフィールドのIDをキーにしたオブジェクトで指定してください。'];
        }

        foreach ($arguments['fields'] as $field_id => $value) {
            if (!isset($fields[$field_id])) {
                return [null, 'フィールド「' . $field_id . '」はこの型にありません。list_fieldsで確かめてください。'];
            }
            if (service_mcp_field_upload($fields[$field_id])) {
                // 画像・ファイルは、メディアのパス（空文字なら削除）
                if ($value !== null && !is_string($value)) {
                    return [null, 'フィールド「' . $fields[$field_id]['name'] . '」の値は、メディアのパスで指定してください。'];
                }
                $field_uploads[$field_id] = $value === null ? '' : $value;

                continue;
            }

            if ($value === null) {
                $value = '';
            } elseif (is_array($value)) {
                foreach ($value as $item) {
                    if (!is_string($item)) {
                        return [null, 'フィールド「' . $fields[$field_id]['name'] . '」の値は、文字列か文字列の配列で指定してください。'];
                    }
                }
                $value = implode("\n", $value);
            } elseif (!is_string($value)) {
                return [null, 'フィールド「' . $fields[$field_id]['name'] . '」の値は、文字列か文字列の配列で指定してください。'];
            }

            $data['field_sets'][$field_id] = $value;
        }
    }

    // 整形と検証（管理画面と同じ）
    $data = model('normalize_entries', $data);

    $warnings = model('validate_entries', $data);
    if (!empty($warnings)) {
        return [null, implode("\n", $warnings)];
    }

    // 画像・ファイル（メディアから複製する。set_file_entries() に渡す形にする）
    list($files, $picture_files, $error) = service_mcp_draft_files($type, $arguments, $entry, $fields, $field_uploads);
    if ($error !== null) {
        return [null, $error];
    }
    $data['files']         = $files;
    $data['picture_files'] = $picture_files;

    return [$data, null];
}

/**
 * 下書きの画像・ファイルを組み立て
 *
 * メディアのファイルを複製して使う（set_file_entries_put() の temp に、メディアの場所を渡す）。新しいファイルは受け取らない
 *
 * @param array      $type
 * @param array      $arguments
 * @param array|null $entry
 * @param array      $fields        型のフィールド（IDがキー）
 * @param array      $field_uploads 画像・ファイルのフィールドに指定された値（IDがキー）
 *
 * @return array 0 にファイル、1 に画像の名前の並び、2 にエラーのメッセージ（無ければ null）
 */
function service_mcp_draft_files($type, $arguments, $entry, $fields, $field_uploads)
{
    $files = [];

    // 今の画像（ファイルを渡すと、ここに無い画像は削除されるので、変えないときも今の並びを渡す）
    $current_pictures = [];
    if ($entry !== null && $entry['pictures'] !== null && $entry['pictures'] !== '') {
        $current_pictures = array_values(array_filter(explode("\n", $entry['pictures']), 'strlen'));
    }
    $picture_files = $current_pictures;

    // 型の設定（データの定義に設定が無ければ、画像もサムネイルも使わない）
    $definition = app_data_type($type['code']);

    // サムネイル
    $thumbnail = service_mcp_argument_text($arguments, 'thumbnail');
    if ($thumbnail === false) {
        return [null, null, 'thumbnailはメディアのパスで指定してください。'];
    }
    if ($thumbnail !== null) {
        if (!app_data_setting($definition, 'use_thumbnail', false)) {
            return [null, null, 'この型はサムネイルを使わない設定です。'];
        }
        if ($thumbnail === '') {
            if ($entry !== null && $entry['thumbnail'] !== null) {
                $files['thumbnail'] = [
                    'delete' => true,
                ];
            }
        } else {
            list($file, $error) = service_mcp_media_file($thumbnail, 'image');
            if ($error !== null) {
                return [null, null, $error];
            }
            $files['thumbnail'] = $file;
        }
    }

    // 画像
    if (array_key_exists('pictures', $arguments) && $arguments['pictures'] !== null) {
        if (!app_data_setting($definition, 'use_pictures', false)) {
            return [null, null, 'この型は画像を使わない設定です。'];
        }
        if (!is_array($arguments['pictures'])) {
            return [null, null, 'picturesはメディアのパスか今の画像の名前の配列で指定してください。'];
        }

        $files['pictures'] = [];
        $picture_files     = [];
        foreach ($arguments['pictures'] as $picture) {
            if (!is_string($picture)) {
                return [null, null, 'picturesはメディアのパスか今の画像の名前の配列で指定してください。'];
            }

            if (in_array($picture, $current_pictures, true)) {
                // 今の画像を残す
                $filename = $picture;
            } else {
                list($file, $error) = service_mcp_media_file($picture, 'image');
                if ($error !== null) {
                    return [null, null, $error];
                }
                $files['pictures'][] = $file;

                // 保存するときの名前（set_file_entries() と同じ作り方）
                preg_match('/(.*)\.(.*)$/', $file['name'], $matches);
                $filename = rawurlencode($matches[1]) . '.' . $matches[2];
            }

            if (in_array($filename, $picture_files, true)) {
                return [null, null, '画像の名前「' . $filename . '」が重複しています。'];
            }
            $picture_files[] = $filename;
        }
    }

    // サムネイルと画像は同じディレクトリに保存されるので、同じ名前だと上書きし合う（サムネイルを削除すると、同じ名前の画像も消える）
    if (isset($files['thumbnail']) || isset($files['pictures'])) {
        if (isset($files['thumbnail']['name'])) {
            preg_match('/(.*)\.(.*)$/', $files['thumbnail']['name'], $matches);
            $thumbnail_name = rawurlencode($matches[1]) . '.' . $matches[2];
        } else {
            $thumbnail_name = $entry === null ? null : $entry['thumbnail'];
        }
        if ($thumbnail_name !== null && in_array($thumbnail_name, $picture_files, true)) {
            return [null, null, 'サムネイルと画像に、同じ名前のファイル「' . $thumbnail_name . '」は使えません。'];
        }
    }

    // 画像・ファイルのフィールド
    foreach ($field_uploads as $field_id => $value) {
        $key = 'field__' . $field_id;

        if ($value === '') {
            if ($entry !== null && isset($entry['field_sets'][$field_id])) {
                $files[$key] = [
                    'delete' => true,
                ];
            }
        } else {
            list($file, $error) = service_mcp_media_file($value, $fields[$field_id]['kind']);
            if ($error !== null) {
                return [null, null, $error];
            }
            $files[$key] = $file;
        }
    }

    return [$files, $picture_files, null];
}

/**
 * メディアのファイルを、エントリーのファイルとして複製する形で取得
 *
 * @param string $path   メディアのパス（例: images/photo.jpg）
 * @param string $format 形式（app/config.php の file_permission のキー。image か file）
 *
 * @return array 0 にファイル（['name' => ファイル名, 'temp' => メディアの場所]）、1 にエラーのメッセージ（無ければ null）
 */
function service_mcp_media_file($path, $format)
{
    if (!preg_match('/^[\w\-\.]+(\/[\w\-\.]+)*$/', $path) || preg_match('/(^|\/)\.\.?(\/|$)/', $path)) {
        return [null, 'メディアのパス「' . $path . '」の指定が不正です。'];
    }

    // 形式（管理画面のアップロードと同じ）
    $names   = [];
    $allowed = false;
    foreach ($GLOBALS['config']['file_permission'][$format] as $permission) {
        $names[] = $permission['name'];
        if (preg_match($permission['regexp'], $path)) {
            $allowed = true;
        }
    }
    if (!$allowed) {
        return [null, 'メディア「' . $path . '」は使えない形式です。使える形式は' . implode('、', $names) . 'です。'];
    }

    $key = $GLOBALS['config']['file_target']['media'] . $path;
    if (!service_storage_exist($key)) {
        return [null, 'メディア「' . $path . '」が見つかりません。list_mediaで確かめてください。'];
    }

    return [
        [
            'name' => basename($path),
            'temp' => $key,
        ],
        null,
    ];
}

/**
 * 下書きの結果
 *
 * @param int $id
 *
 * @return array
 */
function service_mcp_draft_output($id)
{
    $entries = model('select_entries', [
        'where' => [
            'entries.id = :id',
            [
                'id' => $id,
            ],
        ],
    ], [
        'associate' => true,
    ]);
    $entry = $entries[0];

    // 編集画面（データの定義に form が無ければ返さない）
    $definition = app_data_type($entry['type_code']);
    $edit_url   = isset($definition['form']) ? $GLOBALS['config']['http_url'] . $definition['form'] . '?id=' . intval($entry['id']) : null;

    return service_mcp_output([
        'entry' => [
            'id'       => intval($entry['id']),
            'type'     => $entry['type_code'],
            'code'     => $entry['code'],
            'title'    => $entry['title'],
            'public'   => $entry['public'],
            'approved' => (bool)$entry['approved'],
            'modified' => $entry['modified'],
            'edit_url' => $edit_url,
        ],
    ]);
}

/**
 * ツール: 下書きの作成
 *
 * @param array $arguments
 * @param array $auth
 *
 * @return array
 */
function service_mcp_tool_create_draft($arguments, $auth)
{
    $type        = service_mcp_argument_string($arguments, 'type');
    $draft_types = service_mcp_draft_types();
    if (!is_string($type) || !isset($draft_types[$type])) {
        return service_mcp_output(null, 'typeには' . implode('・', array_keys($draft_types)) . 'のどれかを指定してください。');
    }

    $type_data = service_mcp_select_type($type);
    if ($type_data === null) {
        return service_mcp_output(null, '型「' . $type . '」が見つかりません。');
    }

    list($data, $error) = service_mcp_draft_entry($type_data, $arguments, null);
    if ($error !== null) {
        return service_mcp_output(null, $error);
    }

    // トランザクションを開始
    db_transaction();

    // エントリーを登録（承認は、管理画面から登録したときと同じく設定に従う）
    service_entry_insert([
        'values' => [
            'type_id'      => $data['type_id'],
            'approved'     => app_data_setting($draft_types[$type], 'use_approve', false) ? 0 : 1,
            'public'       => 'none',
            'public_begin' => $data['public_begin'],
            'public_end'   => $data['public_end'],
            'password'     => $data['password'],
            'datetime'     => $data['datetime'],
            'code'         => $data['code'],
            'title'        => $data['title'],
            'text'         => $data['text'],
            'text_type'    => $data['text_type'],
            'comment'      => $data['comment'],
        ],
    ], [
        'field_sets'     => $data['field_sets'],
        'category_sets'  => $data['category_sets'],
        'attribute_sets' => $data['attribute_sets'],
        'files'          => $data['files'],
        'picture_files'  => $data['picture_files'],
    ]);

    // 登録したエントリーを取得（関連データの登録で db_last_insert_id() が変わるため、コードで探す）
    $entries = model('select_entries', [
        'select' => 'id',
        'where'  => [
            'type_id = :type_id AND code = :code',
            [
                'type_id' => $data['type_id'],
                'code'    => $data['code'],
            ],
        ],
    ]);

    // トランザクションを終了
    db_commit();

    return service_mcp_draft_output($entries[0]['id']);
}

/**
 * ツール: 下書きの更新
 *
 * @param array $arguments
 * @param array $auth
 *
 * @return array
 */
function service_mcp_tool_update_draft($arguments, $auth)
{
    $id       = service_mcp_argument_integer($arguments, 'id', null, 1);
    $modified = service_mcp_argument_string($arguments, 'modified');

    if ($id === null || $id === false) {
        return service_mcp_output(null, 'idを1以上の整数で指定してください。');
    }
    if ($modified === null || $modified === false) {
        return service_mcp_output(null, 'modifiedを指定してください（get_entryで取得した値）。');
    }

    $entries = model('select_entries', [
        'where' => [
            'entries.id = :id',
            [
                'id' => $id,
            ],
        ],
    ], [
        'associate' => true,
    ]);
    if (empty($entries)) {
        return service_mcp_output(null, 'エントリーが見つかりません。');
    }
    $entry = $entries[0];

    $draft_types = service_mcp_draft_types();
    if (!isset($draft_types[$entry['type_code']])) {
        return service_mcp_output(null, 'この型のエントリーは更新できません。');
    }
    if ($entry['public'] !== 'none') {
        return service_mcp_output(null, '非公開のエントリーだけ更新できます。');
    }
    if ($entry['modified'] !== $modified) {
        return service_mcp_output(null, '取得した後にエントリーが更新されています。get_entryで取得し直してください。');
    }

    list($data, $error) = service_mcp_draft_entry(service_mcp_select_type($entry['type_code']), $arguments, $entry);
    if ($error !== null) {
        return service_mcp_output(null, $error);
    }

    // トランザクションを開始
    db_transaction();

    // エントリーを編集
    service_entry_update([
        'set'   => [
            'public_begin' => $data['public_begin'],
            'public_end'   => $data['public_end'],
            'datetime'     => $data['datetime'],
            'code'         => $data['code'],
            'title'        => $data['title'],
            'text'         => $data['text'],
            'text_type'    => $data['text_type'],
            'comment'      => $data['comment'],
        ],
        'where' => [
            'id = :id AND public = :public',
            [
                'id'     => $id,
                'public' => 'none',
            ],
        ],
    ], [
        'id'             => $id,
        'update'         => $entry['modified'],
        'field_sets'     => $data['field_sets'],
        'category_sets'  => $data['category_sets'],
        'attribute_sets' => $data['attribute_sets'],
        'files'          => $data['files'],
        'picture_files'  => $data['picture_files'],
    ]);

    // トランザクションを終了
    db_commit();

    return service_mcp_draft_output($id);
}
