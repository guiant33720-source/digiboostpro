<?php
/**
 * Page de test complète pour DigiboostPro
 * Teste toutes les pages et fonctionnalités du projet
 */
require_once 'config.php';

// Vérifier si l'utilisateur est connecté
$is_logged = isLoggedIn();
$user_role = $_SESSION['role'] ?? 'guest';

// Test de connexion à la base de données
$db_status = 'OK';
$db_error = '';
try {
    $test = $pdo->query("SELECT 1");
} catch (PDOException $e) {
    $db_status = 'ERREUR';
    $db_error = $e->getMessage();
}

// Récupération des statistiques de la base
$stats = [];
try {
    $tables = ['users', 'clients', 'conseillers', 'packs', 'options_pack', 'transactions', 'litiges', 'messages', 'services'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM $table");
        $stats[$table] = $stmt->fetch()['total'];
    }
} catch (PDOException $e) {
    $stats['error'] = $e->getMessage();
}

// Test des fichiers
$files_to_check = [
    // Racine
    'config.php' => 'Configuration',
    'database.sql' => 'Base de données SQL',
    'login.php' => 'Page de connexion',
    'logout.php' => 'Déconnexion',
    'index.php' => 'Page d\'accueil',
    'tarifs.php' => 'Page tarifs',
    'actualites.php' => 'Page actualités',
    'contact.php' => 'Page contact',
    'header.php' => 'Header public',
    'footer.php' => 'Footer public',
    'style.css' => 'CSS principal',
    
    // JS
    'js/main.js' => 'JavaScript principal',
    
    // Admin
    'admin/dashboard.php' => 'Dashboard admin',
    'admin/stat.php' => 'Statistiques admin',
    'admin/users.php' => 'Gestion utilisateurs',
    'admin/clients.php' => 'Gestion clients',
    'admin/conseillers.php' => 'Gestion conseillers',
    'admin/packs.php' => 'Gestion packs',
    'admin/options.php' => 'Gestion options',
    'admin/transactions.php' => 'Gestion transactions',
    'admin/litiges.php' => 'Gestion litiges',
    'admin/ajax_get_transactions.php' => 'AJAX transactions',
    'admin/includes/header.php' => 'Header admin',
    'admin/includes/footer.php' => 'Footer admin',
    
    // Conseiller
    'conseiller/dashboard.php' => 'Dashboard conseiller',
    'conseiller/ajax_prendre_litige.php' => 'AJAX litige conseiller',
    
    // Client
    'client/dashboard.php' => 'Dashboard client',
    'client/profil.php' => 'Profil client',
];

