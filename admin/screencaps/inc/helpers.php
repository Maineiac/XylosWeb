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

function route($route, array $parameters = [])
{
    /**
     * @var \Phroute\Phroute\RouteCollector $router
     */
    global $router;

    set_error_handler(function () {
    });
    $route = $router->route($route, $parameters);
    restore_error_handler();

    if (empty($route)) {
        $route = '/';
    }

    $route = $route !== '/' ? (ENABLE_FRIENDLY_URLS ? APP_URL_PATH : PHP_SELF.'?').'/'.$route : APP_URL_PATH;

    if (empty($route)) {
        $route = '/';
    }

    return $route;
}