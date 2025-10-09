<?php
/**
 * Gestion des packs - Admin
 * Version complète et corrigée
 */
require_once '../config.php';
requireLogin('admin');

$page_title = 'Gestion des Packs';

$success = '';
$error = '';

// Suppression d'un pack
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM packs WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        $success = "Pack supprimé avec succès";
    } catch (PDOException $e) {
        $error = "Erreur lors de la suppression : " . $e->getMessage();
    }
}

// Activation/Désactivation
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $stmt = $pdo->prepare("UPDATE packs SET actif = NOT actif WHERE id = ?");
    $stmt->execute([$_GET['toggle']]);
    $success = "Statut du pack mis à jour";
}

// Ajout/Modification d'un pack
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pack_id = $_POST['pack_id'] ?? null;
    $nom = trim($_POST['nom']);
    $description = trim($_POST['description']);
    $prix = floatval($_POST['prix']);
    $type = $_POST['type'];
    $actif = isset($_POST['actif']) ? 1 : 0;
    
    if (empty($nom) || empty($prix)) {
        $error = "Le nom et le prix sont obligatoires";
    } else {
        try {
            if ($pack_id) {
                $stmt = $pdo->prepare("UPDATE packs SET nom=?, description=?, prix=?, type=?, actif=? WHERE id=?");
                $stmt->execute([$nom, $description, $prix, $type, $actif, $pack_id]);
                $success = "Pack modifié avec succès";
            } else {
                $stmt = $pdo->prepare("INSERT INTO packs (nom, description, prix, type, actif) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$nom, $description, $prix, $type, $actif]);
                $success = "Pack créé avec succès";
            }
        } catch (PDOException $e) {
            $error = "Erreur : " . $e->getMessage();
        }
    }
}

