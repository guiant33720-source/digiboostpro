<?php
/**
 * Endpoint AJAX sécurisé - Compteur de clients
 * Version: 2.0 Secure
 */

// Headers JSON
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../config.php';

// Pas besoin d'auth pour ce endpoint public
// Mais on vérifie quand même la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Méthode non autorisée', 405);
}

try {
    $pdo = getPdo();
    
    // Requête préparée (même si pas de paramètre utilisateur)
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM clients WHERE status = 'actif'");
    $result = $stmt->fetch();
    
    jsonSuccess([
        'count' => (int)$result['total']
    ]);
    
} catch (PDOException $e) {
    error_log("Count clients error: " . $e->getMessage());
    jsonError('Erreur lors de la récupération des données', 500);
}