<?php
/**
 * Gestion des options - Admin
 * Version complète et corrigée
 */
require_once '../config.php';
requireLogin('admin');

$page_title = 'Gestion des Options';

$success = '';
$error = '';

// Suppression d'une option
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM options_pack WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        $success = "Option supprimée avec succès";
    } catch (PDOException $e) {
        $error = "Erreur lors de la suppression : " . $e->getMessage();
    }
}

// Activation/Désactivation
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $stmt = $pdo->prepare("UPDATE options_pack SET actif = NOT actif WHERE id = ?");
    $stmt->execute([$_GET['toggle']]);
    $success = "Statut de l'option mis à jour";
}

// Ajout/Modification d'une option
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $option_id = $_POST['option_id'] ?? null;
    $nom = trim($_POST['nom']);
    $description = trim($_POST['description']);
    $prix = floatval($_POST['prix']);
    $actif = isset($_POST['actif']) ? 1 : 0;
    
    if (empty($nom) || empty($prix)) {
        $error = "Le nom et le prix sont obligatoires";
    } else {
        try {
            if ($option_id) {
                $stmt = $pdo->prepare("UPDATE options_pack SET nom=?, description=?, prix=?, actif=? WHERE id=?");
                $stmt->execute([$nom, $description, $prix, $actif, $option_id]);
                $success = "Option modifiée avec succès";
            } else {
                $stmt = $pdo->prepare("INSERT INTO options_pack (nom, description, prix, actif) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nom, $description, $prix, $actif]);
                $success = "Option créée avec succès";
            }
        } catch (PDOException $e) {
            $error = "Erreur : " . $e->getMessage();
        }
    }
}

// Récupération des options
$stmt = $pdo->query("SELECT * FROM options_pack ORDER BY prix ASC");
$options = $stmt->fetchAll();

// Statistiques
$total_options = count($options);
$options_actives = count(array_filter($options, fn($o) => $o['actif']));
$prix_total = array_sum(array_column($options, 'prix'));

include 'includes/header.php';
?>

<!-- Statistiques -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-cog fa-2x text-primary mb-2"></i>
                <h4 class="fw-bold"><?php echo $total_options; ?></h4>
                <p class="text-muted mb-0">Total Options</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                <h4 class="fw-bold"><?php echo $options_actives; ?></h4>
                <p class="text-muted mb-0">Options Actives</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-euro-sign fa-2x text-warning mb-2"></i>
                <h4 class="fw-bold"><?php echo number_format($prix_total, 0, ',', ' '); ?>€</h4>
                <p class="text-muted mb-0">Prix Total</p>
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
    <h4 class="mb-0"><i class="fas fa-cog me-2"></i>Gestion des Options</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#optionModal" onclick="resetForm()">
        <i class="fas fa-plus me-2"></i>Nouvelle option
    </button>
</div>

<!-- Tableau des options -->
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="mb-3">
            <input type="text" id="searchOption" class="form-control" placeholder="Rechercher une option...">
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover" id="optionsTable">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Description</th>
                        <th>Prix</th>
                        <th>Statut</th>
                        <th width="150">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($options as $option): ?>
                    <tr>
                        <td><?php echo $option['id']; ?></td>
                        <td class="fw-bold"><?php echo e($option['nom']); ?></td>
                        <td><?php echo e(substr($option['description'], 0, 100)) . (strlen($option['description']) > 100 ? '...' : ''); ?></td>
                        <td class="fw-bold text-primary"><?php echo number_format($option['prix'], 2, ',', ' '); ?>€</td>
                        <td>
                            <?php if ($option['actif']): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick='editOption(<?php echo json_encode($option, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="?toggle=<?php echo $option['id']; ?>" class="btn btn-outline-warning" 
                                   onclick="return confirm('Changer le statut de cette option ?')" title="Activer/Désactiver">
                                    <i class="fas fa-toggle-on"></i>
                                </a>
                                <a href="?delete=<?php echo $option['id']; ?>" class="btn btn-outline-danger" 
                                   onclick="return confirm('Supprimer cette option ?')" title="Supprimer">
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

<!-- Modal Ajout/Modification Option -->
<div class="modal fade" id="optionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Nouvelle Option</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="option_id" id="option_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Nom de l'option *</label>
                        <input type="text" class="form-control" name="nom" id="nom" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Prix (€) *</label>
                        <input type="number" step="0.01" class="form-control" name="prix" id="prix" required>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" name="actif" id="actif" checked>
                        <label class="form-check-label">Option active (visible sur le site)</label>
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
    const searchInput = document.getElementById('searchOption');
    const table = document.getElementById('optionsTable');
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

function resetForm() {
    document.getElementById('modalTitle').textContent = 'Nouvelle Option';
    document.getElementById('option_id').value = '';
    document.getElementById('nom').value = '';
    document.getElementById('description').value = '';
    document.getElementById('prix').value = '';
    document.getElementById('actif').checked = true;
}

function editOption(option) {
    console.log('Edit option:', option); // Debug
    
    document.getElementById('modalTitle').textContent = 'Modifier l\'Option';
    document.getElementById('option_id').value = option.id;
    document.getElementById('nom').value = option.nom;
    document.getElementById('description').value = option.description || '';
    document.getElementById('prix').value = option.prix;
    document.getElementById('actif').checked = option.actif == 1;
    
    const modal = new bootstrap.Modal(document.getElementById('optionModal'));
    modal.show();
}
</script>

<?php include 'includes/footer.php'; ?>