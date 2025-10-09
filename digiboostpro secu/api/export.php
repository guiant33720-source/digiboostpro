<?php
/**
 * API Export sécurisée - CSV avec vérification des rôles
 * Version: 2.0 Secure
 */
require_once __DIR__ . '/../config.php';

// === VÉRIFICATION SESSION ET RÔLE ===
requireRole(['admin', 'conseiller']);

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'];
$type = $_GET['type'] ?? '';

// Validation du type d'export
$allowedTypes = ['clients', 'transactions', 'users'];
if (!in_array($type, $allowedTypes, true)) {
    http_response_code(400);
    die('Type d\'export invalide');
}

// Les users ne peuvent être exportés que par admin
if ($type === 'users') {
    requireRole(['admin']);
}

/**
 * Fonction d'export CSV sécurisée
 */
function exportToCSV(string $filename, array $headers, array $data): void {
    // Headers pour forcer le téléchargement
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('X-Content-Type-Options: nosniff');
    
    $output = fopen('php://output', 'w');
    
    // BOM UTF-8 pour Excel
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // En-têtes
    fputcsv($output, $headers, ';');
    
    // Données
    foreach ($data as $row) {
        fputcsv($output, $row, ';');
    }
    
    fclose($output);
    exit;
}

try {
    $pdo = getPdo();
    
    switch ($type) {
        case 'clients':
            if ($userRole === 'admin') {
                // Admin : tous les clients
                $stmt = $pdo->query("
                    SELECT c.name, c.email, c.phone, u.name as conseiller, c.status, c.created_at
                    FROM clients c
                    LEFT JOIN users u ON c.conseiller_id = u.id
                    ORDER BY c.created_at DESC
                ");
            } else {
                // Conseiller : seulement ses clients
                $stmt = $pdo->prepare("
                    SELECT name, email, phone, status, created_at
                    FROM clients
                    WHERE conseiller_id = ?
                    ORDER BY created_at DESC
                ");
                $stmt->execute([$userId]);
            }
            
            $data = [];
            while ($row = $stmt->fetch()) {
                $data[] = [
                    e($row['name']),
                    e($row['email']),
                    e($row['phone'] ?? 'N/A'),
                    e($row['conseiller'] ?? 'N/A'),
                    e($row['status']),
                    date('d/m/Y', strtotime($row['created_at']))
                ];
            }
            
            // Logger l'export
            logActivity($userId, 'export', "Export clients ($type)");
            exportToCSV(
                'clients_' . date('Y-m-d_His') . '.csv',
                ['Nom', 'Email', 'Téléphone', 'Conseiller', 'Statut', 'Date création'],
                $data
            );
            break;
            
        case 'transactions':
            if ($userRole === 'admin') {
                // Admin : toutes les transactions
                $stmt = $pdo->query("
                    SELECT t.reference, c.name as client, t.type, t.amount, t.status, t.description, t.created_at
                    FROM transactions t
                    JOIN clients c ON t.client_id = c.id
                    ORDER BY t.created_at DESC
                ");
            } else {
                // Conseiller : transactions de ses clients uniquement
                $stmt = $pdo->prepare("
                    SELECT t.reference, c.name as client, t.type, t.amount, t.status, t.description, t.created_at
                    FROM transactions t
                    JOIN clients c ON t.client_id = c.id
                    WHERE c.conseiller_id = ?
                    ORDER BY t.created_at DESC
                ");
                $stmt->execute([$userId]);
            }
            
            $data = [];
            while ($row = $stmt->fetch()) {
                $data[] = [
                    e($row['reference']),
                    e($row['client']),
                    e($row['type']),
                    number_format($row['amount'], 2, ',', ''),
                    e($row['status']),
                    e($row['description'] ?? 'N/A'),
                    date('d/m/Y H:i', strtotime($row['created_at']))
                ];
            }
            
            // Logger l'export
            logActivity($userId, 'export', "Export transactions");
            
            exportToCSV(
                'transactions_' . date('Y-m-d_His') . '.csv',
                ['Référence', 'Client', 'Type', 'Montant', 'Statut', 'Description', 'Date'],
                $data
            );
            break;
            
        case 'users':
            // Uniquement admin
            requireRole(['admin']);
            
            $stmt = $pdo->query("
                SELECT name, email, role, status, created_at
                FROM users
                ORDER BY created_at DESC
            ");
            
            $data = [];
            while ($row = $stmt->fetch()) {
                $data[] = [
                    e($row['name']),
                    e($row['email']),
                    e($row['role']),
                    e($row['status']),
                    date('d/m/Y', strtotime($row['created_at']))
                ];
            }
            
            // Logger l'export
            logActivity($userId, 'export', "Export utilisateurs");
            
            exportToCSV(
                'users_' . date('Y-m-d_His') . '.csv',
                ['Nom', 'Email', 'Rôle', 'Statut', 'Date création'],
                $data
            );
            break;
    }
    
} catch (PDOException $e) {
    error_log("Export error: " . $e->getMessage());
    http_response_code(500);
    die('Erreur lors de l\'export des données');
}