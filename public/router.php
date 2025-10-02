<?php
//Récupère et normalise l'URL demandée
$uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');

//Si c'est un vrai fichier dans /public, laisser le serveur le servir
$docroot = __DIR__;
$file    = realpath($docroot . $uri);

// sécurité: s'assurer que le fichier résolu reste dans /public
$insideDocroot = $file !== false && str_starts_with($file, $docroot . DIRECTORY_SEPARATOR);

if ($uri !== '/' && $insideDocroot && is_file($file)) {
    return false; //le serveur intégré renvoie directement le fichier
}

// i le fichier n'existe pas, on passe au front controller
require __DIR__ . '/index.php';
