<?php
/**
 * Gestion des conseillers - Admin
 * Version complète et corrigée
 */
require_once '../config.php';
requireLogin('admin');

$page_title = 'Gestion des Conseillers';

$success = '';
$error = '';

// Suppression d'un conseiller
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = (SELECT user_id FROM conseillers WHERE id = ?)");
        $stmt->execute([$_GET['delete']]);
        $success = "Conseiller supprimé avec succès";
    } catch (PDOException $e) {
        $error = "Erreur lors de la suppression : " . $e->getMessage();
    }
}

// Modification d'un conseiller
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conseiller_id = $_POST['conseiller_id'];
    $specialite = trim($_POST['specialite']);
    
    try {
        $stmt = $pdo->prepare("UPDATE conseillers SET specialite=? WHERE id=?");
        $stmt->execute([$specialite, $conseiller_id]);
        $success = "Conseiller modifié avec succès";
    } catch (PDOException $e) {
        $error = "Erreur : " . $e->getMessage();
    }
}

// Récupération des conseillers avec leurs statistiques
$stmt = $pdo->query("
    SELECT cons.*, u.nom, u.prenom, u.email, u.telephone, u.created_at, u.actif,
           COUNT(DISTINCT c.id) as nb_clients_actifs,
           COUNT(DISTINCT t.id) as nb_transactions,
           COALESCE(SUM(CASE WHEN t.statut = 'payee' THEN t.montant_total ELSE 0 END), 0) as ca_genere,
           COUNT(DISTINCT l.id) as nb_litiges
    FROM conseillers cons
    JOIN users u ON cons.user_id = u.id
    LEFT JOIN clients c ON cons.id = c.conseiller_id
    LEFT JOIN transactions t ON c.id = t.client_id
    LEFT JOIN litiges l ON cons.id = l.conseiller_id AND l.statut IN ('ouvert', 'en_cours')
    GROUP BY cons.id
    ORDER BY ca_genere DESC
");
$conseillers = $stmt->fetchAll();

// Statistiques globales
$total_conseillers = count($conseillers);
$total_clients_geres = array_sum(array_column($conseillers, 'nb_clients_actifs'));
$total_transactions = array_sum(array_column($conseillers, 'nb_transactions'));
$total_ca = array_sum(array_column($conseillers, 'ca_genere'));

include 'includes/header.php';
?>

<!-- Statistiques globales -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-user-friends fa-2x text-primary mb-2"></i>
                <h4 class="fw-bold"><?php echo $total_conseillers; ?></h4>
                <p class="text-muted mb-0">Total Conseillers</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-users fa-2x text-success mb-2"></i>
                <h4 class="fw-bold"><?php echo $total_clients_geres; ?></h4>
                <p class="text-muted mb-0">Clients Gérés</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-shopping-cart fa-2x text-info mb-2"></i>
                <h4 class="fw-bold"><?php echo $total_transactions; ?></h4>
                <p class="text-muted mb-0">Transactions</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-euro-sign fa-2x text-warning mb-2"></i>
                <h4 class="fw-bold"><?php echo number_format($total_ca, 0, ',', ' '); ?>€</h4>
                <p class="text-muted mb-0">CA Généré</p>
            </div>
        </div>
    </div>
</div>

<!-- Messages -->
<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i><?php echo e($success); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo e($error); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- En-tête -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="fas fa-user-friends me-2"></i>Liste des Conseillers</h4>
    <a href="users.php" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Nouveau conseiller
    </a>
</div>

<!-- Tableau des conseillers -->
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="mb-3">
            <input type="text" id="searchConseiller" class="form-control" placeholder="Rechercher un conseiller...">
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover" id="conseillersTable">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Conseiller</th>
                        <th>Contact</th>
                        <th>Spécialité</th>
                        <th>Clients</th>
                        <th>Transactions</th>
                        <th>CA Généré</th>
                        <th>Litiges</th>
                        <th>Statut</th>
                        <th width="150">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($conseillers as $conseiller): ?>
                    <tr>
                        <td><?php echo $conseiller['id']; ?></td>
                        <td class="fw-bold"><?php echo e($conseiller['prenom'] . ' ' . $conseiller['nom']); ?></td>
                        <td>
                            <small>
                                <i class="fas fa-envelope me-1"></i><?php echo e($conseiller['email']); ?><br>
                                <i class="fas fa-phone me-1"></i><?php echo e($conseiller['telephone'] ?? '-'); ?>
                            </small>
                        </td>
                        <td>
                            <?php if ($conseiller['specialite']): ?>
                                <span class="badge bg-info"><?php echo e($conseiller['specialite']); ?></span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge bg-primary"><?php echo $conseiller['nb_clients_actifs']; ?></span></td>
                        <td><span class="badge bg-success"><?php echo $conseiller['nb_transactions']; ?></span></td>
                        <td class="fw-bold text-success"><?php echo number_format($conseiller['ca_genere'], 0, ',', ' '); ?>€</td>
                        <td>
                            <?php if ($conseiller['nb_litiges'] > 0): ?>
                                <span class="badge bg-warning"><?php echo $conseiller['nb_litiges']; ?></span>
                            <?php else: ?>
                                <span class="badge bg-success">0</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($conseiller['actif']): ?>
                                <span class="badge bg-success">Actif</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactif</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick='editConseiller(<?php echo json_encode($conseiller, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="clients.php?conseiller=<?php echo $conseiller['id']; ?>" class="btn btn-outline-info" title="Voir clients">
                                    <i class="fas fa-users"></i>
                                </a>
                                <a href="?delete=<?php echo $conseiller['id']; ?>" class="btn btn-outline-danger" 
                                   onclick="return confirm('Supprimer ce conseiller ?')" title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Modification Conseiller -->
<div class="modal fade" id="conseillerModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier le Conseiller</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="conseiller_id" id="conseiller_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Nom</label>
                        <input type="text" class="form-control" id="modal_nom" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Prénom</label>
                        <input type="text" class="form-control" id="modal_prenom" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Spécialité</label>
                        <input type="text" class="form-control" name="specialite" id="specialite" 
                               placeholder="Ex: Marketing Digital, E-commerce, SEO...">
                    </div>
                    
                    <div class="alert alert-info">
                        <small>
                            <i class="fas fa-info-circle me-2"></i>
                            Pour modifier les informations personnelles (email, téléphone, mot de passe), 
                            utilisez la page <a href="users.php">Gestion des utilisateurs</a>.
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Fonction de recherche dans le tableau
function initTableSearch() {
    const searchInput = document.getElementById('searchConseiller');
    const table = document.getElementById('conseillersTable');
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    
    searchInput.addEventListener('keyup', function() {
        const filter = this.value.toLowerCase();
        
        Array.from(rows).forEach(function(row) {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
}

// Initialisation de la recherche
initTableSearch();

function editConseiller(conseiller) {
    console.log('Edit conseiller:', conseiller); // Debug
    
    document.getElementById('conseiller_id').value = conseiller.id;
    document.getElementById('modal_nom').value = conseiller.nom;
    document.getElementById('modal_prenom').value = conseiller.prenom;
    document.getElementById('specialite').value = conseiller.specialite || '';
    
    const modal = new bootstrap.Modal(document.getElementById('conseillerModal'));
    modal.show();
}
</script>

<?php include 'includes/footer.php'; ?>