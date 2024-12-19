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
use function array_unique;
use function base64_decode;
use function basename;
use function boolval;
use function count;
use function date;
use function date_create_from_format;
use function date_format;
use function dirname;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function implode;
use function in_array;
use function json_decode;
use function json_encode;
use function md5;
use function pathinfo;
use function preg_match;
use function rename;
use function str_replace;
use function unlink;
use function view;

class Screenshots
{
    /**
     * Screenshot gallery listing
     *
     * @param int $page
     *
     * @return string
     */
    public function index($page = 1)
    {
        $screenshots = Util::getScreenshots($page, null, true);

        $steamids = implode(',', array_unique(array_column($screenshots, 'player')));

        $steam_info = ENABLE_CACHE ? Cache::remember('steam-users-'.md5($steamids), 3600, function () use ($steamids) {
            return Steam::getPlayers($steamids);
        }) : Steam::getPlayers($steamids);
        if ($steam_info) {
            Util::fixSteamInfo($steam_info, isset($steam_info[0]['steamid']));
        }

        $data = [
            'screenshots' => $screenshots,
            'steaminfo'   => $steam_info,
        ];

        return view('screenshots', $data);
    }

    /**
     * Deletes a given image
     *
     * @return array
     */
    public function trash()
    {
        $deleted = false;
        $id = null;

        if (isset($_POST['screenshot'])) {
            if (!empty(APP_URL_PATH)) {
                $screenshot = str_replace(APP_URL_PATH, APP_ROOT, $_POST['screenshot']);
            } else {
                $screenshot = APP_ROOT.str_replace('/', DIRECTORY_SEPARATOR, $_POST['screenshot']);
            }

            if (file_exists($screenshot)) {
                $filename = basename($screenshot);
                $fileinfo = pathinfo($screenshot);
                $extension = $fileinfo['extension'];

                $date = basename($screenshot, '.'.$extension);
                $date = date_create_from_format('Y_m_d ___ H_i_s', $date);
                $timestamp = date_format($date, 'U');

                $steamid = basename(dirname($screenshot));
                Util::mkDir(APP_ROOT.'/data/trash/'.$steamid);
                $id = 'screenshot_'.$steamid.'_'.$timestamp;
                $deleted = rename($screenshot, APP_ROOT.'/data/trash/'.$steamid.'/'.$filename);
            }
        }

        $data = [
            'success'  => $deleted,
            'image_id' => $id,
        ];

        return $data;
    }

    public function auth()
    {
        if (isset($_GET["shoulddo"]) && isset($_GET["sid64"])) {
            $steamid = $_GET["sid64"];
            $should_do = $_GET["shoulddo"];

            $perm_dir = APP_ROOT.DIRECTORY_SEPARATOR.'hasperm';
            Util::mkDir($perm_dir);

            $auth_file_path = $perm_dir.DIRECTORY_SEPARATOR.$steamid;
            $existing_auth_file = file_exists($auth_file_path);

            if ($should_do == 1 && $existing_auth_file) {
                unlink($auth_file_path);
            }

            file_put_contents($auth_file_path, $should_do);

            return $should_do;
        } else {
            die();
        }
    }

