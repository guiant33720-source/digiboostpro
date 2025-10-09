<?php
/**
 * DigiBoostPro - Configuration sécurisée
 * Version: 2.0 Secure
 */

// ==================== ERROR REPORTING ====================

// Mode développement/production
define('IS_DEV_MODE', true); // ⚠️ Mettre FALSE en production !

if (IS_DEV_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', __DIR__ . '/logs/php_errors.log');
}

// ==================== DATABASE CONFIGURATION ====================

// Utiliser variables d'environnement en production
// Exemple: DB_HOST=getenv('DB_HOST') ?: 'localhost';
define('DB_HOST', 'localhost');
define('DB_NAME', 'digiboostpro');
define('DB_USER', 'root');
define('DB_PASS', ''); // ⚠️ Changer en production !
define('DB_CHARSET', 'utf8mb4');

// ==================== SECURITY SETTINGS ====================

define('SESSION_LIFETIME', 3600); // 1 heure
define('CSRF_TOKEN_NAME', 'csrf_token');
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_TIMEOUT', 900); // 15 minutes

// ==================== SESSION HARDENING ====================

// Configuration des cookies de session sécurisés
if (session_status() === PHP_SESSION_NONE) {
    // Paramètres de cookie sécurisés
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path' => '/',
        'domain' => '', // Laisser vide pour auto-detect
        'secure' => !IS_DEV_MODE, // TRUE en HTTPS (production)
        'httponly' => true, // Protection XSS
        'samesite' => 'Strict' // Protection CSRF
    ]);
    
    // Nom de session personnalisé
    session_name('DIGIBOOST_SESSID');
    
    // Démarrer la session
    session_start();
    
    // Régénérer l'ID de session périodiquement (toutes les 30 minutes)
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 1800) {
        session_regenerate_id(true);
        $_SESSION['last_regeneration'] = time();
    }
}

// ==================== TIMEZONE ====================

date_default_timezone_set('Europe/Paris');

// ==================== AUTOLOAD FUNCTIONS ====================

require_once __DIR__ . '/includes/functions.php';

// ==================== URL BASE ====================

// Définir l'URL de base (automatique)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$scriptPath = dirname($_SERVER['SCRIPT_NAME']);
$basePath = preg_replace('#/(admin|client|conseiller|ajax|api|includes).*$#', '', $scriptPath);

define('BASE_URL', $protocol . $host . $basePath);

// ==================== HEADERS SÉCURITÉ ====================

// Headers de sécurité (si pas déjà définis dans .htaccess)
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Content Security Policy (à adapter selon vos besoins)
    if (!IS_DEV_MODE) {
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' data:;");
    }
}

// ==================== CONSTANTS ====================

define('ROOT_PATH', __DIR__);
define('UPLOAD_PATH', ROOT_PATH . '/uploads');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 MB

// ==================== LEGACY COMPATIBILITY ====================

// Créer $pdo pour compatibilité avec ancien code
try {
    $pdo = getPdo();
} catch (PDOException $e) {
    if (IS_DEV_MODE) {
        die("Erreur de connexion à la base de données : " . $e->getMessage());
    } else {
        die("Erreur de connexion à la base de données. Veuillez contacter l'administrateur.");
    }
}