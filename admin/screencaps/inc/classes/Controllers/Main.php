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

namespace Controllers;

use Util;
use function array_filter;
use function array_slice;
use function count;
use function file_exists;
use function glob;
use function number_format;
use function redirect;
use function session_destroy;
use function setcookie;
use function unlink;

class Main
{

    public function index()
    {

        $latest_screenshots = Util::getScreenshots();
        $latest_screenshots_length = count($latest_screenshots);

        if (count($latest_screenshots) > 0) {
            $latest_screenshots = array_slice($latest_screenshots, 0, $latest_screenshots_length <= 5 ? $latest_screenshots_length : 5);
        }

        $data = [
            'user'        => ['screenshots' => count(Util::getScreenshots('all', $_SESSION['SteamLogin']['steamid']))],
            'users'       => ['total' => count(array_filter(glob(APP_ROOT.DIRECTORY_SEPARATOR.'screenshots'.DIRECTORY_SEPARATOR.'*', GLOB_ONLYDIR), 'Util::screenshotFilter'))],
            'screenshots' => [
                'total'  => count(glob(APP_ROOT.DIRECTORY_SEPARATOR.'screenshots'.DIRECTORY_SEPARATOR.'*'.DIRECTORY_SEPARATOR.'*.{bmp,jpg,png,tga}', GLOB_BRACE)),
                'latest' => $latest_screenshots,
            ],
            'servers'     => ['total' => count(glob(APP_ROOT.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'servers'.DIRECTORY_SEPARATOR.'*.json'))],
        ];

        $data['users']['totalFormatted'] = number_format($data['users']['total']);
        $data['screenshots']['totalFormatted'] = number_format($data['screenshots']['total']);
        $data['servers']['totalFormatted'] = number_format($data['servers']['total']);

        return view('index', $data);
    }

    public function login()
    {
        global $steamlogin;
        $steamlogin->login();
    }

    public function logout()
    {
        if (isset($_COOKIE['leyscreencap_cookie_login'])) {
            $cookie_file = APP_ROOT.'/data/logins/'.$_COOKIE['leyscreencap_cookie_login'];
            if (file_exists($cookie_file)) {
                unlink($cookie_file);
            }
        }

        setcookie('leyscreencap_cookie_login', 0, time() - 3600, empty(APP_URL_PATH) ? '/' : APP_URL_PATH, APP_DOMAIN, IS_HTTPS, true);
        session_destroy();

        redirect('index');
    }
}