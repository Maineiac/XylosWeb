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

// let's begin
use J0sh0nat0r\SimpleCache\StaticFacade as Cache;
use kanalumaddela\SteamLogin\SteamLogin;

// class autoloader
function autoload_classes($class_name)
{
    $class_name = (strpos($class_name, '\\') !== false ? str_replace('\\', DIRECTORY_SEPARATOR, $class_name).'.php' : $class_name.'.class.php');

    $file = __DIR__.'/classes/'.$class_name;
    $file_mod = __DIR__.'/classes/modified/'.$class_name;

    $load_file = file_exists($file) ? (file_exists($file_mod) ? (filemtime($file) < filemtime($file_mod) ? $file_mod : $file) : $file) : $file;

    if (file_exists($load_file)) {
        require_once $load_file;
    } else {
        throw new Exception("Failed to load: $class_name. Looked in <code>$load_file</code>");
    }
}

spl_autoload_register('autoload_classes');

require_once APP_ROOT.'/vendor/autoload.php';
require_once APP_ROOT.'/inc/helpers.php';

// redirect func
function redirect($path, $external = false)
{
    header('Location: '.($external ? $path : (IS_HTTPS ? 'https://' : 'http://').APP_DOMAIN.(APP_PORT !== 80 && APP_PORT !== 443 ? ':'.APP_PORT : '').route($path)));
    die();
}


// route fix pt. 1
foreach ($_GET as $key => $value) {
    if (substr($key, 0, 1) === "/") {
        $redirect_fix = $key;
        $route_fix = '?'.$key;
    }
    break;
}

// important constants
define('IS_HTTPS', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) ? $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' : false));
define('APP_HOST', $_SERVER['HTTP_HOST']);

