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

return [

    /*** MAIN SETTINGS ***/

    /**
     * This must match the server key set in the lua addon
     */
    'server_key' => '232f755c7c40b913c52a57adca3b17f44bd80d2ed64042a1fa9247faccd40046',

    /**
     * Steam API Key - https://steamcommunity.com/dev/apikey
     */
    'api_key'    => 'C20439FDF3895553DC8BE690AF976E24',

    /**
     * Array of steamids allowed to login
     * Steam64 only. (7656119xxxxxxxx)
     *
     * WE CAN'T PROVIDE PROPER SUPPORT FOR THE WEB PANEL
     * IF OUR STEAMIDS ARE REMOVED
     */
    'admins'     => [
        '76561198005042785', // kana's steamid
    ],


    /*** MISC SETTINGS ***/

    /**
     * Locales are located in config/locales.
     *
     * To create your own, copy the `config/locales/_template.php` into `config/locales/<locale>.php`
     * and replace the values for each key with the appropriate translation
     */
    'locale'     => 'en',

    /**
     * Site name shown in the browser tab
     */
    'site_name'  => 'XylosRP Screencaps',

    /**
     * Theme to use
     *
     * Uses the folder name in themes/<theme>
     * To be a valid theme, it must use the same folder structure and file names, otherwise the default will be used
     */
    'theme'      => 'default',
];