$files_status = [];
foreach ($files_to_check as $file => $description) {
    $files_status[$file] = [
        'exists' => file_exists($file),
        'description' => $description,
        'size' => file_exists($file) ? filesize($file) : 0
    ];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Complet - DigiboostPro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        .test-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 20px;
        }
        .status-ok {
            color: #28a745;
        }
        .status-error {
            color: #dc3545;
        }
        .status-warning {
            color: #ffc107;
        }
        .test-section {
            padding: 20px;
            border-bottom: 1px solid #eee;
        }
        .test-section:last-child {
            border-bottom: none;
        }
        .file-item {
            padding: 5px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .file-item:last-child {
            border-bottom: none;
        }
        .badge-custom {
            padding: 5px 10px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="test-card">
                    <div class="text-center p-4 bg-primary text-white" style="border-radius: 10px 10px 0 0;">
                        <h1 class="mb-0"><i class="fas fa-vial me-2"></i>DigiboostPro - Test Complet</h1>
                        <p class="mb-0">Vérification de toutes les fonctionnalités du projet</p>
                    </div>
                    
                    <!-- Statut utilisateur -->
                    <div class="test-section">
                        <h4><i class="fas fa-user-circle me-2"></i>Statut de connexion</h4>
                        <div class="alert <?php echo $is_logged ? 'alert-success' : 'alert-warning'; ?>">
                            <?php if ($is_logged): ?>
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>Connecté en tant que :</strong> <?php echo e($_SESSION['prenom'] . ' ' . $_SESSION['nom']); ?> 
                                (<span class="badge bg-primary"><?php echo e($user_role); ?></span>)
                            <?php else: ?>
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Non connecté</strong> - 
                                <a href="login.php" class="alert-link">Se connecter pour tester toutes les fonctionnalités</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Test Base de données -->
                    <div class="test-section">
                        <h4><i class="fas fa-database me-2"></i>Base de données</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <p>
                                    <strong>Connexion :</strong> 
                                    <?php if ($db_status === 'OK'): ?>
                                        <span class="status-ok"><i class="fas fa-check-circle"></i> Connecté</span>
                                    <?php else: ?>
                                        <span class="status-error"><i class="fas fa-times-circle"></i> Erreur</span>
                                        <br><small class="text-danger"><?php echo e($db_error); ?></small>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Base :</strong> <?php echo DB_NAME; ?></p>
                                <p><strong>Hôte :</strong> <?php echo DB_HOST; ?></p>
                            </div>
                        </div>
                        
                        <?php if (!empty($stats) && !isset($stats['error'])): ?>
                        <h5 class="mt-3">Données en base :</h5>
                        <div class="row">
                            <?php foreach ($stats as $table => $count): ?>
                            <div class="col-md-3 col-sm-6 mb-2">
                                <div class="card">
                                    <div class="card-body text-center p-2">
                                        <h5 class="mb-0"><?php echo $count; ?></h5>
                                        <small class="text-muted"><?php echo ucfirst($table); ?></small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php elseif (isset($stats['error'])): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            Erreur lors de la récupération des statistiques : <?php echo e($stats['error']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Test Fichiers -->
                    <div class="test-section">
                        <h4><i class="fas fa-folder me-2"></i>Vérification des fichiers</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-primary">Fichiers présents :</h6>
                                <?php 
                                $present = array_filter($files_status, fn($f) => $f['exists']);
                                $missing = array_filter($files_status, fn($f) => !$f['exists']);
                                ?>
                                <div class="alert alert-success">
                                    <strong><?php echo count($present); ?></strong> fichiers trouvés sur <?php echo count($files_status); ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-danger">Fichiers manquants :</h6>
                                <?php if (count($missing) > 0): ?>
                                <div class="alert alert-warning">
                                    <strong><?php echo count($missing); ?></strong> fichiers manquants
                                </div>
                                <?php else: ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle"></i> Tous les fichiers sont présents !
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="accordion" id="filesAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#filesPresent">
                                        <i class="fas fa-check-circle text-success me-2"></i>
                                        Fichiers présents (<?php echo count($present); ?>)
                                    </button>
                                </h2>
                                <div id="filesPresent" class="accordion-collapse collapse" data-bs-parent="#filesAccordion">
                                    <div class="accordion-body">
                                        <?php foreach ($present as $file => $info): ?>
                                        <div class="file-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <i class="fas fa-file text-success me-2"></i>
                                                <strong><?php echo $file; ?></strong>
                                                <br><small class="text-muted ms-4"><?php echo $info['description']; ?></small>
                                            </div>
                                            <span class="badge bg-secondary"><?php echo number_format($info['size'] / 1024, 2); ?> KB</span>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (count($missing) > 0): ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#filesMissing">
                                        <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                                        Fichiers manquants (<?php echo count($missing); ?>)
                                    </button>
                                </h2>
                                <div id="filesMissing" class="accordion-collapse collapse" data-bs-parent="#filesAccordion">
                                    <div class="accordion-body">
                                        <?php foreach ($missing as $file => $info): ?>
                                        <div class="file-item">
                                            <i class="fas fa-times-circle text-danger me-2"></i>
                                            <strong><?php echo $file; ?></strong>
                                            <br><small class="text-muted ms-4"><?php echo $info['description']; ?></small>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Test des pages publiques -->
                    <div class="test-section">
                        <h4><i class="fas fa-globe me-2"></i>Pages publiques</h4>
                        <div class="row g-2">
                            <div class="col-md-3 col-sm-6">
                                <a href="index.php" class="btn btn-outline-primary w-100" target="_blank">
                                    <i class="fas fa-home me-2"></i>Accueil
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <a href="tarifs.php" class="btn btn-outline-primary w-100" target="_blank">
                                    <i class="fas fa-tags me-2"></i>Tarifs
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <a href="actualites.php" class="btn btn-outline-primary w-100" target="_blank">
                                    <i class="fas fa-newspaper me-2"></i>Actualités
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <a href="contact.php" class="btn btn-outline-primary w-100" target="_blank">
                                    <i class="fas fa-envelope me-2"></i>Contact
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Test des pages Admin -->
                    <?php if ($is_logged && $user_role === 'admin'): ?>
                    <div class="test-section">
                        <h4><i class="fas fa-user-shield me-2"></i>Pages Admin (Connecté)</h4>
                        <div class="row g-2">
                            <div class="col-md-3 col-sm-6">
                                <a href="admin/dashboard.php" class="btn btn-danger w-100" target="_blank">
                                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <a href="admin/stat.php" class="btn btn-danger w-100" target="_blank">
                                    <i class="fas fa-chart-line me-2"></i>Statistiques
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <a href="admin/users.php" class="btn btn-danger w-100" target="_blank">
                                    <i class="fas fa-users me-2"></i>Utilisateurs
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <a href="admin/clients.php" class="btn btn-danger w-100" target="_blank">
                                    <i class="fas fa-user-tie me-2"></i>Clients
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <a href="admin/conseillers.php" class="btn btn-danger w-100" target="_blank">
                                    <i class="fas fa-user-friends me-2"></i>Conseillers
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <a href="admin/packs.php" class="btn btn-danger w-100" target="_blank">
                                    <i class="fas fa-box me-2"></i>Packs
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <a href="admin/options.php" class="btn btn-danger w-100" target="_blank">
                                    <i class="fas fa-cog me-2"></i>Options
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <a href="admin/transactions.php" class="btn btn-danger w-100" target="_blank">
                                    <i class="fas fa-euro-sign me-2"></i>Transactions
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <a href="admin/litiges.php" class="btn btn-danger w-100" target="_blank">
                                    <i class="fas fa-exclamation-triangle me-2"></i>Litiges
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="test-section">
                        <h4><i class="fas fa-user-shield me-2"></i>Pages Admin</h4>
                        <div class="alert alert-warning">
                            <i class="fas fa-lock me-2"></i>
                            <strong>Connexion requise</strong> - Connectez-vous en tant qu'admin pour tester ces pages
                            <br><small>Email: admin@digiboostpro.fr | Mot de passe: password123</small>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Test des pages Conseiller -->
                    <?php if ($is_logged && $user_role === 'conseiller'): ?>
                    <div class="test-section">
                        <h4><i class="fas fa-user-tie me-2"></i>Pages Conseiller (Connecté)</h4>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <a href="conseiller/dashboard.php" class="btn btn-warning w-100" target="_blank">
                                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="test-section">
                        <h4><i class="fas fa-user-tie me-2"></i>Pages Conseiller</h4>
                        <div class="alert alert-warning">
                            <i class="fas fa-lock me-2"></i>
                            <strong>Connexion requise</strong> - Connectez-vous en tant que conseiller pour tester ces pages
                            <br><small>Email: conseiller1@digiboostpro.fr | Mot de passe: password123</small>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Test des pages Client -->
                    <?php if ($is_logged && $user_role === 'client'): ?>
                    <div class="test-section">
                        <h4><i class="fas fa-user me-2"></i>Pages Client (Connecté)</h4>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <a href="client/dashboard.php" class="btn btn-success w-100" target="_blank">
                                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                                </a>
                            </div>
                            <div class="col-md-4">
                                <a href="client/profil.php" class="btn btn-success w-100" target="_blank">
                                    <i class="fas fa-user-edit me-2"></i>Mon Profil
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="test-section">
                        <h4><i class="fas fa-user me-2"></i>Pages Client</h4>
                        <div class="alert alert-warning">
                            <i class="fas fa-lock me-2"></i>
                            <strong>Connexion requise</strong> - Connectez-vous en tant que client pour tester ces pages
                            <br><small>Email: client1@example.com | Mot de passe: password123</small>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Identifiants de test -->
                    <div class="test-section bg-light">
                        <h4><i class="fas fa-key me-2"></i>Identifiants de test</h4>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header bg-danger text-white">
                                        <strong>Administrateur</strong>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-1"><strong>Email:</strong> admin@digiboostpro.fr</p>
                                        <p class="mb-0"><strong>Mot de passe:</strong> password123</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header bg-warning text-white">
                                        <strong>Conseiller</strong>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-1"><strong>Email:</strong> conseiller1@digiboostpro.fr</p>
                                        <p class="mb-0"><strong>Mot de passe:</strong> password123</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header bg-success text-white">
                                        <strong>Client</strong>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-1"><strong>Email:</strong> client1@example.com</p>
                                        <p class="mb-0"><strong>Mot de passe:</strong> password123</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Résumé final -->
                    <div class="test-section text-center">
                        <h4><i class="fas fa-clipboard-check me-2"></i>Résumé du Test</h4>
                        <?php
                        $all_ok = ($db_status === 'OK' && count($missing) === 0);
                        ?>
                        <?php if ($all_ok): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle fa-3x mb-3"></i>
                            <h5>✅ Tous les tests sont passés avec succès !</h5>
                            <p class="mb-0">Le projet DigiboostPro est prêt à être utilisé.</p>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle fa-3x mb-3"></i>
                            <h5>⚠️ Certains tests ont échoué</h5>
                            <p class="mb-0">Vérifiez les sections ci-dessus pour corriger les problèmes.</p>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mt-4">
                            <?php if ($is_logged): ?>
                                <a href="logout.php" class="btn btn-danger btn-lg me-2">
                                    <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
                                </a>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-primary btn-lg me-2">
                                    <i class="fas fa-sign-in-alt me-2"></i>Se connecter
                                </a>
                            <?php endif; ?>
                            <a href="index.php" class="btn btn-secondary btn-lg">
                                <i class="fas fa-home me-2"></i>Retour à l'accueil
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>