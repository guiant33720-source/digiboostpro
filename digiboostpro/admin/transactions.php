<?php
/**
 * Gestion des transactions - Admin
 * Version complète et corrigée
 */
require_once '../config.php';
requireLogin('admin');

$page_title = 'Gestion des Transactions';

$success = '';
$error = '';

// Suppression d'une transaction
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM transactions WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        $success = "Transaction supprimée avec succès";
    } catch (PDOException $e) {
        $error = "Erreur lors de la suppression : " . $e->getMessage();
    }
}

// Modification du statut
if (isset($_GET['change_status']) && is_numeric($_GET['change_status']) && isset($_GET['status'])) {
    $new_status = $_GET['status'];
    if (in_array($new_status, ['en_attente', 'payee', 'annulee'])) {
        $stmt = $pdo->prepare("UPDATE transactions SET statut = ? WHERE id = ?");
        $stmt->execute([$new_status, $_GET['change_status']]);
        $success = "Statut de la transaction mis à jour";
    }
}

// Ajout/Modification d'une transaction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_transaction'])) {
    $transaction_id = $_POST['transaction_id'] ?? null;
    $client_id = $_POST['client_id'];
    $pack_id = $_POST['pack_id'] ?: null;
    $montant_total = floatval($_POST['montant_total']);
    $cout_achat = floatval($_POST['cout_achat']);
    $statut = $_POST['statut'];
    $details = trim($_POST['details']);
    
    if (empty($client_id) || empty($montant_total)) {
        $error = "Le client et le montant sont obligatoires";
    } else {
        try {
            if ($transaction_id) {
                $stmt = $pdo->prepare("
                    UPDATE transactions 
                    SET client_id=?, pack_id=?, montant_total=?, cout_achat=?, statut=?, details=? 
                    WHERE id=?
                ");
                $stmt->execute([$client_id, $pack_id, $montant_total, $cout_achat, $statut, $details, $transaction_id]);
                $success = "Transaction modifiée avec succès";
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO transactions (client_id, pack_id, montant_total, cout_achat, statut, details) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$client_id, $pack_id, $montant_total, $cout_achat, $statut, $details]);
                $success = "Transaction créée avec succès";
            }
        } catch (PDOException $e) {
            $error = "Erreur : " . $e->getMessage();
        }
    }
}

// Filtres
$where = [];
$params = [];

if (isset($_GET['client']) && is_numeric($_GET['client'])) {
    $where[] = "t.client_id = ?";
    $params[] = $_GET['client'];
}

if (isset($_GET['statut']) && in_array($_GET['statut'], ['en_attente', 'payee', 'annulee'])) {
    $where[] = "t.statut = ?";
    $params[] = $_GET['statut'];
}

if (isset($_GET['date_debut']) && !empty($_GET['date_debut'])) {
    $where[] = "DATE(t.date_transaction) >= ?";
    $params[] = $_GET['date_debut'];
}

if (isset($_GET['date_fin']) && !empty($_GET['date_fin'])) {
    $where[] = "DATE(t.date_transaction) <= ?";
    $params[] = $_GET['date_fin'];
}

$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Récupération des transactions
$stmt = $pdo->prepare("
    SELECT t.*, 
           c.entreprise, 
           u.nom, u.prenom, u.email,
           p.nom as pack_nom
    FROM transactions t
    JOIN clients c ON t.client_id = c.id
    JOIN users u ON c.user_id = u.id
    LEFT JOIN packs p ON t.pack_id = p.id
    $where_clause
    ORDER BY t.date_transaction DESC
");
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Statistiques
$stmt = $pdo->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN statut = 'payee' THEN montant_total ELSE 0 END) as ca_paye,
        SUM(CASE WHEN statut = 'en_attente' THEN montant_total ELSE 0 END) as ca_attente,
        SUM(CASE WHEN statut = 'payee' THEN marge_brute ELSE 0 END) as marge_totale,
        AVG(CASE WHEN statut = 'payee' THEN montant_total END) as panier_moyen
    FROM transactions
");
$stats = $stmt->fetch();

