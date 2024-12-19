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

class Util
{
    /**
     * Basic cURL with passable options
     *
     * @param string
     * @param array
     *
     * @return string
     */
    public static function curl($url, array $options = [])
    {
        $curl = curl_init($url);

        foreach ($options as $option => $value) {
            if (!empty($value)) {
                curl_setopt($curl, $option, $value);
            } else {
                curl_setopt($curl, $option);
            }
        }

        $data = curl_exec($curl);
        curl_close($curl);

        return $data;
    }

    /**
     * Return config values or set defaults
     *
     * @param string
     * @param string
     *
     * @return mixed
     */
    public static function getSetting($key, $default = null)
    {
        global $config;

        if (!isset($config)) {
            $config = include APP_ROOT.'/config/settings.php';
        }

        return !empty($config[$key]) ? $config[$key] : $default;
    }

    /**
     * Return if a config key exists
     *
     * @param string
     * @param string
     *
     * @return mixed
     */
    public static function hasSetting($key)
    {
        global $config;

        if (!isset($config)) {
            $config = include APP_ROOT.'/config/settings.php';
        }

        return !empty($config[$key]);
    }

    /**
     * File logging
     *
     * @param string
     * @param string
     * @param boolean
     */
    public static function Log($content = null, $type = 'log', $linebreak = false)
    {
        if (!ENABLE_LOGGING) {
            return;
        }
        if (is_null($content)) {
            $content = 'Request: '.$_SERVER['REQUEST_METHOD'].' '.$_SERVER['REQUEST_URI'].'  IP Address: '.$_SERVER['REMOTE_ADDR'];
            $type = 'access';
        }

        $year = date('Y');
        $month = date('F');

        $log_folder = APP_ROOT."/data/logs/$year/$month/";

        if (!file_exists($log_folder)) {
            self::mkdir($log_folder);
        }
        if (!file_exists($log_folder.'/index.html') || !file_exists($log_folder.'/.htaccess')) {
            copy(APP_ROOT.'/inc/index.html', $log_folder.'/index.html');
            copy(APP_ROOT.'/inc/.htaccess', $log_folder.'/.htaccess');
        }

        $log_filename = date('d.\l\o\g');

        $log_content = ($linebreak ? "\n" : '').'['.strtoupper($type).'] ['.date(LOG_DATE_FORMAT).'] - '.$content."\n".($linebreak ? "\n" : '');

        $file = fopen($log_folder.$log_filename, 'a');
        fwrite($file, $log_content);
        fclose($file);
    }

    /**
     * Create a directory
     *
     * @param string
     *
     * @return void
     */
    public static function mkDir($directory)
    {
        if (!file_exists($directory)) {
            set_error_handler(function () {
            });
            $check = mkdir($directory, 0775, true);
            restore_error_handler();
            if (!$check) {
                die('no perms to create directory, fix it');
            }
        }
    }

    /**
     * Delete a directory recursively
     *
     * @param string $directory
     *
     * @return void
     */
    public static function rmDir($directory)
    {
        $content = glob($directory.'/*');
        foreach ($content as $location) {
            if (is_dir($location)) {
                self::rmdir($location);
            } else {
                unlink($location);
            }
        }
        rmdir($directory);
    }

