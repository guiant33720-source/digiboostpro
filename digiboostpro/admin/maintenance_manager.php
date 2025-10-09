<?php
/**
 * Gestionnaire de maintenance - Admin
 * Permet d'activer/désactiver le mode maintenance
 */
require_once '../config.php';
requireLogin('admin');

$page_title = 'Gestion de la Maintenance';

$success = '';
$error = '';

// Chemins des fichiers
$htaccess_file = dirname(__DIR__) . '/.htaccess';
$htaccess_backup = dirname(__DIR__) . '/.htaccess.backup';

// Vérifier si le mode maintenance est actif
$maintenance_active = false;
if (file_exists($htaccess_file)) {
    $htaccess_content = file_get_contents($htaccess_file);
    $maintenance_active = (strpos($htaccess_content, 'RewriteRule ^(.*)$ /maintenance.php') !== false && 
                          strpos($htaccess_content, '# RewriteRule ^(.*)$ /maintenance.php') === false);
}

// Activation/Désactivation de la maintenance
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_maintenance'])) {
        try {
            if (!file_exists($htaccess_file)) {
                $error = "Fichier .htaccess introuvable !";
            } else {
                // Lire le contenu actuel
                $content = file_get_contents($htaccess_file);
                
                if ($maintenance_active) {
                    // DÉSACTIVER la maintenance - commenter les lignes
                    $content = str_replace(
                        "# ========================================\n# MODE MAINTENANCE (décommenter pour activer)\n# ========================================\n<IfModule mod_rewrite.c>\n    RewriteEngine On\n    RewriteCond %{REQUEST_URI} !^/maintenance\.php$\n    RewriteCond %{REQUEST_URI} !^/admin/maintenance_manager\.php$\n    RewriteCond %{REMOTE_ADDR} !^123\.456\.789\.0$\n    RewriteRule ^(.*)$ /maintenance.php [R=302,L]\n</IfModule>",
                        "# ========================================\n# MODE MAINTENANCE (décommenter pour activer)\n# ========================================\n# <IfModule mod_rewrite.c>\n#     RewriteEngine On\n#     RewriteCond %{REQUEST_URI} !^/maintenance\.php$\n#     RewriteCond %{REQUEST_URI} !^/admin/maintenance_manager\.php$\n#     RewriteCond %{REMOTE_ADDR} !^123\.456\.789\.0$\n#     RewriteRule ^(.*)$ /maintenance.php [R=302,L]\n# </IfModule>",
                        $content
                    );
                    $success = "Mode maintenance DÉSACTIVÉ avec succès !";
                } else {
                    // ACTIVER la maintenance - décommenter les lignes
                    $content = str_replace(
                        "# ========================================\n# MODE MAINTENANCE (décommenter pour activer)\n# ========================================\n# <IfModule mod_rewrite.c>\n#     RewriteEngine On\n#     RewriteCond %{REQUEST_URI} !^/maintenance\.php$\n#     RewriteCond %{REQUEST_URI} !^/admin/maintenance_manager\.php$\n#     RewriteCond %{REMOTE_ADDR} !^123\.456\.789\.0$\n#     RewriteRule ^(.*)$ /maintenance.php [R=302,L]\n# </IfModule>",
                        "# ========================================\n# MODE MAINTENANCE (décommenter pour activer)\n# ========================================\n<IfModule mod_rewrite.c>\n    RewriteEngine On\n    RewriteCond %{REQUEST_URI} !^/maintenance\.php$\n    RewriteCond %{REQUEST_URI} !^/admin/maintenance_manager\.php$\n    RewriteCond %{REMOTE_ADDR} !^123\.456\.789\.0$\n    RewriteRule ^(.*)$ /maintenance.php [R=302,L]\n</IfModule>",
                        $content
                    );
                    
                    // Backup avant activation
                    file_put_contents($htaccess_backup, file_get_contents($htaccess_file));
                    
                    $success = "Mode maintenance ACTIVÉ avec succès !";
                }
                
                // Écrire le nouveau contenu
                file_put_contents($htaccess_file, $content);
                
                // Recharger l'état
                $htaccess_content = $content;
                $maintenance_active = !$maintenance_active;
            }
        } catch (Exception $e) {
            $error = "Erreur : " . $e->getMessage();
        }
    }
}

// Informations système
$server_info = [
    'PHP Version' => phpversion(),
    'Serveur' => $_SERVER['SERVER_SOFTWARE'] ?? 'Inconnu',
    'OS' => PHP_OS,
    'Max Upload' => ini_get('upload_max_filesize'),
    'Max Post' => ini_get('post_max_size'),
    'Memory Limit' => ini_get('memory_limit'),
    'Time Limit' => ini_get('max_execution_time') . 's'
];

include 'includes/header.php';
?>

