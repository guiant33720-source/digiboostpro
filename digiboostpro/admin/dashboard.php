<?php
/**
 * Dashboard administrateur
 */
require_once '../config.php';
requireLogin('admin');

$page_title = 'Dashboard Admin';

// Récupération des statistiques
$stmt = $pdo->query("SELECT SUM(montant_total) as ca FROM transactions WHERE statut = 'payee'");
$chiffre_affaires = $stmt->fetch()['ca'] ?? 0;

$stmt = $pdo->query("SELECT SUM(marge_brute) as marge FROM transactions WHERE statut = 'payee'");
$marge_brute = $stmt->fetch()['marge'] ?? 0;

$stmt = $pdo->query("SELECT SUM(cout_achat) as cout FROM transactions WHERE statut = 'payee'");
$cout_total = $stmt->fetch()['cout'] ?? 0;

$benefice_net = $marge_brute;

$stmt = $pdo->query("SELECT COUNT(*) as total FROM clients");
$nb_clients = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM transactions WHERE statut = 'payee'");
$nb_transactions = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM litiges WHERE statut IN ('ouvert', 'en_cours')");
$nb_litiges_actifs = $stmt->fetch()['total'];

// Derniers clients
$stmt = $pdo->query("
    SELECT c.*, u.email, u.nom, u.prenom, u.created_at 
    FROM clients c
    JOIN users u ON c.user_id = u.id
    ORDER BY u.created_at DESC
    LIMIT 5
");
$derniers_clients = $stmt->fetchAll();

// Dernières transactions
$stmt = $pdo->query("
    SELECT t.*, c.entreprise, u.nom, u.prenom, p.nom as pack_nom
    FROM transactions t
    JOIN clients c ON t.client_id = c.id
    JOIN users u ON c.user_id = u.id
    LEFT JOIN packs p ON t.pack_id = p.id
    ORDER BY t.date_transaction DESC
    LIMIT 5
");
$dernieres_transactions = $stmt->fetchAll();

// Données pour graphiques
$stmt = $pdo->query("
    SELECT DATE_FORMAT(date_transaction, '%Y-%m') as mois, 
           SUM(montant_total) as ca,
           SUM(marge_brute) as marge
    FROM transactions 
    WHERE statut = 'payee' 
    AND date_transaction >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY mois
    ORDER BY mois
");
$ca_mensuel = $stmt->fetchAll();

include 'includes/header.php';
?>

<!-- Cartes de statistiques -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card dashboard-card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Chiffre d'Affaires</p>
                        <h3 class="mb-0 fw-bold"><?php echo number_format($chiffre_affaires, 0, ',', ' '); ?>€</h3>
                    </div>
                    <div class="stat-icon bg-primary text-white">
                        <i class="fas fa-euro-sign"></i>
                    </div>
                </div>
                <a href="transactions.php?statut=payee" class="btn btn-sm btn-outline-primary mt-3 w-100">
                    <i class="fas fa-eye me-1"></i>Voir détails
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card dashboard-card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Bénéfice Net</p>
                        <h3 class="mb-0 fw-bold text-success"><?php echo number_format($benefice_net, 0, ',', ' '); ?>€</h3>
                    </div>
                    <div class="stat-icon bg-success text-white">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>
                <a href="stat.php" class="btn btn-sm btn-outline-success mt-3 w-100">
                    <i class="fas fa-chart-bar me-1"></i>Statistiques
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card dashboard-card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Marge Brute</p>
                        <h3 class="mb-0 fw-bold text-warning"><?php echo number_format($marge_brute, 0, ',', ' '); ?>€</h3>
                    </div>
                    <div class="stat-icon bg-warning text-white">
                        <i class="fas fa-percentage"></i>
                    </div>
                </div>
                <small class="text-muted">
                    Taux: <?php echo $chiffre_affaires > 0 ? number_format(($marge_brute / $chiffre_affaires) * 100, 1) : 0; ?>%
                </small>
                <a href="stat.php#marge" class="btn btn-sm btn-outline-warning mt-2 w-100">
                    <i class="fas fa-info-circle me-1"></i>Détails
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card dashboard-card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Clients</p>
                        <h3 class="mb-0 fw-bold text-info"><?php echo $nb_clients; ?></h3>
                    </div>
                    <div class="stat-icon bg-info text-white">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <a href="clients.php" class="btn btn-sm btn-outline-info mt-3 w-100">
                    <i class="fas fa-list me-1"></i>Liste clients
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Statistiques supplémentaires -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-shopping-cart fa-2x text-primary mb-2"></i>
                <h4 class="fw-bold"><?php echo $nb_transactions; ?></h4>
                <p class="text-muted mb-0">Transactions</p>
                <a href="transactions.php" class="btn btn-sm btn-link">Voir tout</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-exclamation-triangle fa-2x text-warning mb-2"></i>
                <h4 class="fw-bold"><?php echo $nb_litiges_actifs; ?></h4>
                <p class="text-muted mb-0">Litiges actifs</p>
                <a href="litiges.php" class="btn btn-sm btn-link">Gérer</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-box fa-2x text-success mb-2"></i>
                <h4 class="fw-bold"><?php echo $nb_transactions; ?></h4>
                <p class="text-muted mb-0">Packs vendus</p>
                <a href="packs.php" class="btn btn-sm btn-link">Gérer packs</a>
            </div>
        </div>
    </div>
</div>

<!-- Graphiques -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Évolution du CA et de la Marge</h5>
            </div>
            <div class="card-body">
                <canvas id="caChart" height="80"></canvas>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Répartition des ventes</h5>
            </div>
            <div class="card-body">
                <canvas id="packsChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Derniers clients et transactions -->
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Derniers clients</h5>
                <a href="clients.php"class="btn btn-sm btn-outline-primary">Voir tout</a>
</div>
<div class="card-body p-0">
<div class="table-responsive">
<table class="table table-hover mb-0">
<thead class="table-light">
<tr>
<th>Client</th>
<th>Entreprise</th>
<th>Email</th>
<th>Date</th>
</tr>
</thead>
<tbody>
<?php foreach ($derniers_clients as $client): ?>
<tr>
<td class="fw-bold"><?php echo e($client['prenom'] . ' ' . $client['nom']); ?></td>
<td><?php echo e($client['entreprise'] ?? '-'); ?></td>
<td><small><?php echo e($client['email']); ?></small></td>
<td><small><?php echo date('d/m/Y', strtotime($client['created_at'])); ?></small></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>
</div>
</div>
<div class="col-lg-6">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-receipt me-2"></i>Dernières transactions</h5>
            <a href="transactions.php" class="btn btn-sm btn-outline-primary">Voir tout</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Client</th>
                            <th>Pack</th>
                            <th>Montant</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dernieres_transactions as $trans): ?>
                        <tr>
                            <td class="fw-bold"><?php echo e($trans['prenom'] . ' ' . $trans['nom']); ?></td>
                            <td><?php echo e($trans['pack_nom'] ?? 'Personnalisé'); ?></td>
                            <td class="fw-bold text-success"><?php echo number_format($trans['montant_total'], 2, ',', ' '); ?>€</td>
                            <td>
                                <?php
                                $badge_class = [
                                    'payee' => 'success',
                                    'en_attente' => 'warning',
                                    'annulee' => 'danger'
                                ];
                                $statut_text = [
                                    'payee' => 'Payée',
                                    'en_attente' => 'En attente',
                                    'annulee' => 'Annulée'
                                ];
                                ?>
                                <span class="badge bg-<?php echo $badge_class[$trans['statut']]; ?>">
                                    <?php echo $statut_text[$trans['statut']]; ?>
                                </span>
                            </td>
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
// Graphique CA et Marge
const caData = <?php echo json_encode(array_column($ca_mensuel, 'ca')); ?>;
const margeData = <?php echo json_encode(array_column($ca_mensuel, 'marge')); ?>;
const labels = <?php echo json_encode(array_map(function($m) { 
    return date('M Y', strtotime($m . '-01')); 
}, array_column($ca_mensuel, 'mois'))); ?>;

const caCtx = document.getElementById('caChart').getContext('2d');
new Chart(caCtx, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Chiffre d\'Affaires',
            data: caData,
            borderColor: 'rgb(0, 123, 255)',
            backgroundColor: 'rgba(0, 123, 255, 0.1)',
            tension: 0.4,
            fill: true
        }, {
            label: 'Marge Brute',
            data: margeData,
            borderColor: 'rgb(40, 167, 69)',
            backgroundColor: 'rgba(40, 167, 69, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return value.toLocaleString('fr-FR') + '€';
                    }
                }
            }
        }
    }
});

// Graphique répartition des packs (exemple avec données fictives)
const packsCtx = document.getElementById('packsChart').getContext('2d');
new Chart(packsCtx, {
    type: 'doughnut',
    data: {
        labels: ['Starter', 'Business', 'Premium', 'Modulaire', 'E-commerce'],
        datasets: [{
            data: [15, 25, 20, 18, 22],
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
</script>
<?php include 'includes/footer.php';?>