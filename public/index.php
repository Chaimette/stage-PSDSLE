<?php
header('Content-Type: text/html; charset=utf-8');
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../vendor/autoload.php';

// On charge les routes
$routes = require __DIR__ . '/../config/routes.php';

// On normalise l'URI
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
if ($uri === '' || $uri === false) {
    $uri = '/';
}
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Appel du controleur + action
$dispatch = function (string $controllerFqcn, string $action, array $args = []) {
    if (!class_exists($controllerFqcn)) {
        http_response_code(500);
        echo "Contrôleur introuvable: {$controllerFqcn}";
        exit;
    }
    $controller = new $controllerFqcn();
    // Appelle l'action avec ou sans argument
    echo match (count($args)) {
        0 => $controller->$action(),
        1 => $controller->$action($args[0]),
        2 => $controller->$action($args[0], $args[1]),
        default => $controller->$action(...$args),
    };
    exit;
};

// Matching de la route
if (isset($routes[$uri])) {
    $controllerFqcn = "\\App\\Controller\\{$routes[$uri]['controller']}";
    $action = $routes[$uri]['action'];
    $dispatch($controllerFqcn, $action);
}

// Routes avec paramètres
foreach ($routes as $pattern => $info) {
    if (strpos($pattern, '{') === false) continue;

    // remplace {slug} par un named group
    $regex = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', function ($m) {
        $name = $m[1];
        // slug/catch-all segment sans slash
        return '(?P<' . $name . '>[^/]+)';
    }, $pattern);

    $regex = '#^' . $regex . '$#';

    if (preg_match($regex, $uri, $m)) {
        $params = [];
        foreach ($m as $k => $v) {
            if (is_string($k)) $params[$k] = $v;
        }
        $controllerFqcn = "\\App\\Controller\\{$info['controller']}";
        $action = $info['action'];
        // passe les params dans l’ordre de déclaration (ici on a {slug})
        $dispatch($controllerFqcn, $action, array_values($params));
    }
}

// Routes spécifiques pour AdminAuthController (GET et POST séparés)
switch (true) {
    case ($uri === '/admin/login' && $method === 'GET'):
        $dispatch("\\App\\Controller\\AdminAuthController", 'loginForm');
        break;
    case ($uri === '/admin/login' && $method === 'POST'):
        $dispatch("\\App\\Controller\\AdminAuthController", 'login');
        break;
    case ($uri === '/admin/logout' && $method === 'POST'):
        $dispatch("\\App\\Controller\\AdminAuthController", 'logout');
        break;
}

// 404
http_response_code(404);
echo "Page non trouvée";
