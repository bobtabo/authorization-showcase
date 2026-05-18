<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('health', static function () {
    return \Config\Services::response()
        ->setStatusCode(200)
        ->setHeader('Content-Type', 'application/json')
        ->setBody('{"status":"ok"}');
});
$routes->get('clients', 'Proxy::clients');
$routes->get('gate/issue', 'Proxy::gateIssue');
$routes->get('gate/client/(:any)/verify', 'Proxy::gateVerify/$1');
