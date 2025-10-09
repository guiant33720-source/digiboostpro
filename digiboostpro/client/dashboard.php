## 18. Dashboard Client

**Chemin : `client/dashboard.php`**
```php
<?php
/**
 * Dashboard client
 */
require_once '../config.php';
requireLogin('client');

$page_title = 'Mon Espace Client';

// Récupération de l'ID du client
$stmt = $pdo->prepare("SELECT id, conseiller_id FROM clients WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$client = $stmt->fetch();
$client_id = $client['id'];

// Informations du conseiller
$conseiller = null;
if ($client['conseiller_id']) {
    $stmt = $pdo->prepare("
        SELECT u.nom, u.prenom, u.email, u.telephone, c.specialite
        FROM conseillers c
        JOIN users u ON c.user_id = u.id
        WHERE c.id = ?
    ");
    $stmt->execute([$client['conseiller_id']]);
    $conseiller = $stmt->fetch();
}

// Historique des achats
$stmt = $pdo->prepare("
    SELECT t.*, p.nom as pack_nom, p.description
    FROM transactions t
    LEFT JOIN packs p ON t.pack_id = p.id
    WHERE t.client_id = ?
    ORDER BY t.date_transaction DESC
");
$stmt->execute([$client_id]);
$mes_achats = $stmt->fetchAll();

// Statistiques
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as nb_achats,
        SUM(CASE WHEN statut = 'payee' THEN montant_total ELSE 0 END) as total_depense
    FROM transactions
    WHERE client_id = ?
");
$stmt->execute([$client_id]);
$stats = $stmt->fetch();

// Applications téléchargeables
$stmt = $pdo->prepare("
    SELECT DISTINCT t.nom_app, t.lien_download, t.version
    FROM telechargements t
    WHERE t.client_id = ?
    ORDER BY t.date_telechargement DESC
");
$stmt->execute([$client_id]);
$applications = $stmt->fetchAll();

// Litiges ouverts
$stmt = $pdo->prepare("
    SELECT l.*, t.montant_total, p.nom as pack_nom
    FROM litiges l
    JOIN transactions t ON l.transaction_id = t.id
    LEFT JOIN packs p ON t.pack_id = p.id
    WHERE l.client_id = ?
    ORDER BY l.created_at DESC
");
$stmt->execute([$client_id]);
$mes_litiges = $stmt->fetchAll();

include '../admin/includes/header.php';
?>

<style>
.sidebar .nav-link.active {
    background: rgba(255,255,255,0.2);
}
</style>

<script>
// Mise à jour du menu sidebar pour client
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.sidebar .user-menu small');
    if (sidebar) sidebar.textContent = 'Client';
    
    const nav = document.querySelector('.sidebar .nav');
    if (nav) {
        nav.innerHTML = `
            <li class="nav-item">
                <a class="nav-link active" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="achats.php">
                    <i class="fas fa-shopping-bag"></i>Mes Achats
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="telechargements.php">
                    <i class="fas fa-download"></i>Téléchargements
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="profil.php">
                    <i class="fas fa-user-edit"></i>Mon Profil
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="litiges.php">
                    <i class="fas fa-exclamation-circle"></i>Support
                </a>
            </li>
            <li class="nav-item mt-4">
                <a class="nav-link" href="../index.php">
                    <i class="fas fa-globe"></i>Site public
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../tarifs.php">
                    <i class="fas fa-tags"></i>Nos offres
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

<!-- Bannière de bienvenue -->
<div class="alert alert-primary border-0 shadow-sm mb-4">
    <div class="d-flex align-items-center">
        <i class="fas fa-user-circle fa-3x me-3"></i>
        <div>
            <h4 class="alert-heading mb-1">Bienvenue <?php echo e($_SESSION['prenom']); ?> !</h4>
            <p class="mb-0">Retrouvez toutes vos informations et gérez votre compte facilement.</p>
        </div>
    </div>
</div>

<!-- Statistiques -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card dashboard-card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Mes Achats</p>
                        <h3 class="mb-0 fw-bold"><?php echo $stats['nb_achats']; ?></h3>
                    </div>
                    <div class="stat-icon bg-primary text-white">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card dashboard-card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Total Dépensé</p>
                        <h3 class="mb-0 fw-bold text-success"><?php echo number_format($stats['total_depense'], 0, ',', ' '); ?>€</h3>
                    </div>
                    <div class="stat-icon bg-success text-white">
                        <i class="fas fa-euro-sign"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card dashboard-card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted mb-1">Applications</p>
                        <h3 class="mb-0 fw-bold text-info"><?php echo count($applications); ?></h3>
                    </div>
                    <div class="stat-icon bg-info text-white">
                        <i class="fas fa-download"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mon conseiller -->
