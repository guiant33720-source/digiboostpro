---

## 📄 `/client/transactions.php`
```php
<?php
/**
 * Liste complète des transactions avec tri et filtres
 */
require_once '../config.php';

requireRole(['client']);

$pageTitle = 'Mes Transactions';
$userEmail = $_SESSION['user_email'];

// Récupération du client
try {
    $stmt = $pdo->prepare("SELECT id FROM clients WHERE email = ? LIMIT 1");
    $stmt->execute([$userEmail]);
    $client = $stmt->fetch();
    
    if (!$client) {
        die("Client non trouvé.");
    }
    
    $clientId = $client['id'];
    
    // Paramètres de tri et filtrage
    $allowedSort = ['created_at', 'amount', 'status', 'type'];
    $allowedOrder = ['ASC', 'DESC'];
    $allowedStatus = ['all', 'pending', 'completed', 'cancelled'];
    
    $sortBy = isset($_GET['sort']) && in_array($_GET['sort'], $allowedSort) ? $_GET['sort'] : 'created_at';
    $order = isset($_GET['order']) && in_array(strtoupper($_GET['order']), $allowedOrder) ? strtoupper($_GET['order']) : 'DESC';
    $statusFilter = isset($_GET['status']) && in_array($_GET['status'], $allowedStatus) ? $_GET['status'] : 'all';
    
    // Construction de la requête
    $sql = "SELECT * FROM transactions WHERE client_id = ?";
    $params = [$clientId];
    
    if ($statusFilter !== 'all') {
        $sql .= " AND status = ?";
        $params[] = $statusFilter;
    }
    
    $sql .= " ORDER BY $sortBy $order";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Erreur transactions: " . $e->getMessage());
    $transactions = [];
}

include '../header.php';
?>

<section class="dashboard">
    <div class="container">
        <h2>Mes Transactions</h2>
        
        <div class="filters">
            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label for="status">Filtrer par statut :</label>
                    <select name="status" id="status" class="form-control" onchange="this.form.submit()">
                        <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>Tous</option>
                        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>En attente</option>
                        <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Complété</option>
                        <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Annulé</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="sort">Trier par :</label>
                    <select name="sort" id="sort" class="form-control" onchange="this.form.submit()">
                        <option value="created_at" <?php echo $sortBy === 'created_at' ? 'selected' : ''; ?>>Date</option>
                        <option value="amount" <?php echo $sortBy === 'amount' ? 'selected' : ''; ?>>Montant</option>
                        <option value="status" <?php echo $sortBy === 'status' ? 'selected' : ''; ?>>Statut</option>
                        <option value="type" <?php echo $sortBy === 'type' ? 'selected' : ''; ?>>Type</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="order">Ordre :</label>
                    <select name="order" id="order" class="form-control" onchange="this.form.submit()">
                        <option value="DESC" <?php echo $order === 'DESC' ? 'selected' : ''; ?>>Décroissant</option>
                        <option value="ASC" <?php echo $order === 'ASC' ? 'selected' : ''; ?>>Croissant</option>
                    </select>
                </div>
                
                <input type="hidden" name="status" value="<?php echo clean($statusFilter); ?>">
            </form>
        </div>
        
        <?php if (count($transactions) > 0): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>
                                <a href="?sort=reference&order=<?php echo $order === 'ASC' ? 'DESC' : 'ASC'; ?>&status=<?php echo $statusFilter; ?>">
                                    Référence <?php echo $sortBy === 'reference' ? ($order === 'ASC' ? '↑' : '↓') : ''; ?>
                                </a>
                            </th>
                            <th>
                                <a href="?sort=type&order=<?php echo $order === 'ASC' ? 'DESC' : 'ASC'; ?>&status=<?php echo $statusFilter; ?>">
                                    Type <?php echo $sortBy === 'type' ? ($order === 'ASC' ? '↑' : '↓') : ''; ?>
                                </a>
                            </th>
                            <th>
                                <a href="?sort=amount&order=<?php echo $order === 'ASC' ? 'DESC' : 'ASC'; ?>&status=<?php echo $statusFilter; ?>">
                                    Montant <?php echo $sortBy === 'amount' ? ($order === 'ASC' ? '↑' : '↓') : ''; ?>
                                </a>
                            </th>
                            <th>
                                <a href="?sort=status&order=<?php echo $order === 'ASC' ? 'DESC' : 'ASC'; ?>&status=<?php echo $statusFilter; ?>">
                                    Statut <?php echo $sortBy === 'status' ? ($order === 'ASC' ? '↑' : '↓') : ''; ?>
                                </a>
                            </th>
                            <th>
                                <a href="?sort=created_at&order=<?php echo $order === 'ASC' ? 'DESC' : 'ASC'; ?>&status=<?php echo $statusFilter; ?>">
                                    Date <?php echo $sortBy === 'created_at' ? ($order === 'ASC' ? '↑' : '↓') : ''; ?>
                                </a>
                            </th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $transaction): ?>
                            <tr>
                                <td><strong><?php echo clean($transaction['reference']); ?></strong></td>
                                <td><?php echo clean($transaction['type']); ?></td>
                                <td class="amount"><?php echo number_format($transaction['amount'], 2, ',', ' '); ?> €</td>
                                <td>
                                    <span class="badge badge-<?php echo $transaction['status']; ?>">
                                        <?php echo clean($transaction['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?></td>
                                <td><?php echo clean($transaction['description'] ?? 'N/A'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="no-data">Aucune transaction trouvée avec ces critères.</p>
        <?php endif; ?>
        
        <div class="text-center mt-20">
            <a href="<?php echo url('client/dashboard.php'); ?>" class="btn btn-secondary">← Retour au dashboard</a>
        </div>
    </div>
</section>

<?php include '../footer.php'; ?>