    /**
     * Check if request is ajax/xmlhttprequest
     *
     * @return boolean
     */
    public static function isAjax()
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
    }

    /**
     * Create a hash
     *
     * @param integer $length
     * @param boolean $legacy
     *
     * @throws Exception
     * @return string
     */
    public static function Hash($length = 15, $legacy = false)
    {
        $length = ceil($length / 2);

        return bin2hex(function_exists('random_bytes') && !$legacy ? random_bytes($length) : openssl_random_pseudo_bytes($length));
    }

    /**
     * Returns whether or not a folder has pictures in it.
     *
     * @param  [type] $folder [description]
     *
     * @return [type]         [description]
     */
    public static function screenshotFilter($folder)
    {
        return (count(glob($folder.DIRECTORY_SEPARATOR.'*.{bmp,jpg,png,tga}', GLOB_BRACE)) > 0);
    }

    /**
     * Sort files by date descending
     *
     * @param string $a
     * @param string $b
     *
     * @return int
     */
    public static function sortFiles($a, $b)
    {
        $a = filemtime($a);
        $b = filemtime($b);

        return ($a == $b ? 0 : ($a > $b ? -1 : 1));
    }

    /**
     * Get all the screenshots or those of a specific user
     *
     * @param int    $page
     * @param string $steamid
     *
     * @param bool   $ignoreInvalid
     *
     * @return array
     */
    public static function getScreenshots($page = 1, $steamid = '*', $ignoreInvalid = false)
    {
        $screenshots = [];

        if (empty($steamid)) {
            $steamid = '*';
        }

        $folders = glob(APP_ROOT.DIRECTORY_SEPARATOR.'screenshots'.DIRECTORY_SEPARATOR.$steamid.DIRECTORY_SEPARATOR.'*.{jpg,png,'.(!$ignoreInvalid ? 'tga,bmp' : '').'}', GLOB_BRACE);
        usort($folders, 'self::sortFiles');

        $total = count($folders);
        $pages = ceil($total / ITEMS_PER_PAGE);
        $page = $page <= $pages ? $page : 1;

        self::setPages($total, $page, 'screenshots');

        $screenshot_folders = is_numeric($page) ? array_slice($folders, $page * ITEMS_PER_PAGE - ITEMS_PER_PAGE, ITEMS_PER_PAGE) : $folders;

        $length = count($screenshot_folders);
        for ($i = 0; $i < $length; $i++) {
            $steamid = basename(dirname($screenshot_folders[$i]));

            $fileinfo = pathinfo($screenshot_folders[$i]);
            $extension = $fileinfo['extension'];

            $date = basename($screenshot_folders[$i], '.'.$extension);
            $date = date_create_from_format('Y_m_d ___ H_i_s', $date);
            $timestamp = date_format($date, 'U');

            $image = str_replace(DIRECTORY_SEPARATOR, '/', str_replace(APP_ROOT, APP_URL_PATH, $screenshot_folders[$i]));

            $screenshots[] = [
                'id'        => 'screenshot_'.$steamid.'_'.$timestamp,
                'timestamp' => $timestamp,
                'date'      => date(IMAGE_DATE_FORMAT, $timestamp),
                'image'     => $image,
                'player'    => $steamid,
            ];
        }

        return $screenshots;
    }

    /**
     * Create pages variable
     *
     * @param        $total
     * @param int    $page
     * @param string $type
     */
    public static function setPages($total, $page = 1, $type = 'screenshots')
    {
        global $twig_data;

        $twig_data['pages'] = [
            'current' => $page,
            'total'   => ceil($total / ITEMS_PER_PAGE),
            'results' => $total,
            'type'    => $type,
        ];

    }

    /**
     * Get users
     *
     * @param int $page
     *
     * @return array
     */
    public static function getUsers($page = 1)
    {
        $users = [];

        $folders = glob(APP_ROOT.DIRECTORY_SEPARATOR.'screenshots'.DIRECTORY_SEPARATOR.'*', GLOB_ONLYDIR);
        $folders = array_filter($folders, 'Util::screenshotFilter');

        $total = count($folders);
        $pages = ceil($total / ITEMS_PER_PAGE);
        $page = $page <= $pages ? $page : 1;

        self::setPages($total, $page, 'users');

        $user_folders = array_slice($folders, ($page - 1) * ITEMS_PER_PAGE - ITEMS_PER_PAGE, ITEMS_PER_PAGE);

        $steamids = [];
        $length = count($user_folders);
        for ($i = 0; $i < $length; $i++) {
            $steamid = basename($user_folders[$i]);
            $steamids[] = $steamid;

            $users[] = [
                'steamid'     => $steamid,
                'screenshots' => count(glob(APP_ROOT.DIRECTORY_SEPARATOR.'screenshots'.DIRECTORY_SEPARATOR.$steamid.DIRECTORY_SEPARATOR.'*.{bmp,jpg,png,tga}', GLOB_BRACE)),
                'streaming'   => count(glob(APP_ROOT.DIRECTORY_SEPARATOR.'streams'.DIRECTORY_SEPARATOR.$steamid.DIRECTORY_SEPARATOR.'*')) > 0,
            ];

        }

        return $users;
    }

    /**
     * Set steamids as key of steaminfo
     *
     * @param array
     *
     * @return void
     */
    public static function fixSteamInfo(array &$data, $isJson)
    {
        $fixed_array = [];
        $count = count($data);

        if ($isJson) {
            foreach ($data as $row) {
                $fixed_array[$row['steamid']] = $row;
            }
        } else {
            for ($i = 0; $i < $count; $i++) {
                $fixed_array[$data[$i]->steamid] = $data[$i];
            }
        }

        $data = $fixed_array;
    }

}