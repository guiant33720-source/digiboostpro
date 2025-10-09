<?php
/**
 * DigiBoostPro - Fonctions utilitaires sécurisées
 * Version: 2.0 Secure
 */

// ==================== CSRF PROTECTION ====================

/**
 * Génère un token CSRF et le stocke en session
 * @return string Le token généré
 */
function generateCsrfToken(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $token;
    $_SESSION['csrf_token_time'] = time();
    
    return $token;
}

/**
 * Vérifie la validité du token CSRF
 * @param string|null $token Le token à vérifier
 * @return bool True si valide, false sinon
 */
function verifyCsrfToken(?string $token): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Vérifier que le token existe
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        return false;
    }
    
    // Vérifier l'expiration (1 heure)
    if (time() - $_SESSION['csrf_token_time'] > 3600) {
        unset($_SESSION['csrf_token'], $_SESSION['csrf_token_time']);
        return false;
    }
    
    // Vérifier le token avec hash_equals (protection timing attack)
    return $token !== null && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Renvoie un champ hidden avec le token CSRF
 * @return string HTML du champ hidden
 */
function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . generateCsrfToken() . '">';
}

// ==================== DATABASE HELPERS ====================

/**
 * Retourne l'instance PDO (singleton)
 * @return PDO
 * @throws PDOException
 */
function getPdo(): PDO {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = sprintf(
                "mysql:host=%s;dbname=%s;charset=%s",
                DB_HOST,
                DB_NAME,
                DB_CHARSET
            );
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
        } catch (PDOException $e) {
            // Logger l'erreur sans exposer les détails
            error_log("Database connection error: " . $e->getMessage());
            
            // Message générique en production
            if (!IS_DEV_MODE) {
                throw new PDOException("Database connection failed");
            }
            throw $e;
        }
    }
    
    return $pdo;
}

// ==================== OUTPUT ESCAPING ====================

/**
 * Échappe et nettoie les données de sortie (alias court de htmlspecialchars)
 * @param string|null $data
 * @return string
 */
function e(?string $data): string {
    if ($data === null) {
        return '';
    }
    return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Alias de e() pour compatibilité
 * @param string|null $data
 * @return string
 */
function clean(?string $data): string {
    return e($data);
}

// ==================== AUTHENTICATION & AUTHORIZATION ====================

/**
 * Vérifie si l'utilisateur est connecté
 * @return bool
 */
function isLogged(): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Vérifier les variables de session essentielles
    if (!isset($_SESSION['user_id'], $_SESSION['user_role'], $_SESSION['user_auth_token'])) {
        return false;
    }
    
    // Vérifier l'expiration de session (timeout 1 heure)
    if (isset($_SESSION['last_activity'])) {
        $timeout = SESSION_LIFETIME ?? 3600;
        if (time() - $_SESSION['last_activity'] > $timeout) {
            session_unset();
            session_destroy();
            return false;
        }
    }
    
    // Mettre à jour last_activity
    $_SESSION['last_activity'] = time();
    
    return true;
}

/**
 * Vérifie si l'utilisateur a le(s) rôle(s) requis
 * @param string|array $roles Rôle(s) autorisé(s)
 * @return bool
 */
function isRole($roles): bool {
    if (!isLogged()) {
        return false;
    }
    
    $userRole = $_SESSION['user_role'] ?? null;
    
    if (is_array($roles)) {
        return in_array($userRole, $roles, true);
    }
    
    return $userRole === $roles;
}

/**
 * Redirige vers login si non authentifié
 * @param string $message Message d'erreur optionnel
 */
function requireLogin(string $message = ''): void {
    if (!isLogged()) {
        if ($message) {
            $_SESSION['error_message'] = $message;
        }
        redirect('login.php');
    }
}

/**
 * Vérifie le rôle et redirige si non autorisé
 * @param string|array $roles Rôle(s) requis
 */
function requireRole($roles): void {
    requireLogin();
    
    if (!isRole($roles)) {
        http_response_code(403);
        die('Accès refusé');
    }
}

// ==================== URL & ROUTING ====================

/**
 * Obtient l'URL de base du projet
 * @return string
 */
