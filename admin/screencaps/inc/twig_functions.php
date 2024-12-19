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

use kanalumaddela\SteamLogin\SteamLogin;

/*** IMPORTANT FUNCTIONS ***/

$twig_function = new \Twig\TwigFunction('route', function ($route = null, array $parameters = [], $include_query = false) {
    return call_user_func_array('route', func_get_args());

    //return (is_null($route) ? (ENABLE_FRIENDLY_URLS ? APP_URL_PATH : APP_ROUTE) : APP_ROUTE.'/'.$route.(strlen($parameters) > 0 ? '/'.$parameters : ''));
});
$twig->addFunction($twig_function);

$twig_function = new \Twig\TwigFunction('asset', function ($file, $cache_buster = false) {
    return APP_URL_PATH.'/assets/'.$file.($cache_buster ? '?v='.CACHE_BUSTER : '');
});
$twig->addFunction($twig_function);

$twig_function = new \Twig\TwigFunction('theme', function ($file = null, $cache_buster = false) {
    $theme = Util::getSetting('theme', 'default');
    $url = APP_URL_PATH.'/themes/'.$theme.'/assets/';

    return new \Twig\Markup(is_null($file) ? '<link rel="stylesheet" href="'.$url.'css/'.$theme.'.css'.($cache_buster ? '?v='.CACHE_BUSTER : '').'">' : $url.$file.($cache_buster ? '?v='.CACHE_BUSTER : ''), 'UTF-8');
});
$twig->addFunction($twig_function);

$twig_function = new \Twig\TwigFunction('SteamLogin', function ($type = 'small') use ($steamlogin) {
    return new \Twig\Markup('<a class="steam-login" href="'.$steamlogin->getLoginURL().'">'.SteamLogin::button($type, true).'</a>', 'UTF-8');
});
$twig->addFunction($twig_function);

$twig_function = new \Twig\TwigFunction('lang', function ($key) {
    global $lang, $lang_fallback;

    $phrase = !empty($lang[$key]) ? $lang[$key] : (!empty($lang_fallback[$key]) ? $lang_fallback[$key] : $key);

    return $phrase;
});
$twig->addFunction($twig_function);


/*** HELPER FUNCTIONS ***/

$twig_filter = new \Twig\TwigFilter('json', function ($string, $pretty_print = false) {
    return new \Twig\Markup(json_encode($string, $pretty_print ? JSON_PRETTY_PRINT : 0), 'UTF-8');
});
$twig->addFilter($twig_filter);

$twig_filter = new \Twig\TwigFilter('shorten', function ($string, $length = 250, $raw = false) {
    $string = substr($string, 0, $length).'...';

    return ($raw ? new \Twig\Markup($string, 'UTF-8') : $string);
});
$twig->addFilter($twig_filter);

$twig_filter = new \Twig\TwigFilter('truncate', function ($string, $length = 250, $raw = false) {
    $string = substr($string, 0, $length).'...';

    return ($raw ? new \Twig\Markup($string, 'UTF-8') : $string);
});
$twig->addFilter($twig_filter);

$twig_filter = new \Twig\TwigFilter('h_m_s', function ($seconds) {
    $hours = floor($seconds / 3600);
    $seconds = $seconds % 3600;
    $minutes = floor($seconds / 60);
    $seconds = $seconds % 60;

    $time = ($hours < 10 ? '0'.$hours : $hours).':'.($minutes < 10 ? '0'.$minutes : $minutes).':'.($seconds < 10 ? '0'.$seconds : $seconds);

    return $time;
});
$twig->addFilter($twig_filter);


/*** MISC FUNCTIONS ***/

$twig_function = new \Twig\TwigFunction('lorem', function ($length = 750) {
    return substr(file_get_contents(APP_ROOT.'/data/lorem_ipsum.txt'), 0, $length);
});
$twig->addFunction($twig_function);

$twig_function = new \Twig\TwigFunction('rand_number', function ($max = 1000) {
    return rand(0, $max);
});
$twig->addFunction($twig_function);

$twig_function = new \Twig\TwigFunction('rand_string', function ($length = 30) {

    return bin2hex(function_exists('random_bytes') ? random_bytes($length) : openssl_random_pseudo_bytes($length));
});
$twig->addFunction($twig_function);
