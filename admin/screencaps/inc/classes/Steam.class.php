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

use J0sh0nat0r\SimpleCache\StaticFacade as Cache;

class Steam
{
    /**
     * Steam XML API
     */
    const STEAM_XML = 'https://steamcommunity.com/profiles/%s/?xml=1';

    /**
     * personastates.
     */
    protected static $personastates = [
        'Offline',
        'Online',
        'Busy',
        'Away',
        'Snooze',
        'Looking to trade',
        'Looking to play',
    ];

    /**
     * $api_url Steam Player API
     *
     * @var string
     */
    private static $api_url = 'https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=%s&steamids=%s';

    /**
     * $api_key Steam API key - https://steamcommunity.com/dev/apikey
     *
     * @var string
     */
    private static $api_key;

    /**
     * $curl_options array of curl options
     *
     * @var array
     */
    private static $curl_options = [
        CURLOPT_RETURNTRANSFER => true,
    ];

    /**
     * Set the API to be used
     *
     * @param string|null
     *
     * @throws Exception
     */
    public static function setKey($api_key = null)
    {
        if (is_null($api_key) || empty($api_key)) {
            throw new Exception("API key not given, please fix it in your settings.php");
        }

        self::$api_key = $api_key;
        self::$api_url = sprintf(self::$api_url, $api_key, '%s');
    }

    /**
     * Gets a player's profile info and returns it
     * altenate method to allow cookie based login
     *
     * @param int|string
     *
     * @throws \Exception
     * @return \stdClass|null
     */
    public static function getPlayerAlt($steamid)
    {
        $player = new stdClass();
        $player->steamid = $steamid;


        $x = ($player->steamid >> 56) & 0xFF;
        $y = 0;
        $z = ($player->steamid >> 1) & 0x7FFFFFF;

        $player->steamid2 = "STEAM_$x:$y:$z";
        $player->steamid3 = '[U:1:'.($z * 2 + $y).']';

        $method = is_null(self::$api_key) ? 'xml' : 'api';
        switch ($method) {
            case 'api':
                if (ENABLE_CACHE) {
                    $info = Cache::remember('steam-api-alt-'.$steamid, 3600, function () use ($steamid) {
                        $info = json_decode(Util::curl(sprintf(self::$api_url, $steamid), self::$curl_options))->response->players;
                        $info = isset($info[0]) ? $info[0] : null;

                        return $info;
                    });
                } else {
                    $info = json_decode(Util::curl(sprintf(self::$api_url, $steamid), self::$curl_options))->response->players;
                    $info = isset($info[0]) ? $info[0] : null;
                }

                $length = count((array) $info);

                if ($length > 0) {
                    $player->name = $info->personaname;
                    $player->realName = !empty($info->realname) ? $info->realname : null;
                    $player->playerState = $info->personastate != 0 ? 'Online' : 'Offline';
                    $player->privacyState = ($info->communityvisibilitystate == 1 || $info->communityvisibilitystate == 2) ? 'Private' : 'Public';
                    $player->stateMessage = isset(self::$personastates[$info->personastate]) ? self::$personastates[$info->personastate] : $info->personastate;
                    $player->visibilityState = $info->communityvisibilitystate;
                    $player->avatarSmall = $info->avatar;
                    $player->avatarMedium = $info->avatarmedium;
                    $player->avatarLarge = $info->avatarfull;
                    $player->joined = isset($info->timecreated) ? $info->timecreated : null;
                }
                break;
            case 'xml':
                if (ENABLE_CACHE) {
                    $info = Cache::remember('steam-xml-alt-'.$steamid, 3600, function () use ($steamid) {
                        return simplexml_load_string(Util::curl(sprintf(self::STEAM_XML, $steamid), self::$curl_options), 'SimpleXMLElement', LIBXML_NOCDATA);
                    });
                } else {
                    $info = simplexml_load_string(Util::curl(sprintf(self::STEAM_XML, $steamid), self::$curl_options), 'SimpleXMLElement', LIBXML_NOCDATA);
                }

                if ($info !== false && !isset($info->error)) {
                    $player->name = (string) $info->steamID;
                    $player->realName = !empty($info->realName) ? $info->realName : null;
                    $player->playerState = ucfirst($info->onlineState);
                    $player->privacyState = ($info->privacyState == 'friendsonly' || $info->privacyState == 'private') ? 'Private' : 'Public';
                    $player->stateMessage = (string) $info->stateMessage;
                    $player->visibilityState = (int) $info->visibilityState;
                    $player->avatarSmall = (string) $info->avatarIcon;
                    $player->avatarMedium = (string) $info->avatarMedium;
                    $player->avatarLarge = (string) $info->avatarFull;
                    $player->joined = isset($info->memberSince) ? strtotime($info->memberSince) : null;
                } else {
                    if (DEBUG) {
                        throw new Exception('No XML data please look into this: '.(isset($info['error']) ? $info['error'] : ''));
                    }
                }
                break;
            default:
                break;
        }

        return $player;
    }

