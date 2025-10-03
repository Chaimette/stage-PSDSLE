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
        return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    }
    protected function checkCsrf(string $token): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            http_response_code(405);
            exit;
        }
        if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) {
            http_response_code(400);
            exit('CSRF invalide');
        }
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }

    ///HELPERS
    protected function sanitizeSlug(?string $s): string
    {
        $s = strtolower(trim((string)$s));
        $s = preg_replace('~[^a-z0-9-]+~', '-', $s);
        $s = trim($s, '-');
        return mb_substr($s, 0, 150);
    }
    
    protected function posInt($v): int
    {
        $n = (int)($v ?? 0);
        return $n < 0 ? 0 : $n;
    }
    protected function price($v): float
    {
        $s = is_string($v) ? str_replace(',', '.', $v) : $v;
        $f = (float)($s ?? 0);
        return $f < 0 ? 0.0 : $f;
    }
    protected function clip(?string $s, int $max): string
    {
        $s = trim((string)$s);
        return mb_strlen($s) > $max ? mb_substr($s, 0, $max) : $s;
    }
    protected function ensureUniqueSlug(string $slug, int $excludeId = 0): string {
    $base = $slug !== '' ? $slug : 'item';
    $try  = $base; $i = 2;

    while (true) {
        $sql  = 'SELECT COUNT(*) FROM sections WHERE slug = ?' . ($excludeId ? ' AND id <> ?' : '');
        $stmt = $this->pdo->prepare($sql);
        $excludeId ? $stmt->execute([$try, $excludeId]) : $stmt->execute([$try]);
        $exists = (int)$stmt->fetchColumn() > 0;
        if (!$exists) return $try;
        $try = $base . '-' . $i++;
    }
}

}