// Récupération des packs avec statistiques
$stmt = $pdo->query("
    SELECT p.*, 
           COUNT(DISTINCT t.id) as nb_ventes,
           COALESCE(SUM(CASE WHEN t.statut = 'payee' THEN t.montant_total ELSE 0 END), 0) as ca_total
    FROM packs p
    LEFT JOIN transactions t ON p.id = t.pack_id
    GROUP BY p.id
    ORDER BY p.created_at DESC
");
$packs = $stmt->fetchAll();

// Statistiques
$total_packs = count($packs);
$packs_actifs = count(array_filter($packs, fn($p) => $p['actif']));
$total_ventes = array_sum(array_column($packs, 'nb_ventes'));
$ca_total = array_sum(array_column($packs, 'ca_total'));

include 'includes/header.php';
?>

<!-- Statistiques -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-box fa-2x text-primary mb-2"></i>
                <h4 class="fw-bold"><?php echo $total_packs; ?></h4>
                <p class="text-muted mb-0">Total Packs</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                <h4 class="fw-bold"><?php echo $packs_actifs; ?></h4>
                <p class="text-muted mb-0">Packs Actifs</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-shopping-cart fa-2x text-info mb-2"></i>
                <h4 class="fw-bold"><?php echo $total_ventes; ?></h4>
                <p class="text-muted mb-0">Ventes Total</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-euro-sign fa-2x text-warning mb-2"></i>
                <h4 class="fw-bold"><?php echo number_format($ca_total, 0, ',', ' '); ?>€</h4>
                <p class="text-muted mb-0">CA Total</p>
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
    <h4 class="mb-0"><i class="fas fa-box me-2"></i>Gestion des Packs</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#packModal" onclick="resetForm()">
        <i class="fas fa-plus me-2"></i>Nouveau pack
    </button>
</div>

<!-- Filtres -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-6">
                <input type="text" id="searchPack" class="form-control" placeholder="Rechercher un pack...">
            </div>
            <div class="col-md-3">
                <select id="filterType" class="form-select">
                    <option value="">Tous les types</option>
                    <option value="fixe">Fixe</option>
                    <option value="modulaire">Modulaire</option>
                </select>
            </div>
            <div class="col-md-3">
                <select id="filterStatut" class="form-select">
                    <option value="">Tous les statuts</option>
                    <option value="actif">Actifs</option>
                    <option value="inactif">Inactifs</option>
                </select>
            </div>
        </div>
    </div>
</div>

<!-- Grille des packs -->
<div class="row g-4" id="packsGrid">
    <?php foreach ($packs as $pack): ?>
    <div class="col-md-6 col-lg-4 pack-item" data-type="<?php echo $pack['type']; ?>" data-statut="<?php echo $pack['actif'] ? 'actif' : 'inactif'; ?>">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-<?php echo $pack['type'] === 'fixe' ? 'primary' : 'info'; ?> text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><?php echo e($pack['nom']); ?></h5>
                    <?php if (!$pack['actif']): ?>
                        <span class="badge bg-secondary">Inactif</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <h2 class="fw-bold text-primary"><?php echo number_format($pack['prix'], 2, ',', ' '); ?>€</h2>
                    <small class="text-muted"><?php echo ucfirst($pack['type']); ?></small>
                </div>
                
                <p class="text-muted"><?php echo e($pack['description']); ?></p>
                
                <hr>
                
                <div class="row text-center">
                    <div class="col-6">
                        <small class="text-muted">Ventes</small>
                        <h5 class="fw-bold"><?php echo $pack['nb_ventes']; ?></h5>
                    </div>
                    <div class="col-6">
                        <small class="text-muted">CA Total</small>
                        <h5 class="fw-bold text-success"><?php echo number_format($pack['ca_total'], 0, ',', ' '); ?>€</h5>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-white">
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-primary btn-sm flex-fill" onclick='editPack(<?php echo json_encode($pack, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                        <i class="fas fa-edit me-1"></i>Modifier
                    </button>
                    <a href="?toggle=<?php echo $pack['id']; ?>" class="btn btn-outline-warning btn-sm" title="Activer/Désactiver">
                        <i class="fas fa-toggle-on"></i>
                    </a>
                    <a href="?delete=<?php echo $pack['id']; ?>" class="btn btn-outline-danger btn-sm" 
                       onclick="return confirm('Supprimer ce pack ?')" title="Supprimer">
                        <i class="fas fa-trash"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Modal Ajout/Modification Pack -->
<div class="modal fade" id="packModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Nouveau Pack</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="pack_id" id="pack_id">
                    
                    <div class="mb-3">
                        <label class="form-label">Nom du pack *</label>
                        <input type="text" class="form-control" name="nom" id="nom" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" id="description" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Prix (€) *</label>
                            <input type="number" step="0.01" class="form-control" name="prix" id="prix" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Type *</label>
                            <select class="form-select" name="type" id="type" required>
                                <option value="fixe">Fixe</option>
                                <option value="modulaire">Modulaire</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" name="actif" id="actif" checked>
                        <label class="form-check-label">Pack actif (visible sur le site)</label>
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
// Filtres
document.getElementById('filterType').addEventListener('change', filterPacks);
document.getElementById('filterStatut').addEventListener('change', filterPacks);
document.getElementById('searchPack').addEventListener('keyup', filterPacks);

function filterPacks() {
    const typeFilter = document.getElementById('filterType').value;
    const statutFilter = document.getElementById('filterStatut').value;
    const searchText = document.getElementById('searchPack').value.toLowerCase();
    const items = document.querySelectorAll('.pack-item');
    
    items.forEach(item => {
        let showItem = true;
        const cardText = item.textContent.toLowerCase();
        
        if (typeFilter && item.dataset.type !== typeFilter) showItem = false;
        if (statutFilter && item.dataset.statut !== statutFilter) showItem = false;
        if (searchText && !cardText.includes(searchText)) showItem = false;
        
        item.style.display = showItem ? '' : 'none';
    });
}

function resetForm() {
    document.getElementById('modalTitle').textContent = 'Nouveau Pack';
    document.getElementById('pack_id').value = '';
    document.getElementById('nom').value = '';
    document.getElementById('description').value = '';
    document.getElementById('prix').value = '';
    document.getElementById('type').value = 'fixe';
    document.getElementById('actif').checked = true;
}

function editPack(pack) {
    console.log('Edit pack:', pack); // Debug
    
    document.getElementById('modalTitle').textContent = 'Modifier le Pack';
    document.getElementById('pack_id').value = pack.id;
    document.getElementById('nom').value = pack.nom;
    document.getElementById('description').value = pack.description || '';
    document.getElementById('prix').value = pack.prix;
    document.getElementById('type').value = pack.type;
    document.getElementById('actif').checked = pack.actif == 1;
    
    const modal = new bootstrap.Modal(document.getElementById('packModal'));
    modal.show();
}
</script>

<?php include 'includes/footer.php'; ?>