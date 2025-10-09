<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo isset($pageTitle) ? e($pageTitle) . ' - DigiBoostPro' : 'DigiBoostPro - Plateforme de Gestion'; ?></title>
    <link rel="stylesheet" href="<?php echo url('style.css'); ?>">
</head>
<body>
    <header class="main-header">
        <div class="container">
            <div class="header-content">
                <h1 class="logo">
                    <a href="<?php echo url('index.php'); ?>">DigiBoostPro</a>
                </h1>
                
                <nav class="main-nav">
                    <?php if (isLogged()): ?>
                        <span class="user-info">
                            Bonjour, <strong><?php echo e($_SESSION['user_name'] ?? 'Utilisateur'); ?></strong>
                            <span class="badge"><?php echo e($_SESSION['user_role']); ?></span>
                        </span>
                        
                        <?php if ($_SESSION['user_role'] === 'admin'): ?>
                            <a href="<?php echo url('admin/dashboard.php'); ?>" class="nav-link">Dashboard Admin</a>
                            <a href="<?php echo url('admin/users.php'); ?>" class="nav-link">Utilisateurs</a>
                        <?php elseif ($_SESSION['user_role'] === 'conseiller'): ?>
                            <a href="<?php echo url('conseiller/dashboard.php'); ?>" class="nav-link">Dashboard</a>
                        <?php elseif ($_SESSION['user_role'] === 'client'): ?>
                            <a href="<?php echo url('client/dashboard.php'); ?>" class="nav-link">Mon Espace</a>
                            <a href="<?php echo url('client/transactions.php'); ?>" class="nav-link">Transactions</a>
                            <a href="<?php echo url('client/profile.php'); ?>" class="nav-link">Profil</a>
                        <?php endif; ?>
                        
                        <a href="<?php echo url('logout.php'); ?>" class="nav-link btn-logout">Déconnexion</a>
                    <?php else: ?>
                        <a href="<?php echo url('index.php'); ?>" class="nav-link">Accueil</a>
                        <a href="<?php echo url('login.php'); ?>" class="nav-link btn-login">Connexion</a>
                    <?php endif; ?>
                </nav>
                
                <button class="mobile-menu-toggle" aria-label="Menu mobile">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
    </header>
    
    <main class="main-content">