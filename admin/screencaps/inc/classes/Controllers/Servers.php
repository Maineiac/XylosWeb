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

use function count;
use function file_get_contents;
use function glob;
use function json_decode;
use function round;
use function view;

class Servers
{
    public function index()
    {

        $servers = glob(APP_ROOT.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'servers'.DIRECTORY_SEPARATOR.'*.json');

        $servers_info = [];

        foreach ($servers as $server) {
            $server = json_decode(file_get_contents($server), true);
            $server['percentage_full'] = round((count($server['players']) / $server['max_players']) * 100, 2);
            $servers_info[] = $server;
        }

        $data = [
            'servers' => $servers_info,
        ];

        return view('servers', $data);
    }
}