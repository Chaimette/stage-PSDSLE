<?php

namespace App\Controller;


use Twig\Environment;
use Twig\Loader\FilesystemLoader;

abstract class AbstractController
{
    private static ?Environment $twig = null;
    protected ?\PDO $pdo = null;
    public function __construct()
    {
        require_once __DIR__ . '/../../config/env.php';

        if ($this->pdo === null) {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                $_ENV['DB_HOST'],
                $_ENV['DB_NAME'],
                $_ENV['DB_CHARSET']
            );

            $this->pdo = new \PDO($dsn, $_ENV['DB_USER'], $_ENV['DB_PASS'], [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ]);
        }
        // On init twig une seule fois
        if (self::$twig === null) {
            // Dossier des vues
            $viewsPath = \dirname(__DIR__) . '/View';

            // Dossier de cache (optionnel)
            // $cachePath = \dirname(__DIR__, 2) . '/var/cache/twig';

            // On vérifie que le dossier de cache existe, sinon il est créé
            // if (!is_dir($cachePath)) {
            //     @mkdir($cachePath, 0777, true);
            // }

            $loader = new FilesystemLoader($viewsPath);

            self::$twig = new Environment($loader, [
                // 'cache' => is_dir($cachePath) ? $cachePath : false,
                'cache' => false,
                'debug' => true,
                'auto_reload' => true,
                'strict_variables' => true,
            ]);
        }
        $prestaModel = new \App\Model\PrestaModel($this->pdo);
        $sections = $prestaModel->getAllSections();
        self::$twig->addGlobal('menu_sections', $sections);
    }


    protected function render(string $template, array $params = []): string
    {
        return self::$twig->render($template, $params);
    }

    protected function pdo(): \PDO
    {
        return $this->pdo;
    }

    protected function requireAdmin(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (empty($_SESSION['admin_id'])) {
            header('Location: /admin/login');
            exit;
        }
    }
    protected function csrfToken(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        return $_SESSION['csrf'] ??= bin2hex(random_bytes(16));
    }
    protected function checkCsrf(string $token): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
            http_response_code(400);
            exit('CSRF invalide');
        }
    }
}
