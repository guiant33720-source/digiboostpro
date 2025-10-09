<?php
require_once __DIR__ . '/../config.php';

requireRole(['admin']);

$pageTitle = 'Dashboard Admin';
$pdo = getPdo();

try {
    $statsQueries = [
        'total_users' => "SELECT COUNT(*) as total FROM users",
        'total_clients' => "SELECT COUNT(*) as total FROM clients WHERE status = 'actif'",
        'total_conseillers' => "SELECT COUNT(*) as total FROM users WHERE role = 'conseiller' AND status = 'actif'",
        'total_transactions' => "SELECT COUNT(*) as total FROM transactions WHERE MONTH(created_at) = MONTH(CURRENT_DATE())"
    ];
    
    $stats = [];
    foreach ($statsQueries as $key => $query) {
        $stmt = getPdo()->query($query);
        $stats[$key] = $stmt->fetch()['total'];
    }
    
    $stmtActivities = getPdo()->query("
        SELECT u.name, l.login_time, l.ip_address 
        FROM login_logs l 
        JOIN users u ON l.user_id = u.id 
        ORDER BY l.login_time DESC 
        LIMIT 10
    ");
    $recentActivities = $stmtActivities->fetchAll();
    
} catch (PDOException $e) {
    error_log("Erreur dashboard admin: " . $e->getMessage());
    $stats = ['total_users' => 0, 'total_clients' => 0, 'total_conseillers' => 0, 'total_transactions' => 0];
    $recentActivities = [];
}

include __DIR__ . '/../header.php';
?>

<section class="dashboard">
    <div class="container">
        <h2>Dashboard Administrateur</h2>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-content">
                    <h3><?php echo $stats['total_users']; ?></h3>
                    <p>Utilisateurs</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🤝</div>
                <div class="stat-content">
                    <h3><?php echo $stats['total_clients']; ?></h3>
                    <p>Clients actifs</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">💼</div>
                <div class="stat-content">
                    <h3><?php echo $stats['total_conseillers']; ?></h3>
                    <p>Conseillers</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-content">
                    <h3><?php echo $stats['total_transactions']; ?></h3>
                    <p>Transactions (ce mois)</p>
                </div>
            </div>
        </div>
        
        <div class="dashboard-actions">
            <a href="<?php echo url('admin/users.php'); ?>" class="btn btn-primary">Gérer les utilisateurs</a>
            <a href="<?php echo url('admin/clients.php'); ?>" class="btn btn-secondary">Gérer les clients</a>
            <a href="<?php echo url('admin/reports.php'); ?>" class="btn btn-secondary">Rapports</a>
        </div>
        
        <div class="recent-activity">
            <h3>Activités récentes</h3>
            <?php if (count($recentActivities) > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Utilisateur</th>
                            <th>Date de connexion</th>
                            <th>Adresse IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentActivities as $activity): ?>
                            <tr>
                                <td><?php echo e($activity['name']); ?></td>
                                <td><?php echo e(date('d/m/Y H:i', strtotime($activity['login_time']))); ?></td>
                                <td><?php echo e($activity['ip_address']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data">Aucune activité récente.</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include __DIR__ . '/../footer.php'; ?>