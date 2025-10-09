<?php
/**
 * Gestion des clients (Admin)
 */
require_once '../config.php';

requireRole(['admin']);

$pageTitle = 'Gestion des Clients';

// Récupération de tous les clients avec leurs conseillers
try {
    $stmt = $pdo->query("
        SELECT c.*, u.name as conseiller_name 
        FROM clients c 
        LEFT JOIN users u ON c.conseiller_id = u.id 
        ORDER BY c.created_at DESC
    ");
    $clients = $stmt->fetchAll();
    
    // Liste des conseillers pour l'assignation
    $stmtConseillers = $pdo->query("SELECT id, name FROM users WHERE role = 'conseiller' AND status = 'actif'");
    $conseillers = $stmtConseillers->fetchAll();
    
} catch (PDOException $e) {
    error_log("Erreur gestion clients: " . $e->getMessage());
    $clients = [];
    $conseillers = [];
}

include '../header.php';
?>

<section class="dashboard">
    <div class="container">
        <h2>Gestion des Clients</h2>
        
        <div class="dashboard-actions">
            <a href="dashboard.php" class="btn btn-secondary">← Retour au dashboard</a>
        </div>
        
        <?php if (count($clients) > 0): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Téléphone</th>
                        <th>Conseiller</th>
                        <th>Statut</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clients as $client): ?>
                        <tr>
                            <td><?php echo $client['id']; ?></td>
                            <td><?php echo e($client['name']); ?></td>
                            <td><?php echo e($client['email']); ?></td>
                            <td><?php echo e($client['phone'] ?? 'N/A'); ?></td>
                            <td><?php echo e($client['conseiller_name'] ?? 'Non assigné'); ?></td>
                            <td><span class="badge badge-<?php echo $client['status']; ?>"><?php echo e($client['status']); ?></span></td>
                            <td><?php echo date('d/m/Y', strtotime($client['created_at'])); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-data">Aucun client trouvé.</p>
        <?php endif; ?>
    </div>
</section>

<?php include '../footer.php'; ?>