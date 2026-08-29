<?php

if ($_REQUEST['_mode'] === 'entry' && $_REQUEST['_work'] === 'detail') {
    if ($_view['entry']['text_type'] === $GLOBALS['plugin']['markdown']['code']) {
        $_view['entry']['text'] = plugin_markdown_convert($_view['entry']['text']);
    }
} elseif ($_REQUEST['_mode'] === 'page') {
    if ($_view['entry']['text_type'] === $GLOBALS['plugin']['markdown']['code']) {
        $_view['entry']['text'] = plugin_markdown_convert($_view['entry']['text']);
    }
} elseif ($_REQUEST['_mode'] === 'home' || $_REQUEST['_mode'] === 'entry') {
    foreach ($_view['entries'] as $i => $entry) {
        if ($entry['public'] !== 'password' || !empty($_SESSION['entry_passwords'][$entry['id']])) {
            if ($entry['text_type'] === $GLOBALS['plugin']['markdown']['code'] && $entry['text'] !== null) {
                $_view['entries'][$i]['text'] = plugin_markdown_convert($entry['text']);
            }
        }
    }
} elseif ($_REQUEST['_mode'] === 'admin' && ($_REQUEST['_work'] === 'entry_form' || $_REQUEST['_work'] === 'page_form') && isset($_POST['view']) && $_POST['view'] === 'preview') {
    if ($_view['entry']['text_type'] === $GLOBALS['plugin']['markdown']['code']) {
        $_view['entry']['text'] = plugin_markdown_convert($_view['entry']['text']);
    }
}