<?php if ($conseiller): ?>
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="user-avatar bg-primary text-white me-3" style="width: 60px; height: 60px; font-size: 1.5rem;">
                        <?php echo strtoupper(substr($conseiller['prenom'], 0, 1) . substr($conseiller['nom'], 0, 1)); ?>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="mb-1">Votre conseiller : <?php echo e($conseiller['prenom'] . ' ' . $conseiller['nom']); ?></h5>
                        <p class="text-muted mb-0">
                            <i class="fas fa-briefcase me-2"></i><?php echo e($conseiller['specialite']); ?>
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="mailto:<?php echo e($conseiller['email']); ?>" class="btn btn-outline-primary">
                            <i class="fas fa-envelope me-1"></i>Email
                        </a>
                        <a href="tel:<?php echo e($conseiller['telephone']); ?>" class="btn btn-primary">
                            <i class="fas fa-phone me-1"></i>Appeler
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Applications disponibles -->
<?php if (count($applications) > 0): ?>
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-download me-2"></i>Applications Disponibles</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <?php foreach ($applications as $app): ?>
                    <div class="col-md-4">
                        <div class="card h-100 border">
                            <div class="card-body text-center">
                                <i class="fas fa-mobile-alt fa-3x text-primary mb-3"></i>
                                <h6 class="fw-bold mb-2"><?php echo e($app['nom_app']); ?></h6>
                                <p class="text-muted small mb-3">Version <?php echo e($app['version']); ?></p>
                                <a href="<?php echo e($app['lien_download']); ?>" class="btn btn-primary w-100" download>
                                    <i class="fas fa-download me-2"></i>Télécharger
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Historique des achats -->
<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Historique des Achats</h5>
                <a href="achats.php" class="btn btn-sm btn-outline-primary">Voir tout</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Pack</th>
                                <th>Date</th>
                                <th>Montant</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($mes_achats, 0, 5) as $achat): ?>
                            <tr>
                                <td class="fw-bold"><?php echo e($achat['pack_nom'] ?? 'Pack personnalisé'); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($achat['date_transaction'])); ?></td>
                                <td class="fw-bold text-success"><?php echo number_format($achat['montant_total'], 2, ',', ' '); ?>€</td>
                                <td>
                                    <?php
                                    $badge_class = ['payee' => 'success', 'en_attente' => 'warning', 'annulee' => 'danger'];
                                    $statut_text = ['payee' => 'Payé', 'en_attente' => 'En attente', 'annulee' => 'Annulé'];
                                    ?>
                                    <span class="badge bg-<?php echo $badge_class[$achat['statut']]; ?>">
                                        <?php echo $statut_text[$achat['statut']]; ?>
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
    
    <!-- Support / Litiges -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="fas fa-headset me-2"></i>Support</h5>
            </div>
            <div class="card-body">
                <?php if (count($mes_litiges) > 0): ?>
                    <p class="text-muted mb-3">Vous avez <?php echo count($mes_litiges); ?> demande(s) en cours</p>
                    <?php foreach (array_slice($mes_litiges, 0, 3) as $litige): ?>
                    <div class="border-bottom pb-2 mb-2">
                        <h6 class="mb-1"><?php echo e($litige['sujet']); ?></h6>
                        <small class="text-muted">
                            <span class="badge bg-<?php echo $litige['statut'] === 'ouvert' ? 'danger' : ($litige['statut'] === 'resolu' ? 'success' : 'warning'); ?>">
                                <?php echo ucfirst($litige['statut']); ?>
                            </span>
                        </small>
                    </div>
                    <?php endforeach; ?>
                    <a href="litiges.php" class="btn btn-outline-primary btn-sm w-100 mt-2">Voir tout</a>
                <?php else: ?>
                    <p class="text-muted mb-3">Besoin d'aide ?</p>
                    <a href="litiges.php" class="btn btn-primary w-100">
                        <i class="fas fa-plus me-2"></i>Nouvelle demande
                    </a>
                <?php endif; ?>
                
                <hr>
                
                <div class="text-center">
                    <p class="small text-muted mb-2">Ou contactez-nous directement</p>
                    <a href="mailto:<?php echo CONTACT_EMAIL; ?>" class="btn btn-outline-secondary btn-sm w-100 mb-2">
                        <i class="fas fa-envelope me-1"></i><?php echo CONTACT_EMAIL; ?>
                    </a>
                    <a href="tel:+33123456789" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="fas fa-phone me-1"></i>+33 1 23 45 67 89
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Offres recommandées -->
<div class="row g-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm bg-primary text-white">
            <div class="card-body text-center py-4">
                <i class="fas fa-star fa-3x mb-3"></i>
                <h4 class="fw-bold mb-3">Découvrez nos nouvelles offres</h4>
                <p class="mb-3">Profitez de nos packs exclusifs pour booster votre présence digitale</p>
                <a href="../tarifs.php" class="btn btn-light btn-lg">
                    <i class="fas fa-tags me-2"></i>Voir les offres
                </a>
            </div>
        </div>
    </div>
</div>

<?php include '../admin/includes/footer.php'; ?>