// Liste des clients pour le formulaire
$stmt = $pdo->query("
    SELECT c.id, u.nom, u.prenom, c.entreprise 
    FROM clients c
    JOIN users u ON c.user_id = u.id
    ORDER BY u.nom, u.prenom
");
$clients = $stmt->fetchAll();

// Liste des packs
$stmt = $pdo->query("SELECT id, nom, prix FROM packs WHERE actif = 1 ORDER BY nom");
$packs = $stmt->fetchAll();

include 'includes/header.php';
?>

<!-- Statistiques -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-receipt fa-2x text-primary mb-2"></i>
                <h4 class="fw-bold"><?php echo $stats['total']; ?></h4>
                <p class="text-muted mb-0">Total Transactions</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-euro-sign fa-2x text-success mb-2"></i>
                <h4 class="fw-bold"><?php echo number_format($stats['ca_paye'], 0, ',', ' '); ?>€</h4>
                <p class="text-muted mb-0">CA Payé</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                <h4 class="fw-bold"><?php echo number_format($stats['ca_attente'], 0, ',', ' '); ?>€</h4>
                <p class="text-muted mb-0">En Attente</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-shopping-cart fa-2x text-info mb-2"></i>
                <h4 class="fw-bold"><?php echo number_format($stats['panier_moyen'], 0, ',', ' '); ?>€</h4>
                <p class="text-muted mb-0">Panier Moyen</p>
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
    <h4 class="mb-0"><i class="fas fa-receipt me-2"></i>Gestion des Transactions</h4>
    <div class="d-flex gap-2">
        <button class="btn btn-success" onclick="exportCSV()">
            <i class="fas fa-file-csv me-2"></i>Export CSV
        </button>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#transactionModal" onclick="resetForm()">
            <i class="fas fa-plus me-2"></i>Nouvelle transaction
        </button>
    </div>
</div>

<!-- Filtres -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-3">
                <input type="text" id="searchTransaction" class="form-control" placeholder="Rechercher...">
            </div>
            <div class="col-md-2">
                <select name="statut" class="form-select">
                    <option value="">Tous les statuts</option>
                    <option value="en_attente" <?php echo (isset($_GET['statut']) && $_GET['statut'] === 'en_attente') ? 'selected' : ''; ?>>En attente</option>
                    <option value="payee" <?php echo (isset($_GET['statut']) && $_GET['statut'] === 'payee') ? 'selected' : ''; ?>>Payée</option>
                    <option value="annulee" <?php echo (isset($_GET['statut']) && $_GET['statut'] === 'annulee') ? 'selected' : ''; ?>>Annulée</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_debut" class="form-control" value="<?php echo $_GET['date_debut'] ?? ''; ?>" placeholder="Date début">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_fin" class="form-control" value="<?php echo $_GET['date_fin'] ?? ''; ?>" placeholder="Date fin">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter me-2"></i>Filtrer
                </button>
                <a href="transactions.php" class="btn btn-secondary">
                    <i class="fas fa-redo"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tableau des transactions -->
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="transactionsTable">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Client</th>
                        <th>Pack</th>
                        <th>Montant</th>
                        <th>Coût</th>
                        <th>Marge</th>
                        <th>Statut</th>
                        <th width="200">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $trans): ?>
                    <tr>
                        <td class="fw-bold">#<?php echo $trans['id']; ?></td>
                        <td><?php echo date('d/m/Y H:i', strtotime($trans['date_transaction'])); ?></td>
                        <td>
                            <strong><?php echo e($trans['prenom'] . ' ' . $trans['nom']); ?></strong><br>
                            <small class="text-muted"><?php echo e($trans['entreprise'] ?? '-'); ?></small>
                        </td>
                        <td><?php echo e($trans['pack_nom'] ?? 'Personnalisé'); ?></td>
                        <td class="fw-bold text-primary"><?php echo number_format($trans['montant_total'], 2, ',', ' '); ?>€</td>
                        <td class="text-danger"><?php echo number_format($trans['cout_achat'], 2, ',', ' '); ?>€</td>
                        <td class="fw-bold text-success"><?php echo number_format($trans['marge_brute'], 2, ',', ' '); ?>€</td>
                        <td>
                            <div class="dropdown">
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
                                <button class="badge bg-<?php echo $badge_class[$trans['statut']]; ?> border-0 dropdown-toggle" 
                                        type="button" data-bs-toggle="dropdown">
                                    <?php echo $statut_text[$trans['statut']]; ?>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="?change_status=<?php echo $trans['id']; ?>&status=en_attente">En attente</a></li>
                                    <li><a class="dropdown-item" href="?change_status=<?php echo $trans['id']; ?>&status=payee">Payée</a></li>
                                    <li><a class="dropdown-item" href="?change_status=<?php echo $trans['id']; ?>&status=annulee">Annulée</a></li>
                                </ul>
                            </div>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-info" onclick='viewTransaction(<?php echo json_encode($trans, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' title="Voir détails">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-outline-primary" onclick='editTransaction(<?php echo json_encode($trans, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="?delete=<?php echo $trans['id']; ?>" class="btn btn-outline-danger" 
                                   onclick="return confirm('Supprimer cette transaction ?')" title="Supprimer">
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