define('APP_DOMAIN', strtok($_SERVER['HTTP_HOST'], ':'));
define('APP_PORT', (int) $_SERVER['SERVER_PORT']);
define('APP_URL_PATH', rtrim(str_replace('/'.basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']), '/'));
define('APP_URL', (IS_HTTPS ? 'https://' : 'http://').APP_DOMAIN.(APP_PORT !== 80 && !IS_HTTPS ? ':'.APP_PORT : '').APP_URL_PATH);
define('CACHE_BUSTER', Util::Hash(5));


$getKeys = array_keys($_GET);
if (in_array(basename($_SERVER['SCRIPT_NAME']), $fake_files)) {
    $route = '/'.basename($_SERVER['SCRIPT_NAME']);
} elseif (strpos($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME']) !== false && empty($getKeys)) {
    $route = str_replace($_SERVER['SCRIPT_NAME'], '', $_SERVER['REQUEST_URI']);
} elseif (isset($getKeys[0]) && strpos($getKeys[0], '/') !== false) {
    $route = $getKeys[0];
} else {
    if (!empty(APP_URL_PATH)) {
        $replace_pos = strpos($_SERVER['REQUEST_URI'], APP_URL_PATH);
        if ($replace_pos !== false) {
            $route = substr_replace($_SERVER['REQUEST_URI'], '', $replace_pos, strlen(APP_URL_PATH));
        }
    } else {
        $route = strtok($_SERVER["REQUEST_URI"], '?');
    }
}

define('APP_CURRENT_ROUTE', !empty($route) && $route !== '/' ? rtrim($route, '/') : '/');
define('APP_URL_CURRENT', APP_URL.(ENABLE_FRIENDLY_URLS || APP_CURRENT_ROUTE === '/' ? '' : '/'.basename(PHP_SELF).'?').APP_CURRENT_ROUTE);


// let's remove some old stuff
// by now everyone should be running the recoded version
//$remove_files = [
//    'panel',
//    'index.html',
//    'settings.php',
//    'style.css',
//    'inc/htaccess-deny.txt',
//];
//$remove_files_length = count($remove_files);
//
//for ($i = 0; $i < $remove_files_length; $i++) {
//    if (file_exists(APP_ROOT.'/'.$remove_files[$i])) {
//        if (is_dir(APP_ROOT.'/'.$remove_files[$i])) {
//            Util::rmDir(APP_ROOT.'/'.$remove_files[$i]);
//        } else {
//            unlink(APP_ROOT.'/'.$remove_files[$i]);
//        }
//    }
//}

// lets make some directories
Util::mkDir(APP_ROOT.'/screenshots');
Util::mkDir(APP_ROOT.'/streams');
Util::mkDir(APP_ROOT.'/webcaps');
Util::mkDir(APP_ROOT.'/hasperm');
Util::mkDir(APP_ROOT.'/data/debug');
Util::mkDir(APP_ROOT.'/data/logins');
Util::mkDir(APP_ROOT.'/data/logs');
Util::mkDir(APP_ROOT.'/data/servers');
Util::mkDir(APP_ROOT.'/data/trash');

// caching
$cache = new J0sh0nat0r\SimpleCache\Cache(J0sh0nat0r\SimpleCache\Drivers\File::class, ['dir' => APP_ROOT.'/cache']);
Cache::bind($cache);

// lets make some special files too
if (!file_exists(__DIR__.'/index.html')) {
    touch(__DIR__.'/index.html');
}
if (!file_exists(APP_ROOT.'/.htaccess')) { // main htaccess
    updateHtaccessMain();
}
if (!file_exists(__DIR__.'/.htaccess')) {  // protected dirs htaccess
    updateHtaccessProtectedDirs();
}

if (!Cache::has('htaccess-latest')) {
    if (md5(file_get_contents(APP_ROOT.'/.htaccess')) !== md5(file_get_contents(__DIR__.'/htaccess-latest.txt'))) {
        updateHtaccessMain();
    }
}
if (!Cache::has('htaccess-deny-latest')) {
    if (md5(file_get_contents(__DIR__.'/.htaccess')) !== md5(file_get_contents(__DIR__.'/htaccess-latest-deny.txt'))) {
        updateHtaccessProtectedDirs();
    }
}

function updateHtaccessMain()
{
    $data = file_get_contents(__DIR__.'/htaccess-latest.txt');
    file_put_contents(APP_ROOT.'/.htaccess', $data);
    Cache::store('htaccess-latest', md5($data));
}

function updateHtaccessProtectedDirs()
{
    $data = file_get_contents(__DIR__.'/htaccess-latest-deny.txt');
    file_put_contents(__DIR__.'/.htaccess', $data);

    $protected_dirs = [
        '/cache',
        '/config',
        '/data/debug',
        '/data/servers',
        '/data/logins',
        '/data/logs',
    ];

    $protected_dirs_length = count($protected_dirs);

    for ($i = 0; $i < $protected_dirs_length; $i++) {
        copy(__DIR__.'/index.html', APP_ROOT.$protected_dirs[$i].'/index.html');
        copy(__DIR__.'/.htaccess', APP_ROOT.$protected_dirs[$i].'/.htaccess');
    }

    Cache::store('htaccess-deny-latest', md5($data));
}

// logging
Util::Log();

// localization
$locale = Util::getSetting('locale', 'en');
if (!file_exists(APP_ROOT.'/config/locales/'.$locale.'.php')) {
    $locale = 'en';
}

$lang = @include APP_ROOT.'/config/locales/'.$locale.'.php';
$lang_fallback = include APP_ROOT.'/config/locales/en.php';

// steam api key
if (Util::hasSetting('api_key')) {
    Steam::setKey(Util::getSetting('api_key'));
}

// random chance of clearing files
$clear_shit = mt_rand(1, 100) < 25 && strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'gmod') !== false;
$current_time = time();

// clear old webcaps
if ($clear_shit) {
    $webcaps = glob(APP_ROOT.DIRECTORY_SEPARATOR.'webcaps'.DIRECTORY_SEPARATOR.'*');

    if (count($webcaps) > 0) {
        foreach ($webcaps as $webcap) {
            if ($current_time - filemtime($webcap) > 120) {
                unlink($webcap);
            }
        }
    }
}

// clear old server query files
$old_server_files = glob(APP_ROOT.DIRECTORY_SEPARATOR.'data'.DIRECTORY_SEPARATOR.'servers'.DIRECTORY_SEPARATOR.'*.json');
$old_server_cnt = count($old_server_files);
if (count($old_server_files) > 0) {
    foreach ($old_server_files as $oldServerFile) {
        if ($current_time - filemtime($oldServerFile) > 180) {
            unlink($oldServerFile);
        }
    }
}

// steam login
$steamlogin_options = [
    'debug'   => DEBUG,
    'return'  => APP_URL_CURRENT,
    'method'  => 'api',
    'api_key' => $config['api_key'],
    'session' => [
        'enable' => true,
        'name'   => 'leyscreencap',
        'path'   => APP_URL_PATH,
        'home'   => APP_URL,
    ],
];

try {
    $steamlogin = new SteamLogin($steamlogin_options);
} catch (Exception $e) {
    echo '<div style="color:red;text-align:center;"><h1 style="text-transform:uppercase">Steam Login error</h1>';
    echo '<code style="color:#0ed60e;background:black;padding: 5px 3px;">'.$e->getMessage().'</code>';
    echo '<br><br><a href="'.APP_URL.'">Back to the login page</a>';
    echo '</div>';
    die();
}

// check for cookie
$cookie_exists = isset($_COOKIE['leyscreencap_cookie_login']);
if ($cookie_exists && !isset($_SESSION['SteamLogin'])) {
    $cookie_login_hash = $_COOKIE['leyscreencap_cookie_login'];
    $cookie_path = APP_ROOT.'/data/logins/'.$cookie_login_hash;
    $cookie_login_file = !file_exists($cookie_path);

    $cookie_login_user_agent = $_SERVER['HTTP_USER_AGENT'];

    $cookie_login_data = file_exists($cookie_path) ? json_decode(file_get_contents($cookie_path), true) : ['agent' => ''];

    $cookie_login_error = $cookie_login_data['agent'] != $cookie_login_user_agent;

    if ($cookie_login_error) {
        setcookie('leyscreencap_cookie_login', null, time() - 3600, empty(APP_URL_PATH) ? '/' : APP_URL_PATH, APP_DOMAIN, IS_HTTPS, true);
        session_destroy();
        redirect(APP_CURRENT_ROUTE, 1);
        die();
    }
    $cookie_login_steam_info = (array) Steam::getPlayerAlt($cookie_login_data['steamid']);
    $_SESSION['SteamLogin'] = array_merge(['steamid' => $cookie_login_data['steamid']], $cookie_login_steam_info);
}

// check if admin
if (isset($_SESSION['SteamLogin']['steamid'])) {
    $steamid = $_SESSION['SteamLogin']['steamid'];
    if (!in_array($steamid, $config['admins'])) {
        $steamlogin->logout();
    }

    if (isset($_COOKIE['remember_me'])) {
        $remember_me = $_COOKIE['remember_me'] == 1;
        setcookie('remember_me', 0, time() - 3600, empty(APP_URL_PATH) ? '/' : APP_URL_PATH, APP_DOMAIN, IS_HTTPS, true);

        if ($remember_me && !$cookie_exists) {
            $cookie_hash = Util::Hash();
            $cookie_hash_data = [
                'agent'   => $_SERVER['HTTP_USER_AGENT'],
                'steamid' => $steamid,
            ];
            file_put_contents(APP_ROOT.'/data/logins/'.$cookie_hash, json_encode($cookie_hash_data));
            setcookie('leyscreencap_cookie_login', $cookie_hash, time() + (86400 * 30), empty(APP_URL_PATH) ? '/' : APP_URL_PATH, APP_DOMAIN, IS_HTTPS, true);
        }
    }
}

// templating
$twig_data = [
    'app'           => [
        'route'         => ENABLE_FRIENDLY_URLS ? APP_URL_PATH : APP_URL_PATH.'/'.basename($_SERVER['SCRIPT_NAME']).'?',
        'debug'         => DEBUG,
        'current'       => APP_URL_CURRENT,
        'host'          => APP_HOST,
        'root_url'      => (IS_HTTPS ? 'https://' : 'http://').APP_DOMAIN.(APP_PORT !== 80 && APP_PORT !== 443 ? ':'.APP_PORT : ''),
        'path'          => APP_URL_PATH,
        'url'           => APP_URL,
        'site_name'     => $config['site_name'],
        'locale'        => $config['locale'],
        'current_route' => APP_CURRENT_ROUTE,
    ],
    'lang'          => $lang,
    'lang_fallback' => $lang_fallback,
    'session'       => isset($_SESSION['SteamLogin']['steamid']) ? $_SESSION['SteamLogin'] : null,
    'steam'         => [
        'login_url'    => $steamlogin->getLoginURL(),
        'button_small' => SteamLogin::button('small', true),
        'button_large' => SteamLogin::button('large', true),
    ],
    'cookies'       => $_COOKIE,
];

$theme_pages = APP_ROOT.'/themes/%s/pages';
$theme_partials = APP_ROOT.'/themes/%s/partials';

if (!file_exists(sprintf($theme_pages, $config['theme'])) || !file_exists(sprintf($theme_partials, $config['theme']))) {
    $config['theme'] = 'default';
}

$twig_loader = new \Twig\Loader\FilesystemLoader(APP_ROOT.'/themes/'.$config['theme']);
$twig_loader->addPath(APP_ROOT.'/themes/'.$config['theme'].'/partials', 'partials');

$twig_options = [
    'cache'       => ENABLE_CACHE ? APP_ROOT.'/cache/templates' : false,
    'debug'       => DEBUG,
    'auto_reload' => true,
];

$twig = new \Twig\Environment($twig_loader, $twig_options);
if (DEBUG) {
    $twig->addExtension(new \Twig\Extension\DebugExtension);
}
include_once __DIR__.'/twig_functions.php';

function view($template = 'login', array $data = [], $force = false)
{
    global $twig, $twig_data, $start;

    if (!is_array($data)) {
        $data = [$data];
    }

    $template_html = $twig->render(isset($_SESSION['SteamLogin']['steamid']) || $force ? 'pages/'.$template.'.twig' : 'pages/login.twig', $twig_data + (isset($_SESSION['SteamLogin']['steamid']) || $force ? $data : []));

    return '<!-- loaded in '.round(microtime(1) - $start, 3).' secs -->'."\n".$template_html;
}

// error page
function displayException(Exception $e)
{
    global $start;

    $log_messsage = 'Request: '.$_SERVER['REQUEST_METHOD'].' '.$_SERVER['REQUEST_URI'].'  IP Address: '.$_SERVER['REMOTE_ADDR']."\n\t\t".'MESSAGE: "'.$e->getMessage().'" - Code: '.$e->getCode()."\n\t\tSTACK TRACE:";

    $trace = $e->getTrace();
    foreach ($trace as $file => $info) {
        $log_messsage .= "\n\t\t\t[".(isset($file) ? $file : 'N/A').'] - '.$info['function'].' '.(isset($info['file']) ? $info['file'] : '<unknown file>').' on line '.(isset($info['line']) ? $info['line'] : '<unknown>');
    }

    Util::Log($log_messsage, 'exception', true);
    $message = $e->getMessage();

    if (Util::isAjax()) {
        header('Content-type: application/json');
        echo json_encode([
            'message' => $message,
            'trace'   => DEBUG ? $trace : null,
        ]);
    } else {
        echo view('error', [
            'error' => [
                'message' => $message,
                'trace'   => DEBUG ? $trace : null,
            ],
            'time'  => microtime(true) - $start,
        ], true);
    }
    die();
}

// routing
function routes()
{
    global $router;

    $router = new Phroute\Phroute\RouteCollector();
    include __DIR__.'/routes.php';

    return $router->getData();
}

$routes = routes();

$dispatcher = new Phroute\Phroute\Dispatcher($routes);

try {
    $response = $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], APP_CURRENT_ROUTE);

    if (is_bool($response) || is_array($response) || is_object($response)) {
        if (is_object($response)) {
            $response = (array) $response;
            $response['time'] = microtime(true) - $start;
        }

        header('Content-type: application/json');
        echo json_encode($response);
    } else {
        echo $response;
    }
} catch (Exception $e) {
    displayException($e);
}