    /**
     * Gets multiple players' profiles and returns it
     *
     * @param array
     *
     * @return \stdClass|null
     */
    public static function getPlayers(...$steamids)
    {
        if (is_array($steamids[0])) {
            $steamids = $steamids[0];
        }

        $data = [];

        if (is_null(self::$api_key)) {
            foreach ($steamids as $steamid) {
                $info = self::getPlayer($steamid);

                if (!empty($info)) {
                    $data[] = $info;
                }
            }
        } else {
            $steamids = implode(',', $steamids);
            $data = json_decode(Util::curl(sprintf(self::$api_url, $steamids), self::$curl_options), true);
            $data = $data !== false ? (isset($data['response']['players']) ? $data['response']['players'] : []) : [];
        }

        return count($data) > 0 ? $data : null;
    }

    /**
     * Gets a player's profile info and returns it
     *
     * @param int|string
     *
     * @return \stdClass|null
     */
    public static function getPlayer($steamid)
    {
        $player = new stdClass();
        $player->steamid = (string) $steamid;

        if (is_null(self::$api_key)) {
            if (ENABLE_CACHE) {
                $info = Cache::remember('steam-xml-'.$steamid, 3600, function () use ($steamid) {
                    return simplexml_load_string(Util::curl(sprintf(self::STEAM_XML, $steamid), self::$curl_options), 'SimpleXMLElement', LIBXML_NOCDATA);
                });
            } else {
                $info = simplexml_load_string(Util::curl(sprintf(self::STEAM_XML, $steamid), self::$curl_options), 'SimpleXMLElement', LIBXML_NOCDATA);
            }

            if ($info !== false && !isset($info->error)) {
                $player->personaname = (string) $info->steamID;
                $player->realname = !empty($info->realName) ? $info->realName : null;
                $player->personastate = ucfirst($info->onlineState);
                $player->communityvisibilitystate = ($info->privacyState == 'friendsonly' || $info->privacyState == 'private') ? 'Private' : 'Public';
                $player->avatar = (string) $info->avatarIcon;
                $player->avatarmedium = (string) $info->avatarMedium;
                $player->avatarfull = (string) $info->avatarFull;
            } else {
                $player = null;
            }

        } else {
            if (ENABLE_CACHE) {
                $info = Cache::remember('steam-api-'.$steamid, 3600, function () use ($steamid) {
                    $info = json_decode(Util::curl(sprintf(self::$api_url, $steamid), self::$curl_options))->response->players;
                    $info = isset($info[0]) ? $info[0] : null;

                    return $info;
                });
            } else {
                $info = json_decode(Util::curl(sprintf(self::$api_url, $steamid), self::$curl_options))->response->players;
                $info = isset($info[0]) ? $info[0] : null;
            }

            if (!is_null($info)) {
                $player->personaname = $info->personaname;
                $player->realname = !empty($info->realname) ? $info->realname : null;
                $player->personastate = $info->personastate != 0 ? 'Online' : 'Offline';
                $player->communityvisibilitystate = ($info->communityvisibilitystate == 1 || $info->communityvisibilitystate == 2) ? 'Private' : 'Public';
                $player->avatar = $info->avatar;
                $player->avatarmedium = $info->avatarmedium;
                $player->avatarfull = $info->avatarfull;
            } else {
                $player = null;
            }

        }

        return $player;
    }


}