<!-- Modal Ajout/Modification Transaction -->
<div class="modal fade" id="transactionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="save_transaction" value="1">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Nouvelle Transaction</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="transaction_id" id="transaction_id">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Client *</label>
                            <select class="form-select" name="client_id" id="client_id" required>
                                <option value="">Sélectionner un client</option>
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?php echo $client['id']; ?>">
                                        <?php echo e($client['prenom'] . ' ' . $client['nom'] . ' - ' . ($client['entreprise'] ?? 'Particulier')); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Pack</label>
                            <select class="form-select" name="pack_id" id="pack_id" onchange="updatePrice()">
                                <option value="">Personnalisé</option>
                                <?php foreach ($packs as $pack): ?>
                                    <option value="<?php echo $pack['id']; ?>" data-prix="<?php echo $pack['prix']; ?>">
                                        <?php echo e($pack['nom']); ?> - <?php echo number_format($pack['prix'], 2, ',', ' '); ?>€
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Montant Total (€) *</label>
                            <input type="number" step="0.01" class="form-control" name="montant_total" id="montant_total" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Coût d'Achat (€)</label>
                            <input type="number" step="0.01" class="form-control" name="cout_achat" id="cout_achat" value="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Marge Brute</label>
                            <input type="text" class="form-control" id="marge_display" readonly>
                            </div>
                            </div>
                
                <div class="mb-3">
                    <label class="form-label">Statut</label>
                    <select class="form-select" name="statut" id="statut">
                        <option value="en_attente">En attente</option>
                        <option value="payee">Payée</option>
                        <option value="annulee">Annulée</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Détails / Notes</label>
                    <textarea class="form-control" name="details" id="details" rows="3"></textarea>
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
<!-- Modal Détails Transaction -->
<div class="modal fade" id="viewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Détails de la Transaction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="viewContent">
                <!-- Contenu dynamique -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>
