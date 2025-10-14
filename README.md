# Prendre Soin de Soi – MVC (PHP/JS)

## Objectif
Refonte du Google Site en application MVC PHP/JS pour démonstration (démo interne/portfolio). Mise en prod optionnelle.

## Pile technique
- PHP 8.4, Composer
- Router.php
- Vues en PHP/HTML5/Twig, CSS3 natif (+ utilitaires), JS Vanilla
- MySQL (PDO)
- PHPMailer pour emails (pas encore mis en place)
- Dotenv pour config

## Démarrer A REFAIRE
1. `cp .env.example .env` et renseigner DB_USER/DB_PASS/MAIL_*
2. `composer install` (si nécessaire)
3. Créer la base : `mysql -u root -p < database.sql`
4. Lancer en local : `php -S localhost:8000 -t public public/router.php`

## Structure
```
/config
/public
  /assets
    /images
    /css
    /js
  index.php
  router.php
/src
  /Controller
  /Model
  /View
/tests
database.sql
```

## Sécurité de base
- CSRF tokens sur formulaires
- Validation/échappement systématique 
- Headers sécurité

## Licences & crédits
Images et contenus fournis par Sabrina Maraldo, usage interne/stage.
