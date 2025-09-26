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
'/admin/builder/save'=> ['controller' => 'PrestaController', 'action' => 'adminBuilderSave'],
// Sections
'/admin/sections'           => ['controller' => 'PrestaController', 'action' => 'adminSectionsList'],
'/admin/sections/create'    => ['controller' => 'PrestaController', 'action' => 'adminSectionCreate'],
'/admin/sections/edit'      => ['controller' => 'PrestaController', 'action' => 'adminSectionEdit'],    // ?id=
'/admin/sections/delete'    => ['controller' => 'PrestaController', 'action' => 'adminSectionDelete'],

// Prestations
'/admin/prestations'        => ['controller' => 'PrestaController', 'action' => 'adminPrestationsList'], // ?section_id=
'/admin/prestations/create' => ['controller' => 'PrestaController', 'action' => 'adminPrestationCreate'],
'/admin/prestations/edit'   => ['controller' => 'PrestaController', 'action' => 'adminPrestationEdit'],  // ?id=
'/admin/prestations/delete' => ['controller' => 'PrestaController', 'action' => 'adminPrestationDelete'],

// Tarifs
'/admin/tarifs'             => ['controller' => 'PrestaController', 'action' => 'adminTarifsList'],       // ?prestation_id=
'/admin/tarifs/create'      => ['controller' => 'PrestaController', 'action' => 'adminTarifCreate'],      // ?prestation_id=
'/admin/tarifs/edit'        => ['controller' => 'PrestaController', 'action' => 'adminTarifEdit'],        // ?id=
'/admin/tarifs/delete'      => ['controller' => 'PrestaController', 'action' => 'adminTarifDelete'], 

];
