<?php
/**
 * Fichier de configuration DigiboostPro
 * Configuration de la base de données et paramètres globaux
 */

// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'digiboostpro');
define('DB_USER', 'root');
define('DB_PASS', ''); // Modifier selon votre configuration

// Configuration du site
define('SITE_NAME', 'DigiBoostPro');
define('SITE_URL', 'http://localhost'); // Modifier selon votre environnement
define('CONTACT_EMAIL', 'support@digiboostpro.fr');

// Démarrage de la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Connexion à la base de données avec gestion d'erreur
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// Fonction pour vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

// Fonction pour vérifier le rôle
function hasRole($role) {
    return isLoggedIn() && $_SESSION['role'] === $role;
}

// Fonction pour rediriger selon le rôle
function redirectToDashboard() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
    
    switch ($_SESSION['role']) {
        case 'admin':
            header('Location: /admin/dashboard.php');
            break;
        case 'conseiller':
            header('Location: /conseiller/dashboard.php');
            break;
        case 'client':
            header('Location: /client/dashboard.php');
            break;
        default:
            header('Location: /index.php');
    }
    exit;
}

// Fonction de protection des pages
function requireLogin($role = null) {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
    
    if ($role !== null && !hasRole($role)) {
        header('Location: /index.php');
        exit;
    }
}

// Fonction pour échapper les données HTML
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Fuseau horaire
date_default_timezone_set('Europe/Paris');
?>