<?php
/**
 * Upload sécurisé de fichiers
 * Version: 2.0 Secure
 */
require_once 'config.php';

// === VÉRIFICATION AUTHENTIFICATION ===
requireLogin();

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Méthode non autorisée', 405);
}

// === VÉRIFICATION CSRF ===
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    jsonError('Erreur de sécurité', 403);
}

// === VÉRIFIER QU'UN FICHIER A ÉTÉ UPLOADÉ ===
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    jsonError('Aucun fichier uploadé ou erreur lors de l\'upload');
}

$file = $_FILES['file'];
$clientId = isset($_POST['client_id']) ? (int)$_POST['client_id'] : null;
$isPublic = isset($_POST['is_public']) && $_POST['is_public'] === '1';

try {
    $pdo = getPdo();
    
    // === VALIDATIONS ===
    
    // Taille du fichier
    if ($file['size'] > MAX_FILE_SIZE) {
        throw new Exception('Fichier trop volumineux (max ' . (MAX_FILE_SIZE / 1024 / 1024) . ' MB)');
    }
    
    // Types MIME autorisés
    $allowedMimes = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/gif',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain',
        'text/csv'
    ];
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedMimes, true)) {
        throw new Exception('Type de fichier non autorisé');
    }
    
    // Vérifier les permissions pour associer à un client
    if ($clientId) {
        if ($userRole === 'client') {
            // Un client ne peut uploader que pour lui-même
            $stmtCheck = $pdo->prepare("SELECT id FROM clients WHERE id = ? AND email = ?");
            $stmtCheck->execute([$clientId, $_SESSION['user_email']]);
            if (!$stmtCheck->fetch()) {
                throw new Exception('Vous ne pouvez pas uploader pour ce client');
            }
        } elseif ($userRole === 'conseiller') {
            // Un conseiller ne peut uploader que pour ses clients
            $stmtCheck = $pdo->prepare("SELECT id FROM clients WHERE id = ? AND conseiller_id = ?");
            $stmtCheck->execute([$clientId, $userId]);
            if (!$stmtCheck->fetch()) {
                throw new Exception('Ce client ne vous est pas assigné');
            }
        }
        // Admin peut tout faire
    }
    
    // === CRÉER LE DOSSIER D'UPLOAD SI NÉCESSAIRE ===
    $uploadDir = UPLOAD_PATH;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Créer un sous-dossier par année/mois
    $subDir = date('Y/m');
    $targetDir = $uploadDir . '/' . $subDir;
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    // === GÉNÉRER UN NOM DE FICHIER SÉCURISÉ ===
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $safeName = bin2hex(random_bytes(16)) . '.' . $extension;
    $relativePath = 'uploads/' . $subDir . '/' . $safeName;
    $fullPath = ROOT_PATH . '/' . $relativePath;
    
    // === DÉPLACER LE FICHIER ===
    if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
        throw new Exception('Erreur lors du déplacement du fichier');
    }
    
    // === ENREGISTRER EN BASE DE DONNÉES ===
    $stmt = $pdo->prepare("
        INSERT INTO files (filename, original_name, file_path, file_size, mime_type, uploaded_by, client_id, is_public)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $safeName,
        $file['name'],
        $relativePath,
        $file['size'],
        $mimeType,
        $userId,
        $clientId,
        $isPublic ? 1 : 0
    ]);
    
    $fileId = $pdo->lastInsertId();
    
    // === LOGGER L'ACTION ===
    logActivity($userId, 'file_uploaded', "Fichier uploadé: {$file['name']} (ID: $fileId)");
    
    // === RÉPONSE JSON ===
    jsonSuccess([
        'file_id' => $fileId,
        'filename' => $file['name'],
        'download_url' => url("download.php?file=$fileId")
    ], 'Fichier uploadé avec succès');
    
} catch (Exception $e) {
    error_log("Upload error: " . $e->getMessage());
    jsonError($e->getMessage());
}