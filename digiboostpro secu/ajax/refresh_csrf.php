<?php
/**
 * Endpoint pour rafraîchir le token CSRF
 * Version: 2.0 Secure
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';

// Vérifier que l'utilisateur est connecté
if (!isLogged()) {
    jsonError('Non authentifié', 401);
}

// Générer un nouveau token
$token = generateCsrfToken();

jsonSuccess(['token' => $token]);