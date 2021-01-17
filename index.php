<?php
require './pdos/DatabasePdo.php';
require './pdos/IndexPdo.php';
require './vendor/autoload.php';

require './pdos/UserPdo.php';
require './pdos/ListPdo.php';
require './pdos/OrderPdo.php';
// require './pdos/HomePdo.php';

use \Monolog\Logger as Logger;
use Monolog\Handler\StreamHandler;

date_default_timezone_set('Asia/Seoul');
ini_set('default_charset', 'utf8mb4');

//에러출력하게 하는 코드
//error_reporting(E_ALL); ini_set("display_errors", 1);

//Main Server API
$dispatcher = FastRoute\simpleDispatcher(function (FastRoute\RouteCollector $r) {
    /* ******************   Test   ****************** */
    $r->addRoute('GET', '/', ['IndexController', 'index']);
    // $r->addRoute('DELETE', '/test', ['IndexController', 'test']);
    // $r->addRoute('GET', '/test/{testNo}', ['IndexController', 'testDetail']);
    // $r->addRoute('GET', '/jwt', ['MainController', 'validateJwt']);
    // $r->addRoute('POST', '/jwt', ['MainController', 'createJwt']);

    //    $r->addRoute('GET', '/users', 'get_all_users_handler');
    //    // {id} must be a number (\d+)
    //    $r->addRoute('GET', '/user/{id:\d+}', 'get_user_handler');
    //    // The /{title} suffix is optional
    //    $r->addRoute('GET', '/articles/{id:\d+}[/{title}]', 'get_article_handler');

    $r->addRoute('GET', '/users/email', ['UserController', 'emailCheck']);
    $r->addRoute('POST', '/users', ['UserController', 'signUp']);
    $r->addRoute('POST', '/users/token', ['UserController', 'signIn']);
    $r->addRoute('GET', '/users', ['UserController', 'getUsers']);
    $r->addRoute('GET', '/users/token', ['UserController', 'getMyProfile']);

    $r->addRoute('POST', '/addresses', ['ListController', 'setAddress']);
    $r->addRoute('GET', '/category', ['ListController', 'getCategory']);
    $r->addRoute('GET', '/restaurants/all', ['ListController', 'getRestaurantsAll']);
    $r->addRoute('GET', '/restaurants', ['ListController', 'getRestaurantList']);

    $r->addRoute('GET', '/restaurants/{no:\d+}', ['OrderController', 'getRestaurant']);
    $r->addRoute('GET', '/restaurants/{no:\d+}/menu', ['OrderController', 'getMenuList']);
    $r->addRoute('GET', '/restaurants/{no:\d+}/menu/{menuNo:\d+}', ['OrderController', 'getMenuDetail']);
    $r->addRoute('GET', '/restaurants/{no:\d+}/menu/{menuNo:\d+}/review', ['OrderController', 'getMenuReview']);
    $r->addRoute('GET', '/restaurants/{no:\d+}/minimum/{sum:\d+}', ['OrderController', 'satisfyMinimum']);

    $r->addRoute('GET', '/orders/ways', ['OrderController', 'getOrderWays']);
    $r->addRoute('POST', '/orders', ['OrderController', 'order']);
    // $r->addRoute('PATCH', '/orders', ['OrderController', 'updateOrderState']);
    // $r->addRoute('GET', '/orders', ['OrderController', 'getOrderList']);
    // $r->addRoute('GET', '/orders/{no:\d+}[/re]', ['OrderController', 'getOrderDetail']);
    // $r->addRoute('DELETE', '/orders/{no:\d+}', ['OrderController', 'cancelOrder']);
    // $r->addRoute('GET', '/orders/{no:\d+}/re', ['OrderController', 'reOrder']);

});

// Fetch method and URI from somewhere
$httpMethod = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Strip query string (?foo=bar) and decode URI
if (false !== $pos = strpos($uri, '?')) {
    $uri = substr($uri, 0, $pos);
}
$uri = rawurldecode($uri);

$routeInfo = $dispatcher->dispatch($httpMethod, $uri);

// 로거 채널 생성
$accessLogs = new Logger('ACCESS_LOGS');
$errorLogs = new Logger('ERROR_LOGS');
// log/your.log 파일에 로그 생성. 로그 레벨은 Info
$accessLogs->pushHandler(new StreamHandler('logs/access.log', Logger::INFO));
$errorLogs->pushHandler(new StreamHandler('logs/errors.log', Logger::ERROR));
// add records to the log
//$log->addInfo('Info log');
// Debug 는 Info 레벨보다 낮으므로 아래 로그는 출력되지 않음
//$log->addDebug('Debug log');
//$log->addError('Error log');

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        // ... 404 Not Found
        echo "404 Not Found";
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        // ... 405 Method Not Allowed
        echo "405 Method Not Allowed";
        break;
    case FastRoute\Dispatcher::FOUND:
        $handler = $routeInfo[1];
        $vars = $routeInfo[2];

        switch ($routeInfo[1][0]) {
            case 'IndexController':
                $handler = $routeInfo[1][1];
                $vars = $routeInfo[2];
                require './controllers/IndexController.php';
                break;
            case 'MainController':
                $handler = $routeInfo[1][1];
                $vars = $routeInfo[2];
                require './controllers/MainController.php';
                break;
            case 'UserController':
                $handler = $routeInfo[1][1]; $vars = $routeInfo[2];
                require './controllers/UserController.php';
                break;
            case 'ListController':
                $handler = $routeInfo[1][1]; $vars = $routeInfo[2];
                require './controllers/ListController.php';
                break;
            case 'OrderController':
                $handler = $routeInfo[1][1]; $vars = $routeInfo[2];
                require './controllers/OrderController.php';
                break;
            // case 'HomeController':
            //     $handler = $routeInfo[1][1]; $vars = $routeInfo[2];
            //     require './controllers/HomeController.php';
            //     break;
        }

        break;
}
