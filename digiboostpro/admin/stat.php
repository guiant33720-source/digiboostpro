## 16. Page Statistiques Admin

**Chemin : `admin/stat.php`**
```php
<?php
/**
 * Page statistiques détaillées admin
 */
require_once '../config.php';
requireLogin('admin');

$page_title = 'Statistiques Détaillées';

// Statistiques globales
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as nb_transactions,
        SUM(montant_total) as ca_total,
        SUM(cout_achat) as cout_total,
        SUM(marge_brute) as marge_total,
        AVG(montant_total) as panier_moyen
    FROM transactions 
    WHERE statut = 'payee'
");
$stats_globales = $stmt->fetch();

// Statistiques par mois (12 derniers mois)
$stmt = $pdo->query("
    SELECT 
        DATE_FORMAT(date_transaction, '%Y-%m') as mois,
        COUNT(*) as nb_transactions,
        SUM(montant_total) as ca,
        SUM(cout_achat) as cout,
        SUM(marge_brute) as marge
    FROM transactions 
    WHERE statut = 'payee'
    AND date_transaction >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY mois
    ORDER BY mois
");
$stats_mensuelles = $stmt->fetchAll();

// Statistiques par pack
$stmt = $pdo->query("
    SELECT 
        p.nom,
        COUNT(*) as nb_ventes,
        SUM(t.montant_total) as ca_total
    FROM transactions t
    JOIN packs p ON t.pack_id = p.id
    WHERE t.statut = 'payee'
    GROUP BY p.id, p.nom
    ORDER BY nb_ventes DESC
");
$stats_packs = $stmt->fetchAll();

// Top 10 clients
$stmt = $pdo->query("
    SELECT 
        u.nom,
        u.prenom,
        c.entreprise,
        COUNT(t.id) as nb_achats,
        SUM(t.montant_total) as ca_total
    FROM clients c
    JOIN users u ON c.user_id = u.id
    JOIN transactions t ON c.id = t.client_id
    WHERE t.statut = 'payee'
    GROUP BY c.id
    ORDER BY ca_total DESC
    LIMIT 10
");
$top_clients = $stmt->fetchAll();

// Statistiques conseillers
$stmt = $pdo->query("
    SELECT 
        u.nom,
        u.prenom,
        cons.nb_clients,
        COUNT(DISTINCT c.id) as clients_actifs,
        COUNT(t.id) as nb_transactions,
        COALESCE(SUM(t.montant_total), 0) as ca_genere
    FROM conseillers cons
    JOIN users u ON cons.user_id = u.id
    LEFT JOIN clients c ON cons.id = c.conseiller_id
    LEFT JOIN transactions t ON c.id = t.client_id AND t.statut = 'payee'
    GROUP BY cons.id
    ORDER BY ca_genere DESC
");
$stats_conseillers = $stmt->fetchAll();

// Taux de conversion (fictif pour la démo)
$taux_conversion = 23.5;

// Export CSV si demandé
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=statistiques_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
    
    fputcsv($output, ['Statistiques Globales'], ';');
    fputcsv($output, ['Indicateur', 'Valeur'], ';');
    fputcsv($output, ['Nombre de transactions', $stats_globales['nb_transactions']], ';');
    fputcsv($output, ['Chiffre d\'affaires total', number_format($stats_globales['ca_total'], 2, ',', ' ') . '€'], ';');
    fputcsv($output, ['Coût total', number_format($stats_globales['cout_total'], 2, ',', ' ') . '€'], ';');
    fputcsv($output, ['Marge brute totale', number_format($stats_globales['marge_total'], 2, ',', ' ') . '€'], ';');
    fputcsv($output, ['Panier moyen', number_format($stats_globales['panier_moyen'], 2, ',', ' ') . '€'], ';');
    
    fputcsv($output, [], ';');
    fputcsv($output, ['Top 10 Clients'], ';');
    fputcsv($output, ['Nom', 'Prénom', 'Entreprise', 'Nb achats', 'CA total'], ';');
    foreach ($top_clients as $client) {
        fputcsv($output, [
            $client['nom'],
            $client['prenom'],
            $client['entreprise'],
            $client['nb_achats'],
            number_format($client['ca_total'], 2, ',', ' ') . '€'
        ], ';');
    }
    
    fclose($output);
    exit;
}

include 'includes/header.php';
?>

<!-- En-tête avec export -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="fw-bold"><i class="fas fa-chart-bar me-2"></i>Statistiques Détaillées</h2>
    <div>
        <a href="stat.php?export=csv" class="btn btn-success">
            <i class="fas fa-file-csv me-2"></i>Export CSV
        </a>
    </div>
</div>

<!-- Indicateurs clés -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-shopping-cart fa-2x text-primary mb-2"></i>
                <h4 class="fw-bold"><?php echo number_format($stats_globales['nb_transactions']); ?></h4>
                <p class="text-muted mb-0">Transactions</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-euro-sign fa-2x text-success mb-2"></i>
                <h4 class="fw-bold"><?php echo number_format($stats_globales['ca_total'], 0, ',', ' '); ?>€</h4>
                <p class="text-muted mb-0">CA Total</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-chart-line fa-2x text-warning mb-2"></i>
                <h4 class="fw-bold"><?php echo number_format($stats_globales['panier_moyen'], 0, ',', ' '); ?>€</h4>
                <p class="text-muted mb-0">Panier Moyen</p>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-percentage fa-2x text-info mb-2"></i>
                <h4 class="fw-bold"><?php echo number_format($taux_conversion, 1); ?>%</h4>
                <p class="text-muted mb-0">Taux de conversion</p>
            </div>
        </div>
    </div>
</div>

<!-- Graphiques -->
<div class="row g-4 mb-4">
    <!-- CA et Marge mensuelle -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-chart-area me-2"></i>Évolution mensuelle (12 derniers mois)</h5>
            </div>
            <div class="card-body">
                <canvas id="evolutionChart" height="80"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Répartition par pack -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-box me-2"></i>Ventes par pack</h5>
            </div>
            <div class="card-body">
                <canvas id="packsChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Graphiques supplémentaires -->
<div class="row g-4 mb-4">
    <!-- Évolution du nombre de transactions -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Nombre de transactions mensuelles</h5>
            </div>
            <div class="card-body">
                <canvas id="transactionsChart" height="80"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Taux de marge -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-percentage me-2"></i>Taux de marge mensuel</h5>
            </div>
            <div class="card-body">
                <canvas id="margeChart" height="80"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Tableaux détaillés -->
<div class="row g-4">
    <!-- Top 10 clients -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-trophy me-2"></i>Top 10 Clients</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Client</th>
                                <th>Entreprise</th>
                                <th>Achats</th>
                                <th>CA Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $rank = 1; foreach ($top_clients as $client): ?>
                            <tr>
                                <td>
                                    <?php if ($rank <= 3): ?>
                                        <i class="fas fa-medal text-warning"></i>
                                    <?php endif; ?>
                                    <?php echo $rank++; ?>
                                </td>
                                <td class="fw-bold"><?php echo e($client['prenom'] . ' ' . $client['nom']); ?></td>
                                <td><?php echo e($client['entreprise']); ?></td>
                                <td><span class="badge bg-info"><?php echo $client['nb_achats']; ?></span></td>
                                <td class="fw-bold text-success"><?php echo number_format($client['ca_total'], 0, ',', ' '); ?>€</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Performance des conseillers -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-user-friends me-2"></i>Performance des Conseillers</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Conseiller</th>
                                <th>Clients</th>
                                <th>Transactions</th>
                                <th>CA Généré</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats_conseillers as $conseiller): ?>
                            <tr>
                                <td class="fw-bold"><?php echo e($conseiller['prenom'] . ' ' . $conseiller['nom']); ?></td>
                                <td><span class="badge bg-primary"><?php echo $conseiller['clients_actifs']; ?></span></td>
                                <td><span class="badge bg-info"><?php echo $conseiller['nb_transactions']; ?></span></td>
                                <td class="fw-bold text-success"><?php echo number_format($conseiller['ca_genere'], 0, ',', ' '); ?>€</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistiques par pack -->
<div class="row g-4 mt-2">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-box-open me-2"></i>Statistiques par Pack</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Pack</th>
                                <th>Nombre de ventes</th>
                                <th>CA Total</th>
                                <th>% du CA</th>
                                <th>CA Moyen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats_packs as $pack): ?>
                            <tr>
                                <td class="fw-bold"><?php echo e($pack['nom']); ?></td>
                                <td><span class="badge bg-info"><?php echo $pack['nb_ventes']; ?></span></td>
                                <td class="fw-bold text-success"><?php echo number_format($pack['ca_total'], 0, ',', ' '); ?>€</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="progress flex-grow-1 me-2" style="height: 20px;">
                                            <div class="progress-bar" role="progressbar" 
                                                 style="width: <?php echo ($pack['ca_total'] / $stats_globales['ca_total']) * 100; ?>%">
                                                <?php echo number_format(($pack['ca_total'] / $stats_globales['ca_total']) * 100, 1); ?>%
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo number_format($pack['ca_total'] / $pack['nb_ventes'], 0, ',', ' '); ?>€</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Données pour les graphiques
const statsData = <?php echo json_encode($stats_mensuelles); ?>;
const labels = statsData.map(s => {
    const date = new Date(s.mois + '-01');
    return date.toLocaleDateString('fr-FR', { month: 'short', year: 'numeric' });
});

// Graphique évolution CA et Marge
new Chart(document.getElementById('evolutionChart'), {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [{
            label: 'Chiffre d\'Affaires',
            data: statsData.map(s => s.ca),
            backgroundColor: 'rgba(0, 123, 255, 0.7)',
            borderColor: 'rgb(0, 123, 255)',
            borderWidth: 1
        }, {
            label: 'Marge Brute',
            data: statsData.map(s => s.marge),
            backgroundColor: 'rgba(40, 167, 69, 0.7)',
            borderColor: 'rgb(40, 167, 69)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: value => value.toLocaleString('fr-FR') + '€'
                }
            }
        }
    }
});

// Graphique répartition packs
const packsData = <?php echo json_encode($stats_packs); ?>;
new Chart(document.getElementById('packsChart'), {
    type: 'doughnut',
    data: {
        labels: packsData.map(p => p.nom),
        datasets: [{
            data: packsData.map(p => p.nb_ventes),
            backgroundColor: [
                'rgb(0, 123, 255)',
                'rgb(40, 167, 69)',
                'rgb(255, 193, 7)',
                'rgb(23, 162, 184)',
                'rgb(220, 53, 69)'
            ]
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Graphique nombre de transactions
new Chart(document.getElementById('transactionsChart'), {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Nombre de transactions',
            data: statsData.map(s => s.nb_transactions),
            borderColor: 'rgb(220, 53, 69)',
            backgroundColor: 'rgba(220, 53, 69, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});

// Graphique taux de marge
new Chart(document.getElementById('margeChart'), {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Taux de marge (%)',
            data: statsData.map(s => ((s.marge / s.ca) * 100).toFixed(1)),
            borderColor: 'rgb(255, 193, 7)',
            backgroundColor: 'rgba(255, 193, 7, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: value => value + '%'
                }
            }
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>