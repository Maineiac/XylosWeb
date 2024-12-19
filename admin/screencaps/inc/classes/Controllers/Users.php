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

use J0sh0nat0r\SimpleCache\StaticFacade as Cache;
use Steam;
use Util;
use function array_column;
use function array_diff;
use function array_keys;
use function count;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function implode;
use function json_decode;
use function json_encode;
use function str_replace;
use function view;

class Users
{

    public function index($page = 1)
    {
        $users = Util::getUsers($page);

        $steamids = implode(',', array_column($users, 'steamid'));

        $steam_info = ENABLE_CACHE ? Cache::remember('steam-users-'.md5($steamids), 3600, function () use ($steamids) {
            return Steam::getPlayers($steamids);
        }) : Steam::getPlayers($steamids);
        if ($steam_info) {
            Util::fixSteamInfo($steam_info, isset($steam_info[0]['steamid']));
        }

        $data = [
            'users'     => $users,
            'steaminfo' => $steam_info,
        ];

        return view('users', $data);
    }

    public function user($steamid)
    {
        $data = [
            'player' => [
                'info'        => Steam::getPlayer($steamid),
                'steamid'     => $steamid,
                'screenshots' => Util::getScreenshots('all', $steamid),
            ],
        ];

        return view('user', $data);
    }

    /**
     * Request user to be screenshotted
     *
     * @return array
     */
    public function capture()
    {
        $success = false;

        $keys = ['screenshot-steamid', 'screenshot-method', 'screenshot-quality', 'screenshot-server'];

        $valid = count(array_diff($keys, array_keys($_POST))) === 0;

        if ($valid) {
            $webcaps_dir = APP_ROOT.DIRECTORY_SEPARATOR.'webcaps';

            Util::mkDir($webcaps_dir);

            $steamid = $_POST['screenshot-steamid'];

            if ($steamid == 'all' && isset($_POST['screenshot-server'])) {
                $server = str_replace(':', '_', $_POST['screenshot-server']);
                $server_file = APP_ROOT.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'servers'.DIRECTORY_SEPARATOR.$server.'.json';
                if (file_exists($server_file)) {
                    $server_info = json_decode(file_get_contents($server_file), true);
                    $players = $server_info['players'];
                    $player_count = count($players);
                    for ($i = 0; $i < $player_count; $i++) {
                        $success = self::requestCapture($players[$i][1], $_POST);
                    }
                }
            } else {
                $success = self::requestCapture($steamid, $_POST);
            }
        }

        return ['success' => $success];
    }

    private static function requestCapture($steamid, $post)
    {
        $data = [
            'sid64'        => $steamid.'b',
            'method'       => isset($post['screenshot-method']) ? (int) $post['screenshot-method'] : 2,
            'quality'      => isset($post['screenshot-quality']) ? (int) $post['screenshot-quality'] : 17,
            'wants_stream' => false,
            'stream_wait'  => 0,
            'server'       => $post['screenshot-server'],
        ];

        Util::Log("Request capture created for $steamid: method: ".$data['method']." quality: ".$data['quality']." wants_stream: ".$data['wants_stream']." stream_wait: ".$data['stream_wait']);
        $webcap_file = APP_ROOT.DIRECTORY_SEPARATOR.'webcaps'.DIRECTORY_SEPARATOR.$steamid;
        file_put_contents($webcap_file, json_encode($data));

        return file_exists($webcap_file);
    }

}