<?php
require_once 'config.php';
requireLogin('admin');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test des boutons</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Test des liens et boutons</h2>
        
        <div class="card mt-4">
            <div class="card-header">
                <h5>Pages Admin</h5>
            </div>
            <div class="card-body">
                <a href="admin/dashboard.php" class="btn btn-primary m-1">Dashboard</a>
                <a href="admin/stat.php" class="btn btn-primary m-1">Statistiques</a>
                <a href="admin/users.php" class="btn btn-primary m-1">Utilisateurs</a>
                <a href="admin/clients.php" class="btn btn-primary m-1">Clients</a>
                <a href="admin/conseillers.php" class="btn btn-primary m-1">Conseillers</a>
                <a href="admin/packs.php" class="btn btn-primary m-1">Packs</a>
                <a href="admin/options.php" class="btn btn-primary m-1">Options</a>
                <a href="admin/transactions.php" class="btn btn-primary m-1">Transactions</a>
                <a href="admin/litiges.php" class="btn btn-primary m-1">Litiges</a>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h5>Pages Conseiller</h5>
            </div>
            <div class="card-body">
                <a href="conseiller/dashboard.php" class="btn btn-warning m-1">Dashboard Conseiller</a>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h5>Pages Client</h5>
            </div>
            <div class="card-body">
                <a href="client/dashboard.php" class="btn btn-success m-1">Dashboard Client</a>
                <a href="client/profil.php" class="btn btn-success m-1">Profil Client</a>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                <h5>Pages Publiques</h5>
            </div>
            <div class="card-body">
                <a href="index.php" class="btn btn-info m-1">Accueil</a>
                <a href="tarifs.php" class="btn btn-info m-1">Tarifs</a>
                <a href="actualites.php" class="btn btn-info m-1">Actualités</a>
                <a href="contact.php" class="btn btn-info m-1">Contact</a>
            </div>
        </div>
        
        <div class="mt-4">
            <a href="logout.php" class="btn btn-danger">Déconnexion</a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>