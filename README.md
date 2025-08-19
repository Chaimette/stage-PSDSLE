# Prendre Soin de Soi – MVC (PHP/JS)

## Objectif
Refonte du Google Site en application MVC PHP/JS pour démonstration (démo interne/portfolio). Mise en prod optionnelle.

## Pile technique
- PHP 8.x, Composer
- Router minimal (front controller)
- Vues en PHP/HTML, CSS natif (+ utilitaires), JS Vanilla
- MySQL/MariaDB (PDO)
- PHPMailer pour emails
- Dotenv pour config

## Démarrer
1. `cp .env.example .env` et renseigner DB_USER/DB_PASS/MAIL_*
2. `composer install` (si nécessaire)
3. Créer la base : `mysql -u root -p < database/schema.sql`
4. Lancer en local : `php -S localhost:8000 -t public`

## Structure
```
/app
  /Controllers
  /Models
  /Views
/config
/database
/public
  index.php
/resources
  /images
  /css
  /js
/tests
/docs
```

## Sécurité de base
- CSRF tokens sur formulaires
- Validation/échappement systématique
- Headers sécurité (CSP, X-Frame-Options, etc.)

## Licences & crédits
Images et contenus fournis par Sabrina Maraldo, usage interne/stage.
