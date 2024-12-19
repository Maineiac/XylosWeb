<?php
/*
 * Leyscreencap Web (https://demo.maddela.org/leyscreencap/).
 *
 * @link      https://www.maddela.org
 * @link      https://github.com/kanalumaddela/leyscreencap-web
 *
 * @author    kanalumaddela <git@maddela.org>
 * @copyright Copyright (c) 2018-2021 kanalumaddela
 * @license   MIT
 */

$start = microtime(true);

const APP_ROOT = __DIR__;

require_once APP_ROOT.'/config/constants.php';

if (!file_exists(APP_ROOT.'/config/settings.php')) {
    copy(APP_ROOT.'/config/settings.template.php', APP_ROOT.'/config/settings.php');
}

$config = require_once APP_ROOT.'/config/settings.php';

ini_set('display_errors', (DEBUG ? 1 : 0));
ini_set('display_startup_errors', (DEBUG ? 1 : 0));
error_reporting((DEBUG ? E_ALL : E_ERROR | E_PARSE));

date_default_timezone_set(APP_TIMEZONE);

// fuck u ley
$fake_files = [
    'auth.php',
    'requestcap.php',
    'savedata.php',
    'sendwebcaps.php',
];
$php_self = $_SERVER['SCRIPT_NAME'];
if (in_array(basename($_SERVER['SCRIPT_NAME']), $fake_files)) {
    $php_self = substr(str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']), 0, -1).'/'.basename(__FILE__);
}
$php_self = rtrim($php_self, '/');

define('PHP_SELF', $php_self);

// test write perms, doing it this early cause retards
if (!file_exists(APP_ROOT.'/config/FILE_WRITE_CHECK_DO_NOT_REMOVE')) {
    set_error_handler(function () {
    });
    $check = mkdir(APP_ROOT.'/test', 0775, true);
    restore_error_handler();

    if (!$check && !file_exists(APP_ROOT.'/test')) {
        echo '<div style="color:red;text-align:center;"><h1 style="text-transform:uppercase">insufficient permissions to write</h1><h3>before submitting a ticket, try:</h3>';
        echo '<code style="color:#0ed60e;background:black;padding: 5px 3px;">chown -R www-data:www-data /var/www/html</code><p>or whatever the path is to your files</p>';
        echo '</div>';
        phpinfo();
        die();
    }
    rmdir(APP_ROOT.'/test');
    touch(APP_ROOT.'/config/FILE_WRITE_CHECK_DO_NOT_REMOVE');
}

if (empty($config['api_key'])) {
    echo '<div style="color:red;text-align:center;"><h1 style="text-transform:uppercase">Steam API key not set.</h1>';
    echo '<h3>Open <code style="color:#0ed60e;background:black;padding: 4px 3px;">config/settings.php</code> to set your api key</h3>';
    echo '</div>';
    die();
}

require_once APP_ROOT.'/inc/init.php';
