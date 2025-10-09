<?php
/**
 * Rapports et statistiques avancées (Admin)
 */
require_once '../config.php';

requireRole(['admin']);

$pageTitle = 'Rapports et Statistiques';

// Période de rapport
$period = $_GET['period'] ?? '30days';
$dateFrom = match($period) {
    '7days' => date('Y-m-d', strtotime('-7 days')),
    '30days' => date('Y-m-d', strtotime('-30 days')),
    '90days' => date('Y-m-d', strtotime('-90 days')),
    'year' => date('Y-m-d', strtotime('-1 year')),
    default => date('Y-m-d', strtotime('-30 days'))
};

try {
    // Statistiques générales
    $statsGeneral = $pdo->query("
        SELECT 
            (SELECT COUNT(*) FROM users WHERE status = 'actif') as total_users_active,
            (SELECT COUNT(*) FROM clients WHERE status = 'actif') as total_clients_active,
            (SELECT COUNT(*) FROM transactions WHERE created_at >= '$dateFrom') as transactions_period,
            (SELECT SUM(amount) FROM transactions WHERE status = 'completed' AND created_at >= '$dateFrom') as revenue_period
    ")->fetch();
    
    // Transactions par statut
    $transactionsByStatus = $pdo->query("
        SELECT status, COUNT(*) as count, SUM(amount) as total
        FROM transactions
        WHERE created_at >= '$dateFrom'
        GROUP BY status
    ")->fetchAll();
    
    // Top conseillers
    $topConseillers = $pdo->query("
        SELECT 
            u.name,
            COUNT(DISTINCT c.id) as nb_clients,
            COUNT(t.id) as nb_transactions,
            COALESCE(SUM(CASE WHEN t.status = 'completed' THEN t.amount ELSE 0 END), 0) as total_revenue
        FROM users u
        LEFT JOIN clients c ON u.id = c.conseiller_id
        LEFT JOIN transactions t ON c.id = t.client_id AND t.created_at >= '$dateFrom'
        WHERE u.role = 'conseiller' AND u.status = 'actif'
        GROUP BY u.id, u.name
        ORDER BY total_revenue DESC
        LIMIT 5
    ")->fetchAll();
    
    // Évolution mensuelle
    $monthlyEvolution = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as nb_transactions,
            SUM(amount) as total_amount
        FROM transactions
        WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 12 MONTH)
        AND status = 'completed'
        GROUP BY month
        ORDER BY month ASC
    ")->fetchAll();
    
} catch (PDOException $e) {
    error_log("Erreur rapports: " . $e->getMessage());
    $statsGeneral = ['total_users_active' => 0, 'total_clients_active' => 0, 'transactions_period' => 0, 'revenue_period' => 0];
    $transactionsByStatus = [];
    $topConseillers = [];
    $monthlyEvolution = [];
}

include '../header.php';
?>

<section class="dashboard">
    <div class="container">
        <h2>Rapports et Statistiques</h2>
        
        <div class="filters">
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label for="period">Période :</label>
                    <select name="period" id="period" class="form-control" onchange="this.form.submit()">
                        <option value="7days" <?php echo $period === '7days' ? 'selected' : ''; ?>>7 derniers jours</option>
                        <option value="30days" <?php echo $period === '30days' ? 'selected' : ''; ?>>30 derniers jours</option>
                        <option value="90days" <?php echo $period === '90days' ? 'selected' : ''; ?>>90 derniers jours</option>
                        <option value="year" <?php echo $period === 'year' ? 'selected' : ''; ?>>Dernière année</option>
                    </select>
                </div>
            </form>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-content">
                    <h3><?php echo $statsGeneral['total_users_active']; ?></h3>
                    <p>Utilisateurs actifs</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">🤝</div>
                <div class="stat-content">
                    <h3><?php echo $statsGeneral['total_clients_active']; ?></h3>
                    <p>Clients actifs</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-content">
                    <h3><?php echo $statsGeneral['transactions_period']; ?></h3>
                    <p>Transactions (période)</p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-content">
                    <h3><?php echo number_format($statsGeneral['revenue_period'] ?? 0, 2, ',', ' '); ?> €</h3>
                    <p>Revenus (période)</p>
                </div>
            </div>
        </div>
        
        <div class="report-section">
            <h3>Transactions par Statut</h3>
            <?php if (count($transactionsByStatus) > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Statut</th>
                            <th>Nombre</th>
                            <th>Montant total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactionsByStatus as $stat): ?>
                            <tr>
                                <td><span class="badge badge-<?php echo $stat['status']; ?>"><?php echo clean($stat['status']); ?></span></td>
                                <td><?php echo $stat['count']; ?></td>
                                <td class="amount"><?php echo number_format($stat['total'] ?? 0, 2, ',', ' '); ?> €</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data">Aucune donnée pour cette période.</p>
            <?php endif; ?>
        </div>
        
        <div class="report-section">
            <h3>Top 5 Conseillers</h3>
            <?php if (count($topConseillers) > 0): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Conseiller</th>
                            <th>Clients</th>
                            <th>Transactions</th>
                            <th>Revenus générés</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topConseillers as $conseiller): ?>
                            <tr>
                                <td><?php echo clean($conseiller['name']); ?></td>
                                <td><?php echo $conseiller['nb_clients']; ?></td>
                                <td><?php echo $conseiller['nb_transactions']; ?></td>
                                <td class="amount"><?php echo number_format($conseiller['total_revenue'], 2, ',', ' '); ?> €</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="no-data">Aucune donnée disponible.</p>
            <?php endif; ?>
        </div>
        
        <div class="report-section">
            <h3>Évolution Mensuelle (12 derniers mois)</h3>
            <?php if (count($monthlyEvolution) > 0): ?>
                <div id="chartContainer" style="height: 400px; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <canvas id="monthlyChart"></canvas>
                </div>
                
                <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
                <script>
                const ctx = document.getElementById('monthlyChart').getContext('2d');
                const monthlyData = <?php echo json_encode($monthlyEvolution); ?>;
                
                const labels = monthlyData.map(d => {
                    const [year, month] = d.month.split('-');
                    const date = new Date(year, month - 1);
                    return date.toLocaleDateString('fr-FR', { month: 'short', year: 'numeric' });
                });
                
                const transactions = monthlyData.map(d => d.nb_transactions);
                const amounts = monthlyData.map(d => parseFloat(d.total_amount));
                
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Nombre de transactions',
                            data: transactions,
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37, 99, 235, 0.1)',
                            yAxisID: 'y',
                            tension: 0.4
                        }, {
                            label: 'Montant (€)',
                            data: amounts,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            yAxisID: 'y1',
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        scales: {
                            y: {
                                type: 'linear',
                                display: true,
                                position: 'left',
                                title: {
                                    display: true,
                                    text: 'Nombre de transactions'
                                }
                            },
                            y1: {
                                type: 'linear',
                                display: true,
                                position: 'right',
                                title: {
                                    display: true,
                                    text: 'Montant (€)'
                                },
                                grid: {
                                    drawOnChartArea: false,
                                }
                            }
                        }
                    }
                });
                </script>
            <?php else: ?>
                <p class="no-data">Pas assez de données pour afficher le graphique.</p>
            <?php endif; ?>
        </div>
        
        <div class="dashboard-actions">
            <button onclick="window.print()" class="btn btn-secondary">🖨️ Imprimer le rapport</button>
            <a href="dashboard.php" class="btn btn-secondary">← Retour</a>
        </div>
    </div>
</section>

<style>
@media print {
    .main-header, .main-footer, .filters, .dashboard-actions, .btn { display: none !important; }
    .report-section { page-break-inside: avoid; }
}
</style>

<?php include '../footer.php'; ?>