<?php

// Enable/disable debug mode (default is `null`)
if (defined('DEBUG')) {
    ini_set('error_log', ENGINE . DS . 'log' . DS . 'error.log');
    if (DEBUG) {
        ini_set('max_execution_time', 300); // 5 minute(s)
        if (DEBUG === true) {
            error_reporting(E_ALL | E_STRICT);
            ini_set('display_errors', true);
            ini_set('display_startup_errors', true);
            ini_set('html_errors', 1);
        }
    } else if (DEBUG === false) {
        error_reporting(0);
        ini_set('display_errors', false);
        ini_set('display_startup_errors', false);
    }
}

$vars = [&$_GET, &$_POST, &$_REQUEST];
array_walk_recursive($vars, function(&$v) {
    // Normalize line-break
    $v = strtr($v, ["\r\n" => "\n", "\r" => "\n"]);
});

// Normalize `$_FILES` value to `$_POST`
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_FILES as $k => $v) {
        foreach ($v as $kk => $vv) {
            if (is_array($vv)) {
                foreach ($vv as $kkk => $vvv) {
                    $_POST[$k][$kkk][$kk] = $vvv;
                }
            } else {
                $_POST[$k][$kk] = $vv;
            }
        }
    }
}

// Load class(es)…
d(($f = ENGINE . DS) . 'kernel', function($v, $n) use($f) {
    $f .= 'plug' . DS . $n . '.php';
    if (is_file($f)) {
        extract($GLOBALS, EXTR_SKIP);
        require $f;
    }
});

// Boot…
require __DIR__ . DS . 'r' . DS . 'anemon.php';
require __DIR__ . DS . 'r' . DS . 'cache.php';
require __DIR__ . DS . 'r' . DS . 'config.php';
require __DIR__ . DS . 'r' . DS . 'cookie.php';
require __DIR__ . DS . 'r' . DS . 'date.php';
require __DIR__ . DS . 'r' . DS . 'file.php';
require __DIR__ . DS . 'r' . DS . 'guard.php';
require __DIR__ . DS . 'r' . DS . 'header.php';
require __DIR__ . DS . 'r' . DS . 'hook.php';
require __DIR__ . DS . 'r' . DS . 'language.php';
require __DIR__ . DS . 'r' . DS . 'mecha.php';
require __DIR__ . DS . 'r' . DS . 'route.php';
require __DIR__ . DS . 'r' . DS . 'session.php';
require __DIR__ . DS . 'r' . DS . 'u-r-l.php';