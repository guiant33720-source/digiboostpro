<?php
/**
 * Paramètres et configuration système (Admin)
 */
require_once '../config.php';
requireRole(['admin']);

$pageTitle = 'Paramètres Système';
$message = '';

// Traitement des paramètres
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = '<div class="alert alert-error">Erreur de sécurité.</div>';
    } else {
        try {
            // Créer une table de paramètres si elle n'existe pas
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS settings (
                    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    setting_key VARCHAR(100) NOT NULL UNIQUE,
                    setting_value TEXT,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            
            // Sauvegarder les paramètres
            $settings = ['site_name', 'site_email', 'maintenance_mode', 'max_login_attempts'];
            foreach ($settings as $key) {
                if (isset($_POST[$key])) {
                    $value = $_POST[$key];
                    $stmt = $pdo->prepare("
                        INSERT INTO settings (setting_key, setting_value) 
                        VALUES (?, ?) 
                        ON DUPLICATE KEY UPDATE setting_value = ?
                    ");
                    $stmt->execute([$key, $value, $value]);
                }
            }
            
            $message = '<div class="alert alert-success">Paramètres enregistrés avec succès.</div>';
        } catch (PDOException $e) {
            error_log("Erreur settings: " . $e->getMessage());
            $message = '<div class="alert alert-error">Erreur lors de la sauvegarde.</div>';
        }
    }
}

// Récupération des paramètres actuels
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
    $currentSettings = [];
    while ($row = $stmt->fetch()) {
        $currentSettings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    $currentSettings = [];
}

include '../header.php';
?>

<section class="dashboard">
    <div class="container">
        <h2>Paramètres Système</h2>
        
        <?php echo $message; ?>
        
        <div class="settings-form">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                
                <div class="form-group">
                    <label for="site_name">Nom du site</label>
                    <input 
                        type="text" 
                        id="site_name" 
                        name="site_name" 
                        class="form-control" 
                        value="<?php echo clean($currentSettings['site_name'] ?? 'DigiBoostPro'); ?>"
                    >
                </div>
                
                <div class="form-group">
                    <label for="site_email">Email de contact</label>
                    <input 
                        type="email" 
                        id="site_email" 
                        name="site_email" 
                        class="form-control" 
                        value="<?php echo clean($currentSettings['site_email'] ?? 'contact@digiboostpro.com'); ?>"
                    >
                </div>
                
                <div class="form-group">
                    <label for="max_login_attempts">Tentatives de connexion max</label>
                    <input 
                        type="number" 
                        id="max_login_attempts" 
                        name="max_login_attempts" 
                        class="form-control" 
                        min="3" 
                        max="10"
                        value="<?php echo clean($currentSettings['max_login_attempts'] ?? '5'); ?>"
                    >
                </div>
                
                <div class="form-group">
                    <label>
                        <input 
                            type="checkbox" 
                            name="maintenance_mode" 
                            value="1"
                            <?php echo isset($currentSettings['maintenance_mode']) && $currentSettings['maintenance_mode'] == '1' ? 'checked' : ''; ?>
                        >
                        Mode maintenance
                    </label>
                    <small style="display:block; color:#64748b;">Empêche les utilisateurs non-admin de se connecter</small>
                </div>
                
                <div class="dashboard-actions">
                    <button type="submit" class="btn btn-primary">💾 Enregistrer</button>
                    <a href="/admin/dashboard.php" class="btn btn-secondary">← Retour</a>
                </div>
            </form>
        </div>
        
        <div class="system-info" style="margin-top:40px;">
            <h3>Informations Système</h3>
            <div class="info-grid">
                <div class="info-item">
                    <strong>Version PHP :</strong> <?php echo phpversion(); ?>
                </div>
                <div class="info-item">
                    <strong>Serveur :</strong> <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'N/A'; ?>
                </div>
                <div class="info-item">
                    <strong>MySQL :</strong> <?php echo $pdo->query('SELECT VERSION()')->fetchColumn(); ?>
                </div>
                <div class="info-item">
                    <strong>Espace disque :</strong> 
                    <?php 
                    $freeSpace = disk_free_space("/");
                    $totalSpace = disk_total_space("/");
                    $usedSpace = $totalSpace - $freeSpace;
                    echo round($usedSpace / 1073741824, 2) . ' GB / ' . round($totalSpace / 1073741824, 2) . ' GB';
                    ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include '../footer.php'; ?>