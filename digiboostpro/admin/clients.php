<?php
/**
 * Gestion des clients - Admin
 * Version complète et corrigée
 */
require_once '../config.php';
requireLogin('admin');

$page_title = 'Gestion des Clients';

$success = '';
$error = '';

// Suppression d'un client
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        // Suppression en cascade via la clé étrangère
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = (SELECT user_id FROM clients WHERE id = ?)");
        $stmt->execute([$_GET['delete']]);
        $success = "Client supprimé avec succès";
    } catch (PDOException $e) {
        $error = "Erreur lors de la suppression : " . $e->getMessage();
    }
}

// Modification d'un client
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'];
    $conseiller_id = $_POST['conseiller_id'] ?: null;
    $entreprise = trim($_POST['entreprise']);
    $adresse = trim($_POST['adresse']);
    $ville = trim($_POST['ville']);
    $code_postal = trim($_POST['code_postal']);
    $pays = trim($_POST['pays']);
    
    try {
        $stmt = $pdo->prepare("
            UPDATE clients 
            SET conseiller_id=?, entreprise=?, adresse=?, ville=?, code_postal=?, pays=? 
            WHERE id=?
        ");
        $stmt->execute([$conseiller_id, $entreprise, $adresse, $ville, $code_postal, $pays, $client_id]);
        
        // Mise à jour du nombre de clients du conseiller
        if ($conseiller_id) {
            $stmt = $pdo->prepare("
                UPDATE conseillers 
                SET nb_clients = (SELECT COUNT(*) FROM clients WHERE conseiller_id = ?) 
                WHERE id = ?
            ");
            $stmt->execute([$conseiller_id, $conseiller_id]);
        }
        
        $success = "Client modifié avec succès";
    } catch (PDOException $e) {
        $error = "Erreur : " . $e->getMessage();
    }
}

// Récupération des clients avec leurs informations
$stmt = $pdo->query("
    SELECT c.*, u.nom, u.prenom, u.email, u.telephone, u.created_at, u.actif,
           cons_u.nom as conseiller_nom, cons_u.prenom as conseiller_prenom,
           COUNT(DISTINCT t.id) as nb_transactions,
           COALESCE(SUM(CASE WHEN t.statut = 'payee' THEN t.montant_total ELSE 0 END), 0) as ca_total
    FROM clients c
    JOIN users u ON c.user_id = u.id
    LEFT JOIN conseillers cons ON c.conseiller_id = cons.id
    LEFT JOIN users cons_u ON cons.user_id = cons_u.id
    LEFT JOIN transactions t ON c.id = t.client_id
    GROUP BY c.id
    ORDER BY u.created_at DESC
");
$clients = $stmt->fetchAll();

// Statistiques
$total_clients = count($clients);
$clients_actifs = count(array_filter($clients, fn($c) => $c['actif']));
$clients_sans_conseiller = count(array_filter($clients, fn($c) => !$c['conseiller_id']));
$ca_total_clients = array_sum(array_column($clients, 'ca_total'));

// Liste des conseillers pour l'affectation
$stmt = $pdo->query("
    SELECT c.id, u.nom, u.prenom 
    FROM conseillers c
    JOIN users u ON c.user_id = u.id
    WHERE u.actif = 1
    ORDER BY u.nom, u.prenom
");
$conseillers = $stmt->fetchAll();

include 'includes/header.php';
?>

<!-- Statistiques -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-users fa-2x text-primary mb-2"></i>
                <h4 class="fw-bold"><?php echo $total_clients; ?></h4>
                <p class="text-muted mb-0">Total Clients</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                <h4 class="fw-bold"><?php echo $clients_actifs; ?></h4>
                <p class="text-muted mb-0">Clients Actifs</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-user-times fa-2x text-warning mb-2"></i>
                <h4 class="fw-bold"><?php echo $clients_sans_conseiller; ?></h4>
                <p class="text-muted mb-0">Sans Conseiller</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-euro-sign fa-2x text-info mb-2"></i>
                <h4 class="fw-bold"><?php echo number_format($ca_total_clients, 0, ',', ' '); ?>€</h4>
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
    <h4 class="mb-0"><i class="fas fa-user-tie me-2"></i>Gestion des Clients (<?php echo $total_clients; ?>)</h4>
    <a href="users.php" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Nouveau client
    </a>
</div>

<!-- Filtres -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <input type="text" id="searchClient" class="form-control" placeholder="Rechercher un client...">
            </div>
            <div class="col-md-3">
                <select id="filterConseiller" class="form-select">
                    <option value="">Tous les conseillers</option>
                    <option value="sans">Sans conseiller</option>
                    <?php foreach ($conseillers as $cons): ?>
                        <option value="<?php echo $cons['id']; ?>">
                            <?php echo e($cons['prenom'] . ' ' . $cons['nom']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <select id="filterStatut" class="form-select">
                    <option value="">Tous les statuts</option>
                    <option value="actif">Actifs</option>
                    <option value="inactif">Inactifs</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-secondary w-100" onclick="resetFilters()">
                    <i class="fas fa-redo me-2"></i>Réinitialiser
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Tableau des clients -->
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="clientsTable">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Client</th>
                        <th>Entreprise</th>
                        <th>Contact</th>
                        <th>Conseiller</th>
                        <th>Transactions</th>
                        <th>CA Total</th>
                        <th>Statut</th>
                        <th width="150">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $client): ?>
                    <tr data-conseiller="<?php echo $client['conseiller_id'] ?? 'sans'; ?>" 
                        data-statut="<?php echo $client['actif'] ? 'actif' : 'inactif'; ?>">
                        <td><?php echo $client['id']; ?></td>
                        <td class="fw-bold"><?php echo e($client['prenom'] . ' ' . $client['nom']); ?></td>
                        <td><?php echo e($client['entreprise'] ?? '-'); ?></td>
                        <td>
                            <small>
                                <i class="fas fa-envelope me-1"></i><?php echo e($client['email']); ?><br>
                                <i class="fas fa-phone me-1"></i><?php echo e($client['telephone'] ?? '-'); ?>
                            </small>
                        </td>
                        <td>
                            <?php if ($client['conseiller_nom']): ?>
                                <span class="badge bg-info">
                                    <?php echo e($client['conseiller_prenom'] . ' ' . $client['conseiller_nom']); ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Non assigné</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge bg-primary"><?php echo $client['nb_transactions']; ?></span></td>
                        <td class="fw-bold text-success"><?php echo number_format($client['ca_total'], 0, ',', ' '); ?>€</td>
                        <td>
                            <?php if ($client['actif']): ?>
                                <span class="badge bg-success">Actif</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactif</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick='editClient(<?php echo json_encode($client, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="transactions.php?client=<?php echo $client['id']; ?>" class="btn btn-outline-info" title="Voir transactions">
                                    <i class="fas fa-receipt"></i>
                                </a>
                                <a href="?delete=<?php echo $client['id']; ?>" class="btn btn-outline-danger" 
                                   onclick="return confirm('Supprimer ce client et toutes ses données ?')" title="Supprimer">
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

<!-- Modal Modification Client -->
<div class="modal fade" id="clientModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title">Modifier le Client</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="client_id" id="client_id">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nom</label>
                            <input type="text" class="form-control" id="modal_nom" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Prénom</label>
                            <input type="text" class="form-control" id="modal_prenom" readonly>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Conseiller assigné</label>
                        <select class="form-select" name="conseiller_id" id="conseiller_id">
                            <option value="">Aucun</option>
                            <?php foreach ($conseillers as $cons): ?>
                                <option value="<?php echo $cons['id']; ?>">
                                    <?php echo e($cons['prenom'] . ' ' . $cons['nom']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Entreprise</label>
                        <input type="text" class="form-control" name="entreprise" id="entreprise">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Adresse</label>
                        <textarea class="form-control" name="adresse" id="adresse" rows="2"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ville</label>
                            <input type="text" class="form-control" name="ville" id="ville">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Code Postal</label>
                            <input type="text" class="form-control" name="code_postal" id="code_postal">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Pays</label>
                            <input type="text" class="form-control" name="pays" id="pays" value="France">
                        </div>
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
    const searchInput = document.getElementById('searchClient');
    const table = document.getElementById('clientsTable');
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

// Gestion des filtres
document.getElementById('filterConseiller').addEventListener('change', filterTable);
document.getElementById('filterStatut').addEventListener('change', filterTable);

function filterTable() {
    const conseillerFilter = document.getElementById('filterConseiller').value;
    const statutFilter = document.getElementById('filterStatut').value;
    const rows = document.querySelectorAll('#clientsTable tbody tr');
    
    rows.forEach(row => {
        let showRow = true;
        
        // Filtre conseiller
        if (conseillerFilter) {
            const rowConseiller = row.getAttribute('data-conseiller');
            if (conseillerFilter === 'sans' && rowConseiller !== 'sans') {
                showRow = false;
            } else if (conseillerFilter !== 'sans' && rowConseiller !== conseillerFilter) {
                showRow = false;
            }
        }
        
        // Filtre statut
        if (statutFilter) {
            const rowStatut = row.getAttribute('data-statut');
            if (rowStatut !== statutFilter) {
                showRow = false;
            }
        }
        
        row.style.display = showRow ? '' : 'none';
    });
}

function resetFilters() {
    document.getElementById('searchClient').value = '';
    document.getElementById('filterConseiller').value = '';
    document.getElementById('filterStatut').value = '';
    
    // Réafficher toutes les lignes
    const rows = document.querySelectorAll('#clientsTable tbody tr');
    rows.forEach(row => {
        row.style.display = '';
    });
}

function editClient(client) {
    console.log('Edit client:', client); // Debug
    
    document.getElementById('client_id').value = client.id;
    document.getElementById('modal_nom').value = client.nom;
    document.getElementById('modal_prenom').value = client.prenom;
    document.getElementById('conseiller_id').value = client.conseiller_id || '';
    document.getElementById('entreprise').value = client.entreprise || '';
    document.getElementById('adresse').value = client.adresse || '';
    document.getElementById('ville').value = client.ville || '';
    document.getElementById('code_postal').value = client.code_postal || '';
    document.getElementById('pays').value = client.pays || 'France';
    
    const modal = new bootstrap.Modal(document.getElementById('clientModal'));
    modal.show();
}
</script>

<?php include 'includes/footer.php'; ?>