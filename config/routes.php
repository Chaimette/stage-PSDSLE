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
    '/admin/login' => [
        'controller' => 'AdminAuthController',
        'action'     => 'login',
    ],
    // Admin
    '/admin'                    => ['controller' => 'AdminDashboardController', 'action' => 'index'],
   

];
