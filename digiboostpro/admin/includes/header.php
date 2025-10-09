<?php
/**
 * Header pour l'espace admin
 */
if (!isset($page_title)) {
    $page_title = 'Dashboard Admin';
}

// Vérifier le mode maintenance
$maintenance_active = false;
$htaccess = dirname(dirname(__DIR__)) . '/.htaccess';
if (file_exists($htaccess)) {
    $content = file_get_contents($htaccess);
    $maintenance_active = (strpos($content, 'RewriteRule ^(.*)$ /maintenance.php') !== false && 
                          strpos($content, '# RewriteRule ^(.*)$ /maintenance.php') === false);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($page_title); ?> - Admin DigiboostPro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
    <?php if ($maintenance_active): ?>
    <!-- Bandeau mode maintenance -->
    <div class="alert alert-warning mb-0 rounded-0 text-center" style="position: sticky; top: 0; z-index: 1050;">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>MODE MAINTENANCE ACTIF</strong> - Le site est en maintenance pour les visiteurs
        <a href="maintenance_manager.php" class="alert-link ms-2">Gérer</a>
    </div>
    <?php endif; ?>
    
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="sidebar bg-dark text-white" style="width: 250px; min-height: 100vh; position: fixed;">
            <div class="user-menu text-center">
                <div class="user-avatar mx-auto mb-2">
                    <?php echo strtoupper(substr($_SESSION['prenom'], 0, 1) . substr($_SESSION['nom'], 0, 1)); ?>
                </div>
                <h6 class="mb-0"><?php echo e($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?></h6>
                <small class="text-muted">Administrateur</small>
            </div>
            
            <ul class="nav flex-column mt-4">
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'stat.php' ? 'active' : ''; ?>" href="stat.php">
                        <i class="fas fa-chart-line"></i>Statistiques
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'maintenance_manager.php' ? 'active' : ''; ?>" href="maintenance_manager.php">
                        <i class="fas fa-tools"></i>Maintenance
                        <?php if ($maintenance_active): ?>
                            <span class="badge bg-danger ms-2">ON</span>
                        <?php endif; ?>
                    </a>
                </li>
                
                <li class="nav-item mt-3">
                    <small class="text-muted text-uppercase px-3">Utilisateurs</small>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>" href="users.php">
                        <i class="fas fa-users"></i>Tous les utilisateurs
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'clients.php' ? 'active' : ''; ?>" href="clients.php">
                        <i class="fas fa-user-tie"></i>Clients
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'conseillers.php' ? 'active' : ''; ?>" href="conseillers.php">
                        <i class="fas fa-user-friends"></i>Conseillers
                    </a>
                </li>
                
                <li class="nav-item mt-3">
                    <small class="text-muted text-uppercase px-3">Catalogue</small>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'packs.php' ? 'active' : ''; ?>" href="packs.php">
                        <i class="fas fa-box"></i>Packs
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'options.php' ? 'active' : ''; ?>" href="options.php">
                        <i class="fas fa-cog"></i>Options
                    </a>
                </li>
                
                <li class="nav-item mt-3">
                    <small class="text-muted text-uppercase px-3">Ventes & Support</small>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'transactions.php' ? 'active' : ''; ?>" href="transactions.php">
                        <i class="fas fa-euro-sign"></i>Transactions
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'litiges.php' ? 'active' : ''; ?>" href="litiges.php">
                        <i class="fas fa-exclamation-triangle"></i>Litiges
                        <?php
                        // Compteur de litiges actifs
                        $stmt = $pdo->query("SELECT COUNT(*) as total FROM litiges WHERE statut IN ('ouvert', 'en_cours')");
                        $nb_litiges_actifs = $stmt->fetch()['total'];
                        if ($nb_litiges_actifs > 0):
                        ?>
                            <span class="badge bg-danger ms-2"><?php echo $nb_litiges_actifs; ?></span>
                        <?php endif; ?>
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
            </ul>
        </nav>
        
        <!-- Main Content -->
        <div class="content-wrapper" style="margin-left: 250px; width: calc(100% - 250px);">
            <!-- Top Navbar -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
                <div class="container-fluid">
                    <h5 class="mb-0"><?php echo e($page_title); ?></h5>
                    <div class="d-flex align-items-center">
                        <div class="dropdown">
                            <button class="btn btn-link text-dark position-relative" type="button" data-bs-toggle="dropdown">
                                <i class="fas fa-bell fa-lg"></i>
                                <span class="notification-badge">3</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><h6 class="dropdown-header">Notifications</h6></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-user text-primary me-2"></i>Nouveau client inscrit</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-euro-sign text-success me-2"></i>Nouvelle transaction</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-exclamation-triangle text-warning me-2"></i>Nouveau litige</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>
            
            <!-- Page Content -->
            <div class="container-fluid p-4">