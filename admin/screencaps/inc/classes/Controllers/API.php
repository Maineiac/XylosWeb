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
use function array_diff;
use function array_keys;
use function count;
use function file_put_contents;
use function json_decode;
use function json_encode;
use function setcookie;
use function time;

class API
{
    public function updateServer()
    {
        $keys = ['key', 'ip', 'port', 'hostname', 'gamemode', 'map', 'players', 'address', 'max_players'];

        $valid = count(array_diff($keys, array_keys($_POST))) === 0;

        if ($valid) {
            if ($_POST['key'] === Util::getSetting('server_key')) {
                unset($_POST['key']);
                $data = $_POST;
                $data['players'] = json_decode($data['players'], true);
                $data['request_ip'] = !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
                file_put_contents(APP_ROOT.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'servers'.DIRECTORY_SEPARATOR.$data['ip'].'_'.$data['port'].'.json', json_encode($data));

                return $data;
            }
        }

        return false;
    }

    public function rememberMe()
    {
        $data['success'] = false;

        if (isset($_POST['checked'])) {
            $data['success'] = setcookie('remember_me', (int) ($_POST['checked'] == 'true'), time() + 3600, empty(APP_URL_PATH) ? '/' : APP_URL_PATH, APP_DOMAIN, IS_HTTPS, true);
        }

        return $data;
    }
}