<script>
// Fonction de recherche dans le tableau
function initTableSearch() {
    const searchInput = document.getElementById('searchTransaction');
    const table = document.getElementById('transactionsTable');
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

// Mise à jour du prix selon le pack sélectionné
function updatePrice() {
    const packSelect = document.getElementById('pack_id');
    const montantInput = document.getElementById('montant_total');
    
    if (packSelect.value) {
        const selectedOption = packSelect.options[packSelect.selectedIndex];
        const prix = selectedOption.getAttribute('data-prix');
        montantInput.value = prix;
        calculateMarge();
    }
}

// Calcul de la marge
document.getElementById('montant_total').addEventListener('input', calculateMarge);
document.getElementById('cout_achat').addEventListener('input', calculateMarge);

function calculateMarge() {
    const montant = parseFloat(document.getElementById('montant_total').value) || 0;
    const cout = parseFloat(document.getElementById('cout_achat').value) || 0;
    const marge = montant - cout;
    
    document.getElementById('marge_display').value = marge.toFixed(2) + '€';
}

function resetForm() {
    document.getElementById('modalTitle').textContent = 'Nouvelle Transaction';
    document.getElementById('transaction_id').value = '';
    document.getElementById('client_id').value = '';
    document.getElementById('pack_id').value = '';
    document.getElementById('montant_total').value = '';
    document.getElementById('cout_achat').value = '0';
    document.getElementById('statut').value = 'en_attente';
    document.getElementById('details').value = '';
    document.getElementById('marge_display').value = '';
}

function editTransaction(trans) {
    console.log('Edit transaction:', trans); // Debug
    
    document.getElementById('modalTitle').textContent = 'Modifier la Transaction';
    document.getElementById('transaction_id').value = trans.id;
    document.getElementById('client_id').value = trans.client_id;
    document.getElementById('pack_id').value = trans.pack_id || '';
    document.getElementById('montant_total').value = trans.montant_total;
    document.getElementById('cout_achat').value = trans.cout_achat;
    document.getElementById('statut').value = trans.statut;
    document.getElementById('details').value = trans.details || '';
    calculateMarge();
    
    const modal = new bootstrap.Modal(document.getElementById('transactionModal'));
    modal.show();
}

function viewTransaction(trans) {
    const formatCurrency = (amount) => {
        return parseFloat(amount).toFixed(2).replace('.', ',') + '€';
    };
    
    const content = `
        <div class="row g-3">
            <div class="col-12">
                <div class="alert alert-info">
                    <h6 class="fw-bold">Transaction #${trans.id}</h6>
                    <small>Date : ${new Date(trans.date_transaction).toLocaleString('fr-FR')}</small>
                </div>
            </div>
            <div class="col-md-6">
                <strong>Client :</strong><br>
                ${trans.prenom} ${trans.nom}<br>
                <small class="text-muted">${trans.entreprise || 'Particulier'}</small>
            </div>
            <div class="col-md-6">
                <strong>Pack :</strong><br>
                ${trans.pack_nom || 'Personnalisé'}
            </div>
            <div class="col-md-4">
                <strong>Montant Total :</strong><br>
                <span class="text-primary fw-bold">${formatCurrency(trans.montant_total)}</span>
            </div>
            <div class="col-md-4">
                <strong>Coût d'Achat :</strong><br>
                <span class="text-danger fw-bold">${formatCurrency(trans.cout_achat)}</span>
            </div>
            <div class="col-md-4">
                <strong>Marge Brute :</strong><br>
                <span class="text-success fw-bold">${formatCurrency(trans.marge_brute)}</span>
            </div>
            <div class="col-12">
                <strong>Statut :</strong><br>
                <span class="badge bg-${trans.statut === 'payee' ? 'success' : (trans.statut === 'annulee' ? 'danger' : 'warning')}">
                    ${trans.statut === 'payee' ? 'Payée' : (trans.statut === 'annulee' ? 'Annulée' : 'En attente')}
                </span>
            </div>
            ${trans.details ? `
            <div class="col-12">
                <strong>Détails :</strong><br>
                <p class="text-muted">${trans.details}</p>
            </div>
            ` : ''}
        </div>
    `;
    
    document.getElementById('viewContent').innerHTML = content;
    const modal = new bootstrap.Modal(document.getElementById('viewModal'));
    modal.show();
}

function exportCSV() {
    const data = <?php echo json_encode($transactions); ?>;
    
    let csv = 'ID,Date,Client,Entreprise,Pack,Montant,Cout,Marge,Statut\n';
    
    data.forEach(t => {
        const date = new Date(t.date_transaction).toLocaleDateString('fr-FR');
        csv += `${t.id},"${date}","${t.prenom} ${t.nom}","${t.entreprise || '-'}","${t.pack_nom || 'Personnalisé'}",${t.montant_total},${t.cout_achat},${t.marge_brute},${t.statut}\n`;
    });
    
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    
    link.setAttribute('href', url);
    link.setAttribute('download', 'transactions_' + new Date().toISOString().split('T')[0] + '.csv');
    link.style.visibility = 'hidden';
    
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>
<?php include 'includes/footer.php'; ?>