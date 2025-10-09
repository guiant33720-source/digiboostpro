## 8. Fichier AJAX pour charger les transactions d'un client

**Chemin : `admin/ajax_get_transactions.php`**
```php
<?php
/**
 * AJAX - Récupération des transactions d'un client
 */
require_once '../config.php';
requireLogin('admin');

header('Content-Type: application/json');

$client_id = $_GET['client_id'] ?? null;

if (!$client_id || !is_numeric($client_id)) {
    echo json_encode(['success' => false, 'message' => 'ID client invalide']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT t.id, t.montant_total, t.date_transaction, p.nom as pack_nom
        FROM transactions t
        LEFT JOIN packs p ON t.pack_id = p.id
        WHERE t.client_id = ?
        ORDER BY t.date_transaction DESC
    ");
    $stmt->execute([$client_id]);
    $transactions = $stmt->fetchAll();
    
    // Formater les données
    $formatted = array_map(function($trans) {
        return [
            'id' => $trans['id'],
            'pack_nom' => $trans['pack_nom'] ?? 'Personnalisé',
            'montant_total' => number_format($trans['montant_total'], 2, ',', ' '),
            'date' => date('d/m/Y', strtotime($trans['date_transaction']))
        ];
    }, $transactions);
    
    echo json_encode([
        'success' => true,
        'transactions' => $formatted
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur'
    ]);
}
?>