function getBaseUrl(): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
    
    // Retirer les sous-dossiers admin/client/conseiller/ajax/api
    $basePath = preg_replace('#/(admin|client|conseiller|ajax|api|includes).*$#', '', $scriptPath);
    
    return $protocol . $host . $basePath;
}

/**
 * Génère une URL complète vers un fichier
 * @param string $path
 * @return string
 */
function url(string $path = ''): string {
    return getBaseUrl() . '/' . ltrim($path, '/');
}

/**
 * Redirige vers une URL du projet
 * @param string $path
 */
function redirect(string $path): void {
    $redirectUrl = url($path);
    
    // S'assurer qu'aucun contenu n'a été envoyé
    if (headers_sent($file, $line)) {
        error_log("Headers already sent in $file line $line. Cannot redirect to $redirectUrl");
        die("Cannot redirect, headers already sent.");
    }
    
    header('Location: ' . $redirectUrl);
    exit;
}

// ==================== SECURITY HELPERS ====================

/**
 * Protection brute force - Vérifie le nombre de tentatives par IP
 * @param string $identifier Identifiant (IP, email, etc.)
 * @param int $maxAttempts Nombre max de tentatives
 * @param int $timeWindow Fenêtre de temps en secondes
 * @return bool True si autorisé, False si bloqué
 */
function checkRateLimit(string $identifier, int $maxAttempts = 5, int $timeWindow = 900): bool {
    try {
        $pdo = getPdo();
        
        // Créer la table si nécessaire
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS rate_limits (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                identifier VARCHAR(255) NOT NULL,
                attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_identifier (identifier),
                INDEX idx_time (attempt_time)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Nettoyer les anciennes entrées
        $stmt = $pdo->prepare("DELETE FROM rate_limits WHERE attempt_time < DATE_SUB(NOW(), INTERVAL ? SECOND)");
        $stmt->execute([$timeWindow]);
        
        // Compter les tentatives récentes
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as attempts 
            FROM rate_limits 
            WHERE identifier = ? 
            AND attempt_time > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute([$identifier, $timeWindow]);
        $result = $stmt->fetch();
        
        if ($result['attempts'] >= $maxAttempts) {
            return false; // Bloqué
        }
        
        // Enregistrer la tentative
        $stmt = $pdo->prepare("INSERT INTO rate_limits (identifier) VALUES (?)");
        $stmt->execute([$identifier]);
        
        return true; // Autorisé
        
    } catch (PDOException $e) {
        error_log("Rate limit error: " . $e->getMessage());
        return true; // En cas d'erreur, on laisse passer
    }
}

/**
 * Génère un token d'authentification aléatoire
 * @param int $length
 * @return string
 */
function generateAuthToken(int $length = 32): string {
    return bin2hex(random_bytes($length));
}

/**
 * Valide un email
 * @param string $email
 * @return bool
 */
function isValidEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Nettoie un numéro de téléphone
 * @param string $phone
 * @return string
 */
function sanitizePhone(string $phone): string {
    return preg_replace('/[^0-9+]/', '', $phone);
}

/**
 * Log une activité utilisateur
 * @param int $userId
 * @param string $action
 * @param string $details
 */
function logActivity(int $userId, string $action, string $details = ''): void {
    try {
        $pdo = getPdo();
        
        // Créer la table si nécessaire
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS activity_logs (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                action VARCHAR(100) NOT NULL,
                details TEXT,
                ip_address VARCHAR(45),
                user_agent VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_user (user_id),
                INDEX idx_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId,
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
    } catch (PDOException $e) {
        error_log("Log activity error: " . $e->getMessage());
    }
}

// ==================== JSON HELPERS ====================

/**
 * Envoie une réponse JSON
 * @param mixed $data
 * @param int $statusCode
 */
function jsonResponse($data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Envoie une erreur JSON
 * @param string $message
 * @param int $statusCode
 */
function jsonError(string $message, int $statusCode = 400): void {
    jsonResponse([
        'success' => false,
        'error' => $message
    ], $statusCode);
}

/**
 * Envoie un succès JSON
 * @param mixed $data
 * @param string $message
 */
function jsonSuccess($data = [], string $message = ''): void {
    $response = [
        'success' => true,
        'data' => $data
    ];
    
    if ($message) {
        $response['message'] = $message;
    }
    
    jsonResponse($response);
}
function isLoggedIn(): bool {
    return isLogged();
}
?>