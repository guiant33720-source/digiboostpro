<?php
/**
 * Dashboard Client
 */
require_once '../config.php';

requireRole(['client']);

$pageTitle = 'Mon Espace Client';
$userEmail = $_SESSION['user_email'];

// Récupération des informations du client
try {
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE email = ? LIMIT 1");
    $stmt->execute([$userEmail]);
    $client = $stmt->fetch();
    
    if (!$client) {
        die("Aucun compte client trouvé.");
    }
    
    // Récupération des transactions du client
    $stmtTransactions = $pdo->prepare("
        SELECT * FROM transactions 
        WHERE client_id = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmtTransactions->execute([$client['id']]);
    $transactions = $stmtTransactions->fetchAll();
    
    // Statistiques personnelles
    $stmtStats = $pdo->prepare("
        SELECT 
            COUNT(*) as total_transactions,
            SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_amount,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_transactions
        FROM transactions 
        WHERE client_id = ?
    ");
    $stmtStats->execute([$client['id']]);
    $stats = $stmtStats->fetch();
    
} catch (PDOException $e) {
    error_log("Erreur dashboard client: " . $e->getMessage());
    die("Erreur lors du chargement des données.");
}

include '../header.php';
?>

<section class="dashboard">
    <div class="container">
        <h2>Bienvenue, <?php echo e($client['name']); ?></h2>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📋</div>
                <div class="stat-content">
                    <h3><?php echo $stats['total_transactions']; ?></h3>
                    <p>Transactions totales</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">⏳</div>
                <div class="stat-content">
                    <h3><?php echo $stats['pending_transactions']; ?></h3>
                    <p>En attente</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-content">
                    <h3><?php echo number_format($stats['total_amount'], 2, ',', ' '); ?> €</h3>
                    <p>Volume total</p>
                </div>
            </div>
        </div>
        
        <div class="account-info">
            <h3>Mes Informations</h3>
            <div class="info-grid">
                <div class="info-item">
                    <strong>Email :</strong> <?php echo e($client['email']); ?>
                </div>
                <div class="info-item">
                    <strong>Téléphone :</strong> <?php echo e($client['phone'] ?? 'Non renseigné'); ?>
                </div>
                <div class="info-item">
                    <strong>Statut :</strong> 
                    <span class="badge badge-<?php echo $client['status']; ?>">
                        <?php echo e($client['status']); ?>
                        </span>
                </div>
                <div class="info-item">
                    <strong>Membre depuis :</strong> <?php echo date('d/m/Y', strtotime($client['created_at'])); ?>
                </div>
            </div>
        </div>
        <div class="transactions-list">
        <h3>Mes Dernières Transactions</h3>
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
                    <?php foreach ($transactions as $transaction): ?>
                        <tr>
                            <td><?php echo e($transaction['reference']); ?></td>
                            <td><?php echo e($transaction['type']); ?></td>
                            <td><?php echo number_format($transaction['amount'], 2, ',', ' '); ?> €</td>
                            <td>
                                <span class="badge badge-<?php echo $transaction['status']; ?>">
                                    <?php echo e($transaction['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="text-center mt-20">
                <a href="<?php echo url('client/transactions.php'); ?>" class="btn btn-secondary">Voir toutes mes transactions</a>
            </div>
        <?php else: ?>
            <p class="no-data">Aucune transaction pour le moment.</p>
        <?php endif; ?>
    </div>
</div>
</section>
<?php include '../footer.php'; ?>