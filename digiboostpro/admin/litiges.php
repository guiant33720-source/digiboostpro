## Fichier COMPLET : admin/litiges.php
```php
<?php
/**
 * Gestion des litiges - Admin
 * Version complète et corrigée
 */
require_once '../config.php';
requireLogin('admin');

$page_title = 'Gestion des Litiges';

$success = '';
$error = '';

// Suppression d'un litige
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM litiges WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        $success = "Litige supprimé avec succès";
    } catch (PDOException $e) {
        $error = "Erreur lors de la suppression : " . $e->getMessage();
    }
}

// Changement de statut
if (isset($_GET['change_status']) && is_numeric($_GET['change_status']) && isset($_GET['status'])) {
    $new_status = $_GET['status'];
    if (in_array($new_status, ['ouvert', 'en_cours', 'resolu', 'ferme'])) {
        $stmt = $pdo->prepare("UPDATE litiges SET statut = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$new_status, $_GET['change_status']]);
        $success = "Statut du litige mis à jour";
    }
}

// Assignation d'un conseiller
if (isset($_POST['assign_conseiller'])) {
    $litige_id = $_POST['litige_id'];
    $conseiller_id = $_POST['conseiller_id'] ?: null;
    
    $stmt = $pdo->prepare("UPDATE litiges SET conseiller_id = ?, statut = 'en_cours', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$conseiller_id, $litige_id]);
    $success = "Conseiller assigné avec succès";
}

// Ajout d'un litige
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_litige'])) {
    $transaction_id = $_POST['transaction_id'];
    $client_id = $_POST['client_id'];
    $sujet = trim($_POST['sujet']);
    $description = trim($_POST['description']);
    $conseiller_id = $_POST['conseiller_id'] ?: null;
    
    if (empty($transaction_id) || empty($client_id) || empty($sujet) || empty($description)) {
        $error = "Tous les champs sont obligatoires";
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO litiges (transaction_id, client_id, conseiller_id, sujet, description, statut) 
                VALUES (?, ?, ?, ?, ?, 'ouvert')
            ");
            $stmt->execute([$transaction_id, $client_id, $conseiller_id, $sujet, $description]);
            $success = "Litige créé avec succès";
        } catch (PDOException $e) {
            $error = "Erreur : " . $e->getMessage();
        }
    }
}

// Filtres
$where = ['1=1'];
$params = [];

if (isset($_GET['statut']) && in_array($_GET['statut'], ['ouvert', 'en_cours', 'resolu', 'ferme'])) {
    $where[] = "l.statut = ?";
    $params[] = $_GET['statut'];
}

if (isset($_GET['conseiller']) && is_numeric($_GET['conseiller'])) {
    $where[] = "l.conseiller_id = ?";
    $params[] = $_GET['conseiller'];
}

if (isset($_GET['sans_conseiller']) && $_GET['sans_conseiller'] == '1') {
    $where[] = "l.conseiller_id IS NULL";
}

$where_clause = 'WHERE ' . implode(' AND ', $where);

// Récupération des litiges
$stmt = $pdo->prepare("
    SELECT l.*, 
           u.nom, u.prenom, u.email,
           c.entreprise,
           t.montant_total, t.statut as transaction_statut,
           p.nom as pack_nom,
           cons_u.nom as conseiller_nom, cons_u.prenom as conseiller_prenom
    FROM litiges l
    JOIN clients cl ON l.client_id = cl.id
    JOIN users u ON cl.user_id = u.id
    LEFT JOIN clients c ON cl.id = c.id
    JOIN transactions t ON l.transaction_id = t.id
    LEFT JOIN packs p ON t.pack_id = p.id
    LEFT JOIN conseillers cons ON l.conseiller_id = cons.id
    LEFT JOIN users cons_u ON cons.user_id = cons_u.id
    $where_clause
    ORDER BY 
        CASE l.statut 
            WHEN 'ouvert' THEN 1 
            WHEN 'en_cours' THEN 2 
            WHEN 'resolu' THEN 3 
            WHEN 'ferme' THEN 4 
        END,
        l.created_at DESC
");
$stmt->execute($params);
$litiges = $stmt->fetchAll();

// Statistiques
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN statut = 'ouvert' THEN 1 ELSE 0 END) as ouverts,
        SUM(CASE WHEN statut = 'en_cours' THEN 1 ELSE 0 END) as en_cours,
        SUM(CASE WHEN statut = 'resolu' THEN 1 ELSE 0 END) as resolus,
        SUM(CASE WHEN statut = 'ferme' THEN 1 ELSE 0 END) as fermes,
        SUM(CASE WHEN conseiller_id IS NULL THEN 1 ELSE 0 END) as sans_conseiller
    FROM litiges
");
$stats = $stmt->fetch();

// Liste des conseillers
$stmt = $pdo->query("
    SELECT c.id, u.nom, u.prenom 
    FROM conseillers c
    JOIN users u ON c.user_id = u.id
    WHERE u.actif = 1
    ORDER BY u.nom, u.prenom
");
$conseillers = $stmt->fetchAll();

// Liste des clients pour le formulaire
$stmt = $pdo->query("
    SELECT c.id, u.nom, u.prenom, c.entreprise 
    FROM clients c
    JOIN users u ON c.user_id = u.id
    ORDER BY u.nom, u.prenom
");
$clients = $stmt->fetchAll();

include 'includes/header.php';
?>

<!-- Statistiques -->
<div class="row g-4 mb-4">
    <div class="col-md-2">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-exclamation-triangle fa-2x text-primary mb-2"></i>
                <h4 class="fw-bold"><?php echo $stats['total']; ?></h4>
                <p class="text-muted mb-0 small">Total</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-folder-open fa-2x text-danger mb-2"></i>
                <h4 class="fw-bold"><?php echo $stats['ouverts']; ?></h4>
                <p class="text-muted mb-0 small">Ouverts</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-spinner fa-2x text-warning mb-2"></i>
                <h4 class="fw-bold"><?php echo $stats['en_cours']; ?></h4>
                <p class="text-muted mb-0 small">En cours</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                <h4 class="fw-bold"><?php echo $stats['resolus']; ?></h4>
                <p class="text-muted mb-0 small">Résolus</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-archive fa-2x text-secondary mb-2"></i>
                <h4 class="fw-bold"><?php echo $stats['fermes']; ?></h4>
                <p class="text-muted mb-0 small">Fermés</p>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-user-times fa-2x text-info mb-2"></i>
                <h4 class="fw-bold"><?php echo $stats['sans_conseiller']; ?></h4>
                <p class="text-muted mb-0 small">Non assignés</p>
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
    <h4 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Gestion des Litiges</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#litigeModal">
        <i class="fas fa-plus me-2"></i>Nouveau litige
    </button>
</div>

<!-- Filtres -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-3">
                <input type="text" id="searchLitige" class="form-control" placeholder="Rechercher...">
            </div>
            <div class="col-md-2">
                <select name="statut" class="form-select">
                    <option value="">Tous les statuts</option>
                    <option value="ouvert" <?php echo (isset($_GET['statut']) && $_GET['statut'] === 'ouvert') ? 'selected' : ''; ?>>Ouverts</option>
                    <option value="en_cours" <?php echo (isset($_GET['statut']) && $_GET['statut'] === 'en_cours') ? 'selected' : ''; ?>>En cours</option>
                    <option value="resolu" <?php echo (isset($_GET['statut']) && $_GET['statut'] === 'resolu') ? 'selected' : ''; ?>>Résolus</option>
                    <option value="ferme" <?php echo (isset($_GET['statut']) && $_GET['statut'] === 'ferme') ? 'selected' : ''; ?>>Fermés</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="conseiller" class="form-select">
                    <option value="">Tous les conseillers</option>
                    <option value="" name="sans_conseiller" <?php echo (isset($_GET['sans_conseiller']) && $_GET['sans_conseiller'] == '1') ? 'selected' : ''; ?>>Non assignés</option>
                    <?php foreach ($conseillers as $cons): ?>
                        <option value="<?php echo $cons['id']; ?>" <?php echo (isset($_GET['conseiller']) && $_GET['conseiller'] == $cons['id']) ? 'selected' : ''; ?>>
                            <?php echo e($cons['prenom'] . ' ' . $cons['nom']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter me-2"></i>Filtrer
                </button>
            </div>
            <div class="col-md-2">
                <a href="litiges.php" class="btn btn-secondary w-100">
                    <i class="fas fa-redo me-2"></i>Réinitialiser
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Liste des litiges -->
<div class="row g-4" id="litigesContainer">
    <?php foreach ($litiges as $litige): ?>
    <div class="col-md-6 col-lg-4">
        <div class="card h-100 border-0 shadow-sm litige-card">
            <div class="card-header bg-<?php 
                echo $litige['statut'] === 'ouvert' ? 'danger' : 
                     ($litige['statut'] === 'en_cours' ? 'warning' : 
                     ($litige['statut'] === 'resolu' ? 'success' : 'secondary')); 
            ?> text-white">
                <div class="d-flex justify-content-between align-items-center">
                    <strong>#<?php echo $litige['id']; ?> - <?php echo ucfirst(str_replace('_', ' ', $litige['statut'])); ?></strong>
                    <small><?php echo date('d/m/Y', strtotime($litige['created_at'])); ?></small>
                </div>
            </div>
            <div class="card-body">
                <h6 class="fw-bold mb-2"><?php echo e($litige['sujet']); ?></h6>
                
                <div class="mb-2">
                    <small class="text-muted">
                        <i class="fas fa-user me-1"></i>
                        <strong><?php echo e($litige['prenom'] . ' ' . $litige['nom']); ?></strong>
                        <?php if ($litige['entreprise']): ?>
                            <br><span class="ms-3"><?php echo e($litige['entreprise']); ?></span>
                        <?php endif; ?>
                    </small>
                </div>
                
                <div class="mb-2">
                    <small class="text-muted">
                        <i class="fas fa-receipt me-1"></i>Transaction #<?php echo $litige['transaction_id']; ?>
                        (<?php echo number_format($litige['montant_total'], 2, ',', ' '); ?>€)
                    </small>
                </div>
                
                <?php if ($litige['conseiller_nom']): ?>
                    <div class="mb-2">
                        <small class="text-muted">
                            <i class="fas fa-user-tie me-1"></i>
                            Assigné à : <strong><?php echo e($litige['conseiller_prenom'] . ' ' . $litige['conseiller_nom']); ?></strong>
                        </small>
                    </div>
                <?php else: ?>
                    <div class="mb-2">
                        <span class="badge bg-warning text-dark">Non assigné</span>
                    </div>
                <?php endif; ?>
                
                <p class="text-muted small mb-3"><?php echo e(substr($litige['description'], 0, 100)) . (strlen($litige['description']) > 100 ? '...' : ''); ?></p>
                
                <div class="dropdown mb-2">
                    <button class="btn btn-sm btn-outline-primary dropdown-toggle w-100" type="button" data-bs-toggle="dropdown">
                        <i class="fas fa-tasks me-1"></i>Changer le statut
                    </button>
                    <ul class="dropdown-menu w-100">
                        <li><a class="dropdown-item" href="?change_status=<?php echo $litige['id']; ?>&status=ouvert">
                            <i class="fas fa-folder-open text-danger me-2"></i>Ouvert
                        </a></li>
                        <li><a class="dropdown-item" href="?change_status=<?php echo $litige['id']; ?>&status=en_cours">
                            <i class="fas fa-spinner text-warning me-2"></i>En cours
                        </a></li>
                        <li><a class="dropdown-item" href="?change_status=<?php echo $litige['id']; ?>&status=resolu">
                            <i class="fas fa-check-circle text-success me-2"></i>Résolu
                        </a></li>
                        <li><a class="dropdown-item" href="?change_status=<?php echo $litige['id']; ?>&status=ferme">
                            <i class="fas fa-archive text-secondary me-2"></i>Fermé
                        </a></li>
                    </ul>
                </div>
            </div>
            <div class="card-footer bg-white">
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-info flex-fill" onclick='viewLitige(<?php echo json_encode($litige, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                        <i class="fas fa-eye me-1"></i>Détails
                    </button>
                    <button class="btn btn-sm btn-outline-primary" onclick="assignConseiller(<?php echo $litige['id']; ?>, <?php echo $litige['conseiller_id'] ?? 'null'; ?>)">
                        <i class="fas fa-user-plus"></i>
                    </button>
                    <a href="?delete=<?php echo $litige['id']; ?>" class="btn btn-sm btn-outline-danger" 
                       onclick="return confirm('Supprimer ce litige ?')">
                        <i class="fas fa-trash"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if (empty($litiges)): ?>
    <div class="text-center py-5">
        <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
        <h5>Aucun litige trouvé</h5>
        <p class="text-muted">Tous les litiges sont gérés ou aucun litige ne correspond aux filtres.</p>
    </div>
<?php endif; ?>

<!-- Modal Nouveau Litige -->
<div class="modal fade" id="litigeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="create_litige" value="1">
                <div class="modal-header">
                    <h5 class="modal-title">Nouveau Litige</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Client *</label>
                        <select class="form-select" name="client_id" id="new_client_id" required onchange="loadTransactions()">
                            <option value="">Sélectionner un client</option>
                            <?php foreach ($clients as $client): ?>
                                <option value="<?php echo $client['id']; ?>">
                                    <?php echo e($client['prenom'] . ' ' . $client['nom'] . ' - ' . ($client['entreprise'] ?? 'Particulier')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Transaction *</label>
                        <select class="form-select" name="transaction_id" id="new_transaction_id" required>
                            <option value="">Sélectionner d'abord un client</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Assigner à un conseiller</label>
                        <select class="form-select" name="conseiller_id">
                            <option value="">Aucun (à assigner plus tard)</option>
                            <?php foreach ($conseillers as $cons): ?>
                                <option value="<?php echo $cons['id']; ?>">
                                    <?php echo e($cons['prenom'] . ' ' . $cons['nom']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Sujet *</label>
                        <input type="text" class="form-control" name="sujet" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description *</label>
                        <textarea class="form-control" name="description" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Créer le litige
</button>
</div>
</form>
</div>
</div>
</div>
<!-- Modal Assignation Conseiller -->
<div class="modal fade" id="assignModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="assign_conseiller" value="1">
                <input type="hidden" name="litige_id" id="assign_litige_id">
                <div class="modal-header">
                    <h5 class="modal-title">Assigner un Conseiller</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Conseiller</label>
                        <select class="form-select" name="conseiller_id" id="assign_conseiller_id">
                            <option value="">Aucun</option>
                            <?php foreach ($conseillers as $cons): ?>
                                <option value="<?php echo $cons['id']; ?>">
                                    <?php echo e($cons['prenom'] . ' ' . $cons['nom']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Assigner
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<!-- Modal Détails Litige -->
<div class="modal fade" id="viewLitigeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Détails du Litige</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewLitigeContent">
                <!-- Contenu dynamique -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>
<script>
// Recherche simplifiée dans les cartes
document.getElementById('searchLitige').addEventListener('keyup', function() {
    const searchText = this.value.toLowerCase();
    const cards = document.querySelectorAll('.col-md-6.col-lg-4');
    
    cards.forEach(card => {
        const cardText = card.textContent.toLowerCase();
        card.style.display = cardText.includes(searchText) ? '' : 'none';
    });
});

function assignConseiller(litigeId, conseillerActuel) {
    document.getElementById('assign_litige_id').value = litigeId;
    document.getElementById('assign_conseiller_id').value = conseillerActuel || '';
    
    const modal = new bootstrap.Modal(document.getElementById('assignModal'));
    modal.show();
}

function viewLitige(litige) {
    console.log('View litige:', litige); // Debug
    
    const statutBadge = {
        'ouvert': 'danger',
        'en_cours': 'warning',
        'resolu': 'success',
        'ferme': 'secondary'
    };
    
    const formatCurrency = (amount) => {
        return parseFloat(amount).toFixed(2).replace('.', ',') + '€';
    };
    
    const content = `
        <div class="row g-3">
            <div class="col-12">
                <div class="alert alert-${statutBadge[litige.statut]}">
                    <h6 class="fw-bold">Litige #${litige.id} - ${litige.sujet}</h6>
                    <small>Créé le : ${new Date(litige.created_at).toLocaleString('fr-FR')}</small><br>
                    <small>Mis à jour : ${new Date(litige.updated_at).toLocaleString('fr-FR')}</small>
                </div>
            </div>
            <div class="col-md-6">
                <strong>Client :</strong><br>
                ${litige.prenom} ${litige.nom}<br>
                <small class="text-muted">${litige.entreprise || 'Particulier'}</small><br>
                <small class="text-muted">${litige.email}</small>
            </div>
            <div class="col-md-6">
                <strong>Transaction :</strong><br>
                #${litige.transaction_id}<br>
                <small class="text-muted">${litige.pack_nom || 'Personnalisé'}</small><br>
                <span class="fw-bold text-primary">${formatCurrency(litige.montant_total)}</span>
            </div>
            <div class="col-md-6">
                <strong>Statut :</strong><br>
                <span class="badge bg-${statutBadge[litige.statut]}">${litige.statut.replace('_', ' ')}</span>
            </div>
            <div class="col-md-6">
                <strong>Conseiller assigné :</strong><br>
                ${litige.conseiller_nom ? `${litige.conseiller_prenom} ${litige.conseiller_nom}` : '<span class="text-muted">Non assigné</span>'}
            </div>
            <div class="col-12">
                <strong>Description :</strong>
                <div class="p-3 bg-light rounded mt-2">
                    ${litige.description}
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('viewLitigeContent').innerHTML = content;
    const modal = new bootstrap.Modal(document.getElementById('viewLitigeModal'));
    modal.show();
}

// Chargement des transactions d'un client
function loadTransactions() {
    const clientId = document.getElementById('new_client_id').value;
    const transactionSelect = document.getElementById('new_transaction_id');
    
    if (!clientId) {
        transactionSelect.innerHTML = '<option value="">Sélectionner d\'abord un client</option>';
        return;
    }
    
    transactionSelect.innerHTML = '<option value="">Chargement...</option>';
    
    fetch(`ajax_get_transactions.php?client_id=${clientId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.transactions.length > 0) {
                let options = '<option value="">Sélectionner une transaction</option>';
                data.transactions.forEach(trans => {
                    options += `<option value="${trans.id}">
                        #${trans.id} - ${trans.pack_nom || 'Personnalisé'} - ${trans.montant_total}€ (${trans.date})
                    </option>`;
                });
                transactionSelect.innerHTML = options;
            } else {
                transactionSelect.innerHTML = '<option value="">Aucune transaction trouvée</option>';
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            transactionSelect.innerHTML = '<option value="">Erreur de chargement</option>';
        });
}
</script>
<?php include 'includes/footer.php'; ?>