<!-- Statut du mode maintenance -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm <?php echo $maintenance_active ? 'border-danger' : 'border-success'; ?>">
            <div class="card-body text-center py-5">
                <?php if ($maintenance_active): ?>
                    <i class="fas fa-exclamation-triangle fa-4x text-danger mb-3"></i>
                    <h2 class="text-danger mb-3">⚠️ MODE MAINTENANCE ACTIF</h2>
                    <p class="lead mb-4">Le site est actuellement en maintenance pour les visiteurs.</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle me-2"></i>
                        Seuls les administrateurs peuvent accéder au site. Les visiteurs voient la page de maintenance.
                    </div>
                <?php else: ?>
                    <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                    <h2 class="text-success mb-3">✅ SITE EN LIGNE</h2>
                    <p class="lead mb-4">Le site est accessible à tous les visiteurs.</p>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Vous pouvez activer le mode maintenance pour effectuer des mises à jour.
                    </div>
                <?php endif; ?>
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

<!-- Actions -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-toggle-on me-2"></i>Contrôle de la Maintenance</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="maintenanceForm">
                    <input type="hidden" name="toggle_maintenance" value="1">
                    
                    <div class="mb-4">
                        <h6>État actuel :</h6>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="maintenanceSwitch" 
                                   <?php echo $maintenance_active ? 'checked' : ''; ?> disabled>
                            <label class="form-check-label" for="maintenanceSwitch">
                                <strong><?php echo $maintenance_active ? 'Activé' : 'Désactivé'; ?></strong>
                            </label>
                        </div>
                    </div>
                    
                    <?php if ($maintenance_active): ?>
                        <button type="submit" class="btn btn-success btn-lg w-100" 
                                onclick="return confirm('Désactiver le mode maintenance ? Le site sera accessible à tous.')">
                            <i class="fas fa-check-circle me-2"></i>Désactiver la Maintenance
                        </button>
                        <p class="text-muted mt-3 mb-0 small">
                            <i class="fas fa-info-circle me-1"></i>
                            Le site redeviendra accessible à tous les visiteurs.
                        </p>
                    <?php else: ?>
                        <button type="submit" class="btn btn-warning btn-lg w-100" 
                                onclick="return confirm('Activer le mode maintenance ? Les visiteurs verront la page de maintenance.')">
                            <i class="fas fa-exclamation-triangle me-2"></i>Activer la Maintenance
                        </button>
                        <p class="text-muted mt-3 mb-0 small">
                            <i class="fas fa-info-circle me-1"></i>
                            Seuls les administrateurs pourront accéder au site.
                        </p>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-eye me-2"></i>Aperçu</h5>
            </div>
            <div class="card-body">
                <h6 class="mb-3">Actions rapides :</h6>
                
                <a href="../maintenance.php" class="btn btn-outline-primary w-100 mb-2" target="_blank">
                    <i class="fas fa-external-link-alt me-2"></i>Voir la page de maintenance
                </a>
                
                <a href="../index.php" class="btn btn-outline-success w-100 mb-2" target="_blank">
                    <i class="fas fa-home me-2"></i>Voir le site public
                </a>
                
                <?php if (file_exists($htaccess_backup)): ?>
                <div class="alert alert-info mt-3 mb-0">
                    <i class="fas fa-save me-2"></i>
                    <strong>Backup disponible</strong><br>
                    <small>Un backup du .htaccess a été créé avant la dernière activation.</small>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Informations système -->
<div class="row g-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="fas fa-server me-2"></i>Informations Système</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <?php foreach ($server_info as $key => $value): ?>
                    <tr>
                        <td class="fw-bold"><?php echo e($key); ?></td>
                        <td><?php echo e($value); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-warning text-white">
                <h5 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Avertissements</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-warning mb-3">
                    <strong><i class="fas fa-info-circle me-2"></i>Avant d'activer la maintenance :</strong>
                    <ul class="mb-0 mt-2">
                        <li>Prévenez les utilisateurs connectés</li>
                        <li>Sauvegardez la base de données</li>
                        <li>Vérifiez que la page maintenance.php fonctionne</li>
                    </ul>
                </div>
                
                <div class="alert alert-info mb-0">
                    <strong><i class="fas fa-lightbulb me-2"></i>Astuce :</strong><br>
                    Vous pouvez personnaliser la page de maintenance en éditant le fichier <code>maintenance.php</code>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Guide -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="fas fa-book me-2"></i>Guide d'utilisation</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary">Quand activer la maintenance ?</h6>
                        <ul>
                            <li>Lors des mises à jour majeures</li>
                            <li>Pendant les migrations de base de données</li>
                            <li>Pour les interventions techniques importantes</li>
                            <li>En cas de problème de sécurité</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-success">Fonctionnement</h6>
                        <ul>
                            <li>Le fichier .htaccess redirige tous les visiteurs</li>
                            <li>Les administrateurs peuvent toujours accéder</li>
                            <li>La page maintenance.php s'affiche pour les autres</li>
                            <li>Un backup est créé automatiquement</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>