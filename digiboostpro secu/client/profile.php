<?php
/**
 * Profil utilisateur client - Sécurisé
 * Version: 2.0 Secure
 */
require_once __DIR__ . '/../config.php';

// === VÉRIFICATION RÔLE CLIENT ===
requireRole(['client']);

$pageTitle = 'Mon Profil';
$userEmail = $_SESSION['user_email'];
$message = '';
$messageType = '';

// === TRAITEMENT DU FORMULAIRE ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // VÉRIFICATION CSRF
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Erreur de sécurité. Veuillez réessayer.';
        $messageType = 'error';
    } else {
        try {
            $pdo = getPdo();
            $name = trim($_POST['name'] ?? '');
            $phone = sanitizePhone($_POST['phone'] ?? '');
            
            // Validation
            if (empty($name)) {
                throw new Exception('Le nom est requis');
            }
            
            if (strlen($name) > 100) {
                throw new Exception('Le nom est trop long');
            }
            
            // Mise à jour du profil client
            $stmt = $pdo->prepare("UPDATE clients SET name = ?, phone = ? WHERE email = ?");
            $stmt->execute([$name, $phone, $userEmail]);
            
            // Mise à jour du nom en session
            $_SESSION['user_name'] = $name;
            
            // Changement de mot de passe si fourni
            if (!empty($_POST['new_password'])) {
                $currentPassword = $_POST['current_password'] ?? '';
                $newPassword = $_POST['new_password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';
                
                // Validation mot de passe
                if (empty($currentPassword)) {
                    throw new Exception('Veuillez entrer votre mot de passe actuel');
                }
                
                if (strlen($newPassword) < 8) {
                    throw new Exception('Le nouveau mot de passe doit contenir au moins 8 caractères');
                }
                
                if ($newPassword !== $confirmPassword) {
                    throw new Exception('Les mots de passe ne correspondent pas');
                }
                
                // Vérifier le mot de passe actuel
                $stmtUser = $pdo->prepare("SELECT password FROM users WHERE email = ?");
                $stmtUser->execute([$userEmail]);
                $user = $stmtUser->fetch();
                
                if (!$user || !password_verify($currentPassword, $user['password'])) {
                    throw new Exception('Mot de passe actuel incorrect');
                }
                
                // Hash et mise à jour du nouveau mot de passe
                $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmtPass = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
                $stmtPass->execute([$newPasswordHash, $userEmail]);
                
                // Logger l'action
                logActivity($_SESSION['user_id'], 'password_changed', 'Mot de passe modifié');
                
                $message = 'Profil et mot de passe mis à jour avec succès';
            } else {
                $message = 'Profil mis à jour avec succès';
            }
            
            $messageType = 'success';
            
            // Logger l'action
            logActivity($_SESSION['user_id'], 'profile_updated', 'Profil modifié');
            
        } catch (Exception $e) {
            $message = $e->getMessage();
            $messageType = 'error';
            error_log("Profile update error: " . $e->getMessage());
        }
    }
}

// === RÉCUPÉRATION DES INFORMATIONS CLIENT ===
try {
    $pdo = getPdo();
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE email = ?");
    $stmt->execute([$userEmail]);
    $client = $stmt->fetch();
    
    if (!$client) {
        die('Profil client non trouvé');
    }
} catch (PDOException $e) {
    error_log("Fetch profile error: " . $e->getMessage());
    die('Erreur lors du chargement du profil');
}

include __DIR__ . '/../header.php';
?>

<section class="dashboard">
    <div class="container">
        <h2>Mon Profil</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo e($messageType); ?>">
                <?php echo e($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="profile-form">
            <form method="POST">
                <?php echo csrfField(); ?>
                
                <h3>Informations Personnelles</h3>
                
                <div class="form-group">
                    <label for="name">Nom complet <span style="color:red;">*</span></label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        class="form-control" 
                        value="<?php echo e($client['name']); ?>" 
                        required
                        maxlength="100"
                    >
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        class="form-control" 
                        value="<?php echo e($client['email']); ?>" 
                        disabled
                    >
                    <small style="color: var(--gray-text);">L'email ne peut pas être modifié</small>
                </div>
                
                <div class="form-group">
                    <label for="phone">Téléphone</label>
                    <input 
                        type="tel" 
                        id="phone" 
                        name="phone" 
                        class="form-control" 
                        value="<?php echo e($client['phone'] ?? ''); ?>"
                        pattern="[0-9]{10}"
                        placeholder="0612345678"
                        maxlength="20"
                    >
                </div>
                
                <hr style="margin: 30px 0; border: none; border-top: 1px solid var(--border-color);">
                
                <h3>Changer le Mot de Passe</h3>
                <p style="color: var(--gray-text); margin-bottom: 20px;">Laissez vide si vous ne souhaitez pas modifier votre mot de passe</p>
                
                <div class="form-group">
                    <label for="current_password">Mot de passe actuel</label>
                    <input 
                        type="password" 
                        id="current_password" 
                        name="current_password" 
                        class="form-control"
                        autocomplete="current-password"
                        maxlength="255"
                    >
                </div>
                
                <div class="form-group">
                    <label for="new_password">Nouveau mot de passe</label>
                    <input 
                        type="password" 
                        id="new_password" 
                        name="new_password" 
                        class="form-control"
                        minlength="8"
                        autocomplete="new-password"
                        maxlength="255"
                    >
                    <small style="color: var(--gray-text);">Minimum 8 caractères</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirmer le nouveau mot de passe</label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        class="form-control"
                        autocomplete="new-password"
                        maxlength="255"
                    >
                </div>
                
                <div class="dashboard-actions">
                    <button type="submit" class="btn btn-primary">💾 Enregistrer les modifications</button>
                    <a href="<?php echo url('client/dashboard.php'); ?>" class="btn btn-secondary">← Retour</a>
                </div>
            </form>
        </div>
        
        <div class="account-info" style="margin-top: 40px;">
            <h3>Informations du Compte</h3>
            <div class="info-grid">
                <div class="info-item">
                    <strong>Statut :</strong> 
                    <span class="badge badge-<?php echo e($client['status']); ?>">
                        <?php echo e($client['status']); ?>
                    </span>
                </div>
                <div class="info-item">
                    <strong>Membre depuis :</strong> 
                    <?php echo date('d/m/Y', strtotime($client['created_at'])); ?>
                </div>
                <div class="info-item">
                    <strong>Dernière mise à jour :</strong> 
                    <?php echo date('d/m/Y à H:i', strtotime($client['updated_at'])); ?>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Validation côté client du formulaire
document.querySelector('form').addEventListener('submit', function(e) {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    const currentPassword = document.getElementById('current_password').value;
    
    if (newPassword || confirmPassword) {
        if (!currentPassword) {
            e.preventDefault();
            alert('Veuillez entrer votre mot de passe actuel pour en définir un nouveau.');
            return;
        }
        
        if (newPassword !== confirmPassword) {
            e.preventDefault();
            alert('Les mots de passe ne correspondent pas.');
            return;
        }
        
        if (newPassword.length < 8) {
            e.preventDefault();
            alert('Le nouveau mot de passe doit contenir au moins 8 caractères.');
            return;
        }
    }
});
</script>

<style>
.profile-form {
    background: var(--white);
    padding: 30px;
    border-radius: 12px;
    box-shadow: var(--shadow);
}

.profile-form h3 {
    color: var(--primary-color);
    margin-bottom: 20px;
}
</style>

<?php include __DIR__ . '/../footer.php'; ?>