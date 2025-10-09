<?php
/**
 * Téléchargement sécurisé de fichiers avec vérification de permissions
 * Version: 2.0 Secure
 */
require_once 'config.php';

// === VÉRIFICATION AUTHENTIFICATION ===
requireLogin();

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

// Récupérer le fichier demandé
$fileId = isset($_GET['file']) ? (int)$_GET['file'] : 0;

if ($fileId <= 0) {
    http_response_code(400);
    die('Fichier invalide');
}

try {
    $pdo = getPdo();
    
    // Créer la table files si elle
    // Créer la table files si elle n'existe pas
    getPodo()->exec("
        CREATE TABLE IF NOT EXISTS files (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            filename VARCHAR(255) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_size INT UNSIGNED NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            uploaded_by INT UNSIGNED NOT NULL,
            client_id INT UNSIGNED NULL,
            is_public BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
            INDEX idx_client (client_id),
            INDEX idx_uploaded (uploaded_by)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    
    // Récupérer les informations du fichier
    $stmt = getPodo()->prepare("
        SELECT f.*, u.id as uploader_id, c.id as owner_client_id
        FROM files f
        LEFT JOIN users u ON f.uploaded_by = u.id
        LEFT JOIN clients c ON f.client_id = c.id
        WHERE f.id = ?
    ");
    $stmt->execute([$fileId]);
    $file = $stmt->fetch();
    
    if (!$file) {
        http_response_code(404);
        die('Fichier non trouvé');
    }
    
    // === VÉRIFICATION DES PERMISSIONS ===
    $hasAccess = false;
    
    // Admin : accès à tous les fichiers
    if ($userRole === 'admin') {
        $hasAccess = true;
    }
    // Fichiers publics : accessible à tous les connectés
    elseif ($file['is_public']) {
        $hasAccess = true;
    }
    // Propriétaire du fichier
    elseif ($file['uploaded_by'] === $userId) {
        $hasAccess = true;
    }
    // Client : accès uniquement à ses propres fichiers
    elseif ($userRole === 'client') {
        // Vérifier que l'utilisateur est bien le client
        $stmtClientCheck = getPdo()->prepare("SELECT id FROM clients WHERE email = ? AND id = ?");
        $stmtClientCheck->execute([$_SESSION['user_email'], $file['client_id']]);
        if ($stmtClientCheck->fetch()) {
            $hasAccess = true;
        }
    }
    // Conseiller : accès aux fichiers de ses clients
    elseif ($userRole === 'conseiller') {
        if ($file['client_id']) {
            $stmtConseiller = getPdo()->prepare("SELECT id FROM clients WHERE id = ? AND conseiller_id = ?");
            $stmtConseiller->execute([$file['client_id'], $userId]);
            if ($stmtConseiller->fetch()) {
                $hasAccess = true;
            }
        }
    }
    
    // Refuser l'accès si pas de permission
    if (!$hasAccess) {
        http_response_code(403);
        logActivity($userId, 'file_access_denied', "Tentative accès fichier ID: $fileId");
        die('Accès refusé');
    }
    
    // === VÉRIFIER QUE LE FICHIER EXISTE PHYSIQUEMENT ===
    $filePath = ROOT_PATH . '/' . $file['file_path'];
    
    if (!file_exists($filePath) || !is_readable($filePath)) {
        http_response_code(404);
        error_log("File not found or not readable: $filePath");
        die('Fichier introuvable sur le serveur');
    }
    
    // === LOGGER LE TÉLÉCHARGEMENT ===
    logActivity($userId, 'file_downloaded', "Fichier téléchargé: " . $file['original_name'] . " (ID: $fileId)");
    
    // === PRÉPARER LE TÉLÉCHARGEMENT ===
    
    // Nettoyer le buffer de sortie
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Headers sécurisés pour le téléchargement
    header('Content-Type: ' . $file['mime_type']);
    header('Content-Length: ' . $file['file_size']);
    header('Content-Disposition: attachment; filename="' . basename($file['original_name']) . '"');
    header('Content-Transfer-Encoding: binary');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Expires: 0');
    header('X-Content-Type-Options: nosniff');
    
    // Empêcher l'exécution du script
    header('X-Content-Type-Options: nosniff');
    
    // Lecture et envoi du fichier par chunks (pour les gros fichiers)
    $handle = fopen($filePath, 'rb');
    if ($handle === false) {
        http_response_code(500);
        die('Erreur lors de la lecture du fichier');
    }
    
    while (!feof($handle)) {
        echo fread($handle, 8192); // 8KB chunks
        flush();
    }
    
    fclose($handle);
    exit;
    
} catch (PDOException $e) {
    error_log("Download error: " . $e->getMessage());
    http_response_code(500);
    die('Erreur lors du téléchargement');
}