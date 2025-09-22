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
                header('Location: /admin'); // TODO: crée la page /admin
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
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        session_destroy();
        header('Location: /');
        exit;
    }
}
