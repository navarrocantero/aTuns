<?php
/**
 * Created by PhpStorm.
 * User: navarrocantero
 * Date: 14/11/2017
 * Time: 00:02
 */
require_once '../vendor/autoload.php';
include_once '../helpers.php';
session_start();


use Phroute\Phroute\RouteCollector;
use Illuminate\Database\Capsule\Manager as Capsule;

$baseDir = str_replace(basename($_SERVER['SCRIPT_NAME']), '', $_SERVER['SCRIPT_NAME']);
$baseUrl = $_SERVER['HTTP_HOST'] . $baseDir;
$protocol = (strpos($baseUrl, 'heroku')) ? "https://" : "http://";
$baseUrl = $protocol.$baseUrl;
define('BASE_URL', $baseUrl);
if (file_exists(__DIR__ . '/../.env')) {
    $dotEnv = new Dotenv\Dotenv(__DIR__ . '/..');
    $dotEnv->load();
}

/**
 * ELOQUENT
 */
$capsule = new Capsule;
$capsule->addConnection([
    'driver' => 'mysql',
    'host' => getenv('DB_HOST'),
    'database' => getenv('DB_NAME'),
    'username' => getenv('DB_USER'),
    'password' => getenv('DB_PASS'),
    'charset' => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix' => ''
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();

/**
 * route is empty? route = /
 */
$route = $_GET['route'] ?? "/";
$router = new RouteCollector();


// Filter to verify the auth's users
$router->filter('auth', function(){
    if(!isset($_SESSION['userId'])){
        header('Location: '. BASE_URL);
        return false;
    }
});
$router->group(['before' => 'auth'], function ($router){

    $router->get('/album/add', ['\App\Controllers\AlbumController', 'getAdd']);
    $router->post('/album/add', ['\App\Controllers\AlbumController', 'postAdd']);
    $router->get('/album/edit/{name}', ['\App\Controllers\AlbumController', 'getEdit']);
    $router->put('/album/edit/{name}', ['\App\Controllers\AlbumController', 'putEdit']);
    $router->delete('/album', ['\App\Controllers\AlbumController', 'deleteIndex']);

    $router->get('/album/{name}/add', ['\App\Controllers\TrackController', 'getAdd']);
    $router->post('/album/{name}/add', ['\App\Controllers\TrackController', 'postAdd']);
    $router->delete('/album/{name}', ['\App\Controllers\TrackController', 'deleteIndex']);
    $router->get('/album/{name}/track/{trackName}', ['\App\Controllers\TrackController', 'getTrack']);
    $router->put('/album/{name}/track/{trackName}', ['\App\Controllers\TrackController', 'putTrack']);


    $router->get('/logout', ['\App\Controllers\HomeController', 'getLogout']);
    $router->get('/profile', ['\App\Controllers\HomeController', 'getProfile']);
    $router->post('/profile', ['\App\Controllers\HomeController', 'postProfile']);
});

// Filter to verify the no auth's users
$router->filter('noAuth', function(){
    if( isset($_SESSION['userId'])){
        header('Location: '. BASE_URL);
        return false;
    }
});

$router->group(['before' => 'noAuth'], function ($router){
    $router->get('/login', ['\App\Controllers\HomeController', 'getLogin']);
    $router->post('/login', ['\App\Controllers\HomeController', 'postLogin']);
    $router->get('/register', ['\App\Controllers\HomeController', 'getRegister']);
    $router->post('/register', ['\App\Controllers\HomeController', 'postRegister']);
});

// Routes without filters
$router->get('/', ['\App\Controllers\HomeController', 'getIndex']);
$router->get('/album/{name}', ['\App\Controllers\AlbumController', 'getIndex']);
$router->get('/api/{name}', ['\App\Controllers\ApiController', 'getAlbum']);
$router->get('/api', ['\App\Controllers\ApiController', 'getAlbums']);

$dispatcher = new Phroute\Phroute\Dispatcher($router->getData());

$method = $_REQUEST['_method'] ?? $_SERVER['REQUEST_METHOD'];

$response = $dispatcher->dispatch($method, $route);

echo $response;