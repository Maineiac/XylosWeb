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

/**
 * @var \Phroute\Phroute\RouteCollector $router
 */
$router->filter('auth', function () {
    if (!isset($_SESSION['SteamLogin']['steamid']) && !isset($_POST['serverkey']) && !isset($_GET['serverkey'])) {
        if (Util::isAjax()) {
            return false;
        }
        session_destroy();

        return view();
    }
});


$router->filter('serverkey', function () {
    $key = isset($_POST['key']) ? $_POST['key'] : (isset($_GET['serverkey']) ? $_GET['serverkey'] : Util::Hash());
    if ($key !== Util::getSetting('server_key')) {
        die('server key does match the one set in config/settings.php');
    }
});

$router->get(['/logout', 'logout'], ['Controllers\Main', 'logout']);
$router->get(['/login', 'login'], ['Controllers\Main', 'login']);

$router->any('/savedata.php', ['Controllers\Screenshots', 'savedata']);


$router->group(['before' => 'serverkey'], function ($router) {
    /**
     * @var \Phroute\Phroute\RouteCollector $router
     */
    $router->any('/auth.php', ['Controllers\Screenshots', 'auth']);
    $router->any('/requestcap.php', ['Controllers\Screenshots', 'requestcap']);
    $router->any('/sendwebcaps.php', ['Controllers\Screenshots', 'sendwebcaps']);

    $router->post('/api/servers/update', ['Controllers\API', 'updateServer']);
});

$router->group(['before' => 'auth'], function ($router) {
    /**
     * @var \Phroute\Phroute\RouteCollector $router
     */
    $router->get(['/', 'index'], ['Controllers\Main', 'index']);

    $router->get(['/browse/screenshots/{pg:i}?', 'screenshots'], ['Controllers\Screenshots', 'index']);
    $router->post('/api/screenshots/delete', ['Controllers\Screenshots', 'trash']);

    $router->get(['/servers', 'servers'], ['Controllers\Servers', 'index']);
    $router->get(['/users/{pg:i}?', 'users'], ['Controllers\Users', 'index']);
    $router->get(['/user/{steamid}', 'user'], ['Controllers\Users', 'user']);

    $router->post('/servers', ['Controllers\Users', 'capture']);
    $router->post('/api/users/capture', ['Controllers\Users', 'capture']);
});

$router->post('/api/rememberme', ['Controllers\API', 'rememberMe']);