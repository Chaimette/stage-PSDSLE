<?php

return [
    '/' => [
        'controller' => 'HomeController',
        'action'     => 'index',
    ],
    '/contact' => [
        'controller' => 'HomeController',
        'action'     => 'contact',
    ],
    '/rdv' => [
        'controller' => 'HomeController',
        'action'     => 'rdv',
    ],
    '/prestations' => [
        'controller' => 'PrestaController',
        'action'     => 'index',
    ],
    '/prestations/{slug}' => [
        'controller' => 'PrestaController',
        'action'     => 'show',
    ],

    // Admin
    '/admin' => [
        'controller' => 'AdminDashboardController', 
        'action' => 'index'
    ],
    '/logout' => [
        'controller' => 'AdminAuthController', 
        'action' => 'logout'
    ],
    '/admin/login' => [
        'controller' => 'AdminAuthController',
        'action'     => 'login',
    ],

];
