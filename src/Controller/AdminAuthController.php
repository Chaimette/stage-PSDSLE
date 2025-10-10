<?php

namespace App\Controller;

class AdminAuthController extends AbstractController
{
    public function login(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (!empty($_SESSION['admin_id'])) {
            if ($this->touchSession(3600, 86400)) {
                header('Location: /admin');
                exit;
            }
        }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->checkCsrf($_POST['csrf'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $pass  = $_POST['password'] ?? '';

            if (empty($email) || empty($pass)) {
                $_SESSION['flash_error'] = "Veuillez fournir vos identifiants.";
                header('Location: /admin/login');
                exit;
            }

            $stmt = $this->pdo->prepare("SELECT id, password_hash FROM admins WHERE email=? LIMIT 1");
            $stmt->execute([$email]);
            $admin = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($admin && password_verify($pass, $admin['password_hash'])) {
                if (session_status() !== PHP_SESSION_ACTIVE) session_start();
                session_regenerate_id(true); // anti fixation - on change l'ID de session après login
                $_SESSION['admin_id'] = (int)$admin['id'];
                $_SESSION['_created'] = time();
                $_SESSION['_last']    = time();

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
            'error' => $error,
            'csrf' => $this->csrfToken(),
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
