<?php
/**
 * Dashboard Conseiller
 */
require_once '../config.php';

requireRole(['conseiller']);

$pageTitle = 'Dashboard Conseiller';
$userId = $_SESSION['user_id'];

// Récupération des clients assignés au conseiller
try {
    $stmt = $pdo->prepare("
        SELECT c.*, COUNT(t.id) as nb_transactions
        FROM clients c
        LEFT JOIN transactions t ON c.id = t.client_id
        WHERE c.conseiller_id = ?
        GROUP BY c.id
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$userId]);
    $clients = $stmt->fetchAll();
    
    // Statistiques personnelles
    $stmtStats = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT c.id) as total_clients,
            COUNT(t.id) as total_transactions,
            SUM(CASE WHEN t.status = 'completed' THEN t.amount ELSE 0 END) as total_amount
        FROM clients c
        LEFT JOIN transactions t ON c.id = t.client_id
        WHERE c.conseiller_id = ?
    ");
    $stmtStats->execute([$userId]);
    $stats = $stmtStats->fetch();
    
} catch (PDOException $e) {
    error_log("Erreur dashboard conseiller: " . $e->getMessage());
    $clients = [];
    $stats = ['total_clients' => 0, 'total_transactions' => 0, 'total_amount' => 0];
}

include '../header.php';
?>

<section class="dashboard">
    <div class="container">
        <h2>Dashboard Conseiller</h2>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🤝</div>
                <div class="stat-content">
                    <h3><?php echo $stats['total_clients']; ?></h3>
                    <p>Mes clients</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📋</div>
                <div class="stat-content">
                    <h3><?php echo $stats['total_transactions']; ?></h3>
                    <p>Transactions</p>
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
        
        <div class="clients-list">
            <h3>Mes Clients</h3>
            <?php if (count($clients) > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Téléphone</th>
                            <th>Transactions</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clients as $client): ?>
                            <tr>
                                <td><?php echo e($client['name']); ?></td>
                                <td><?php echo e($client['email']); ?></td>
                                <td><?php echo e($client['phone'] ?? 'N/A'); ?></td>
                                <td><?php echo $client['nb_transactions']; ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $client['status']; ?>">
                                        <?php echo e($client['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="/conseiller/clients.php?id=<?php echo $client['id']; ?>" class="btn btn-sm">Voir</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data">Aucun client assigné pour le moment.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include '../footer.php'; ?>