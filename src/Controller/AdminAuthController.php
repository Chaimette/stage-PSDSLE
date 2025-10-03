<?php

namespace App\Controller;

class AdminAuthController extends AbstractController
{
    public function login(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $email = trim($_POST['email'] ?? '');
            $pass  = $_POST['password'] ?? '';
            $pdo   = $this->pdo();

            $stmt = $pdo->prepare("SELECT id, password_hash FROM admins WHERE email=? LIMIT 1");
            $stmt->execute([$email]);
            $admin = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($admin && password_verify($pass, $admin['password_hash'])) {
                $_SESSION['admin_id'] = (int)$admin['id'];
                header('Location: /admin');
                exit;
            }
            $_SESSION['flash_error'] = "Identifiants invalides.";
            header('Location: /admin/login');
            exit;
        }

        $error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);
        return $this->render('admin/login.html.twig', [
            'title' => 'Connexion Admin',
            'error' => $error
        ]);
    }

    public function logout(): void
    {
        // On exige POST
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            exit;
        }

        $this->checkCsrf($_POST['csrf'] ?? '');

        if (session_status() !== PHP_SESSION_ACTIVE) {
            // nécessité à déterminer
            session_set_cookie_params([
                'httponly' => true,
                'secure'   => !empty($_SERVER['HTTPS']),
                'samesite' => 'Lax',
            ]);
            session_start();
        }


        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();

        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $_SESSION['flash_success'] = "Vous avez été déconnecté(e).";

        header('Location: /');
        exit;
    }
}
