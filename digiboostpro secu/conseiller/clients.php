<?php
/**
 * Détails d'un client (Conseiller)
 */
require_once '../config.php';

requireRole(['conseiller']);

$pageTitle = 'Détails du Client';
$userId = $_SESSION['user_id'];
$clientId = $_GET['id'] ?? 0;

// Récupération des détails du client
try {
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id = ? AND conseiller_id = ?");
    $stmt->execute([$clientId, $userId]);
    $client = $stmt->fetch();
    
    if (!$client) {
        die("Client non trouvé ou accès non autorisé.");
    }
    
    // Transactions du client
    $stmtTrans = $pdo->prepare("SELECT * FROM transactions WHERE client_id = ? ORDER BY created_at DESC LIMIT 20");
    $stmtTrans->execute([$clientId]);
    $transactions = $stmtTrans->fetchAll();
    
} catch (PDOException $e) {
    error_log("Erreur détails client: " . $e->getMessage());
    die("Erreur lors du chargement des données.");
}

include '../header.php';
?>

<section class="dashboard">
    <div class="container">
        <h2>Détails du Client</h2>
        
        <div class="account-info">
            <h3><?php echo clean($client['name']); ?></h3>
            <div class="info-grid">
                <div class="info-item">
                    <strong>Email :</strong> <?php echo clean($client['email']); ?>
                </div>
                <div class="info-item">
                    <strong>Téléphone :</strong> <?php echo clean($client['phone'] ?? 'N/A'); ?>
                </div>
                <div class="info-item">
                    <strong>Statut :</strong> 
                    <span class="badge badge-<?php echo $client['status']; ?>"><?php echo clean($client['status']); ?></span>
                </div>
                <div class="info-item">
                    <strong>Client depuis :</strong> <?php echo date('d/m/Y', strtotime($client['created_at'])); ?>
                </div>
            </div>
        </div>
        
        <div class="transactions-list">
            <h3>Transactions</h3>
            <?php if (count($transactions) > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Référence</th>
                            <th>Type</th>
                            <th>Montant</th>
                            <th>Statut</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $trans): ?>
                            <tr>
                                <td><?php echo clean($trans['reference']); ?></td>
                                <td><?php echo clean($trans['type']); ?></td>
                                <td><?php echo number_format($trans['amount'], 2, ',', ' '); ?> €</td>
                                <td><span class="badge badge-<?php echo $trans['status']; ?>"><?php echo clean($trans['status']); ?></span></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($trans['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data">Aucune transaction pour ce client.</p>
            <?php endif; ?>
        </div>
        
        <div class="text-center mt-20">
            <a href="/conseiller/dashboard.php" class="btn btn-secondary">← Retour</a>
        </div>
    </div>
</section>

<?php include '../footer.php'; ?>