    public function requestcap()
    {
        if (isset($_GET["serverkey"]) && isset($_GET["sid64"])) {
            if ($_GET["serverkey"] === Util::getSetting('server_key')) {
                $steamid = $_GET['sid64'];

                $webcaps_dir = APP_ROOT.DIRECTORY_SEPARATOR.'webcaps';
                $perm_dir = APP_ROOT.DIRECTORY_SEPARATOR.'hasperm';

                Util::mkDir($webcaps_dir);
                Util::mkDir($perm_dir);

                if (isset($_GET["isfin"]) && $_GET['isfin'] == 1) {
                    if (file_exists($webcaps_dir.DIRECTORY_SEPARATOR.$steamid) && isset($_GET['server'])) {
                        $webcap = json_decode(file_get_contents($webcaps_dir.DIRECTORY_SEPARATOR.$steamid), true);

                        if ($_GET['server'] == $webcap['server']) {
                            unlink($webcaps_dir.DIRECTORY_SEPARATOR.$steamid);
                        }

                        die();
                    }
                }

                $array = [
                    'sid64'        => $steamid.'b',
                    'method'       => isset($_GET["method"]) ? (int) $_GET["method"] : 2,
                    'quality'      => isset($_GET["quality"]) ? (int) $_GET["quality"] : 17,
                    'wants_stream' => isset($_GET["wants_stream"]) ? boolval($_GET["wants_stream"]) : false,
                    'stream_wait'  => isset($_GET["stream_wait"]) ? (int) $_GET["stream_wait"] : 0,
                    'timestamp'    => time(),
                    'server'       => $_GET['server'] ?: 0,
                ];

                Util::Log("Request capture created for $steamid: method: ".$array['method']." quality: ".$array['quality']." wants_stream: ".$array['wants_stream']." stream_wait: ".$array['stream_wait']);
                file_put_contents(APP_ROOT.DIRECTORY_SEPARATOR.'webcaps'.DIRECTORY_SEPARATOR.$steamid, json_encode($array));
            }
        }
    }

    public function savedata()
    {
        $valid_file_formats = ['bmp', 'jpg', 'png', 'tga'];

        if (count(array_diff(['data', 'datatype', 'fileformat', 'sid64'], array_keys($_POST))) === 0) {
            $data = $_POST["data"];
            $media_type = $_POST["datatype"];
            $file_format = $_POST["fileformat"];
            $steamid = $_POST["sid64"];

            if (preg_match('/\d{17,17}/', $steamid)) {
                $image = base64_decode($data, true);

                if ($image && ($media_type == 'stream' || $media_type == 'screenshot')) {
                    if (in_array($file_format, $valid_file_formats)) {

                        $auth_file = APP_ROOT.DIRECTORY_SEPARATOR.'hasperm'.DIRECTORY_SEPARATOR.$steamid;

                        if (file_exists($auth_file)) {
                            $auth_file_contents = file_get_contents($auth_file);

                            if ($media_type == 'screenshot' || $auth_file_contents == 2) {
                                unlink($auth_file);
                                $file_name = date("Y_m_d ___ H_i_s").'.'.$file_format;
                            }
                            if ($media_type == 'stream') {
                                $file_name = $file_format.'.stream';
                            }

                            Util::Log("Screenshot captured for '$steamid' saved as $file_name");
                            self::save($media_type.'s', $steamid, $file_name, $image);

                            return APP_URL.'/'.$media_type."s/{$steamid}/{$file_name}";
                        }
                    }
                }
            }
        } else {
            die('missing data');
        }
    }

    public static function save($type, $steamid, $filename, $file_contents)
    {
        $dir = APP_ROOT.DIRECTORY_SEPARATOR.$type.DIRECTORY_SEPARATOR.$steamid;

        if (!file_exists($dir)) {
            Util::mkDir($dir);
        }

        Util::Log("Attempted to save $type for '$steamid' named $filename in: $dir");
        file_put_contents($dir.DIRECTORY_SEPARATOR.$filename, $file_contents);
    }

    public function sendwebcaps()
    {
        Util::Log("Webcaps requested");

        $webcaps_dir = APP_ROOT.DIRECTORY_SEPARATOR.'webcaps';
        Util::mkDir($webcaps_dir);

        $webcaps = glob(APP_ROOT.'/webcaps/*');

        $data = [];

        if (count($webcaps) > 0) {
            foreach ($webcaps as $file) {
                if (isset($_GET['server'])) {
                    $contents = file_get_contents($file);
                    $webcap = json_decode($contents, true);

                    if (isset($webcap['server']) && $webcap['server'] == $_GET['server']) {
                        $data[] = $contents;
                        unlink($file);
                    }
                }
            }
        }

        return $data;
    }
}
