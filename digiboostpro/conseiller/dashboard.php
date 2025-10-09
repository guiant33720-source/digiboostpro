<?php
/**
 * Dashboard conseiller
 */
require_once '../config.php';
requireLogin('conseiller');

$page_title = 'Dashboard Conseiller';

// Récupération de l'ID du conseiller
$stmt = $pdo->prepare("SELECT id FROM conseillers WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$conseiller = $stmt->fetch();
$conseiller_id = $conseiller['id'];

// Statistiques du conseiller
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM clients 
    WHERE conseiller_id = ?
");
$stmt->execute([$conseiller_id]);
$nb_clients = $stmt->fetch()['total'];

$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM transactions t
    JOIN clients c ON t.client_id = c.id
    WHERE c.conseiller_id = ? AND t.statut = 'payee'
");
$stmt->execute([$conseiller_id]);
$nb_transactions = $stmt->fetch()['total'];

$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM litiges l
    WHERE l.conseiller_id = ? AND l.statut IN ('ouvert', 'en_cours')
");
$stmt->execute([$conseiller_id]);
$nb_litiges = $stmt->fetch()['total'];

// Clients assignés
$stmt = $pdo->prepare("
    SELECT c.*, u.nom, u.prenom, u.email, u.telephone,
           COUNT(DISTINCT t.id) as nb_achats,
           COALESCE(SUM(CASE WHEN t.statut = 'payee' THEN t.montant_total ELSE 0 END), 0) as ca_total
    FROM clients c
    JOIN users u ON c.user_id = u.id
    LEFT JOIN transactions t ON c.id = t.client_id
    WHERE c.conseiller_id = ?
    GROUP BY c.id
    ORDER BY u.created_at DESC
");
$stmt->execute([$conseiller_id]);
$mes_clients = $stmt->fetchAll();

// Dernières transactions
$stmt = $pdo->prepare("
    SELECT t.*, c.entreprise, u.nom, u.prenom, p.nom as pack_nom
    FROM transactions t
    JOIN clients c ON t.client_id = c.id
    JOIN users u ON c.user_id = u.id
    LEFT JOIN packs p ON t.pack_id = p.id
    WHERE c.conseiller_id = ?
    ORDER BY t.date_transaction DESC
    LIMIT 10
");
$stmt->execute([$conseiller_id]);
$dernieres_transactions = $stmt->fetchAll();

// Litiges à gérer
$stmt = $pdo->prepare("
    SELECT l.*, u.nom, u.prenom, u.email, c.entreprise, t.montant_total
    FROM litiges l
    JOIN clients cl ON l.client_id = cl.id
    JOIN users u ON cl.user_id = u.id
    LEFT JOIN clients c ON cl.id = c.id
    LEFT JOIN transactions t ON l.transaction_id = t.id
    WHERE (l.conseiller_id = ? OR l.conseiller_id IS NULL) 
    AND l.statut IN ('ouvert', 'en_cours')
    ORDER BY l.created_at DESC
");
$stmt->execute([$conseiller_id]);
$litiges = $stmt->fetchAll();

// Messages non lus
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total 
    FROM messages 
    WHERE destinataire_id = ? AND lu = 0
");
$stmt->execute([$_SESSION['user_id']]);
$nb_messages_non_lus = $stmt->fetch()['total'];

include '../admin/includes/header.php';
?>

<!-- Modification de la sidebar pour conseiller -->
<style>
.sidebar .nav-link.active {
    background: rgba(255,255,255,0.2);
}
</style>

<script>
// Mise à jour du menu sidebar pour conseiller
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.sidebar .user-menu small');
    if (sidebar) sidebar.textContent = 'Conseiller';
    
    const nav = document.querySelector('.sidebar .nav');
    if (nav) {
        nav.innerHTML = `
            <li class="nav-item">
                <a class="nav-link active" href="dashboard.php">
<i class="fas fa-tachometer-alt"></i>Dashboard
</a>
</li>
<li class="nav-item">
<a class="nav-link" href="clients.php">
<i class="fas fa-users"></i>Mes Clients
</a>
</li>
<li class="nav-item">
<a class="nav-link" href="transactions.php">
<i class="fas fa-euro-sign"></i>Transactions
</a>
</li>
<li class="nav-item">
<a class="nav-link" href="litiges.php">
<i class="fas fa-exclamation-triangle"></i>Litiges
${<?php echo $nb_litiges; ?> > 0 ? '<span class="badge bg-danger ms-2"><?php echo $nb_litiges; ?></span>' : ''}
</a>
</li>
<li class="nav-item">
<a class="nav-link" href="messages.php">
<i class="fas fa-envelope"></i>Messages
${<?php echo $nb_messages_non_lus; ?> > 0 ? '<span class="badge bg-danger ms-2"><?php echo $nb_messages_non_lus; ?></span>' : ''}
</a>
</li>
<li class="nav-item mt-4">
<a class="nav-link" href="../index.php">
<i class="fas fa-globe"></i>Site public
</a>
</li>
<li class="nav-item">
<a class="nav-link text-danger" href="../logout.php">
<i class="fas fa-sign-out-alt"></i>Déconnexion
</a>
</li>
`;
}
});
</script>
<!-- Cartes statistiques -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card dashboard-card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Mes Clients</p>
                        <h3 class="mb-0 fw-bold"><?php echo $nb_clients; ?></h3>
                    </div>
                    <div class="stat-icon bg-primary text-white">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
    <div class="card dashboard-card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted mb-1">Transactions</p>
                    <h3 class="mb-0 fw-bold"><?php echo $nb_transactions; ?></h3>
                </div>
                <div class="stat-icon bg-success text-white">
                    <i class="fas fa-shopping-cart"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="col-md-3">
    <div class="card dashboard-card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted mb-1">Litiges Actifs</p>
                    <h3 class="mb-0 fw-bold text-warning"><?php echo $nb_litiges; ?></h3>
                </div>
                <div class="stat-icon bg-warning text-white">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="col-md-3">
    <div class="card dashboard-card border-0 shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="text-muted mb-1">Messages</p>
                    <h3 class="mb-0 fw-bold text-info"><?php echo $nb_messages_non_lus; ?></h3>
                </div>
                <div class="stat-icon bg-info text-white">
                    <i class="fas fa-envelope"></i>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
<!-- Mes clients -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-users me-2"></i>Mes Clients</h5>
                <span class="badge bg-primary"><?php echo count($mes_clients); ?> clients</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Client</th>
                                <th>Entreprise</th>
                                <th>Contact</th>
                                <th>Achats</th>
                                <th>CA Total</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mes_clients as $client): ?>
                            <tr>
                                <td class="fw-bold"><?php echo e($client['prenom'] . ' ' . $client['nom']); ?></td>
                                <td><?php echo e($client['entreprise'] ?? '-'); ?></td>
                                <td>
                                    <small>
                                        <i class="fas fa-envelope me-1"></i><?php echo e($client['email']); ?><br>
                                        <i class="fas fa-phone me-1"></i><?php echo e($client['telephone'] ?? '-'); ?>
                                    </small>
                                </td>
                                <td><span class="badge bg-info"><?php echo $client['nb_achats']; ?></span></td>
                                <td class="fw-bold text-success"><?php echo number_format($client['ca_total'], 0, ',', ' '); ?>€</td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#contactModal<?php echo $client['id']; ?>">
                                        <i class="fas fa-envelope"></i>
                                    </button>
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
<!-- Dernières transactions -->
<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-receipt me-2"></i>Dernières Transactions</h5>
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
                            <?php foreach (array_slice($dernieres_transactions, 0, 5) as $trans): ?>
                            <tr>
                                <td class="fw-bold"><?php echo e($trans['prenom'] . ' ' . $trans['nom']); ?></td>
                                <td><?php echo e($trans['pack_nom'] ?? 'Personnalisé'); ?></td>
                                <td class="fw-bold text-success"><?php echo number_format($trans['montant_total'], 2, ',', ' '); ?>€</td>
                                <td>
                                    <?php
                                    $badge_class = ['payee' => 'success', 'en_attente' => 'warning', 'annulee' => 'danger'];
                                    $statut_text = ['payee' => 'Payée', 'en_attente' => 'En attente', 'annulee' => 'Annulée'];
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
    <!-- Litiges à gérer -->
<div class="col-lg-6">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Litiges à Gérer</h5>
            <span class="badge bg-warning"><?php echo count($litiges); ?></span>
        </div>
        <div class="card-body">
            <?php if (count($litiges) > 0): ?>
                <?php foreach (array_slice($litiges, 0, 5) as $litige): ?>
                <div class="border-bottom pb-3 mb-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1 fw-bold"><?php echo e($litige['sujet']); ?></h6>
                            <small class="text-muted">
                                <i class="fas fa-user me-1"></i><?php echo e($litige['prenom'] . ' ' . $litige['nom']); ?>
                                <span class="ms-2">
                                    <i class="fas fa-calendar me-1"></i><?php echo date('d/m/Y', strtotime($litige['created_at'])); ?>
                                </span>
                            </small>
                        </div>
                        <span class="badge bg-<?php echo $litige['statut'] === 'ouvert' ? 'danger' : 'warning'; ?>">
                            <?php echo ucfirst($litige['statut']); ?>
                        </span>
                    </div>
                    <p class="mb-2 mt-2 small"><?php echo e(substr($litige['description'], 0, 100)) . '...'; ?></p>
                    <div class="d-flex gap-2">
                        <button class="btn btn-sm btn-primary" onclick="prendreEnCharge(<?php echo $litige['id']; ?>)">
                            <i class="fas fa-hand-paper me-1"></i>Prendre en charge
                        </button>
                        <a href="litiges.php?id=<?php echo $litige['id']; ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-eye me-1"></i>Détails
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center text-muted py-4">
                    <i class="fas fa-check-circle fa-3x mb-3"></i>
                    <p>Aucun litige en cours</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>
<script>
function prendreEnCharge(litigeId) {
    if (confirm('Voulez-vous prendre en charge ce litige ?')) {
        fetch('ajax_prendre_litige.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ litige_id: litigeId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                DigiboostPro.showNotification('Litige pris en charge avec succès', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                DigiboostPro.showNotification('Erreur lors de la prise en charge', 'error');
            }
        });
    }
}
</script>
<?php include '../admin/includes/footer.php'; ?>