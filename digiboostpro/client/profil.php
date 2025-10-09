<?php
require_once '../config.php';
requireLogin('client');

$page_title = 'Mon Profil';

$success = '';
$error = '';

// Récupération des infos utilisateur et client
$stmt = $pdo->prepare("
    SELECT u.*, c.entreprise, c.adresse, c.ville, c.code_postal, c.pays
    FROM users u
    LEFT JOIN clients c ON u.id = c.user_id
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Modification du profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $email = trim($_POST['email']);
    $telephone = trim($_POST['telephone']);
    $entreprise = trim($_POST['entreprise']);
    $adresse = trim($_POST['adresse']);
    $ville = trim($_POST['ville']);
    $code_postal = trim($_POST['code_postal']);
    $pays = trim($_POST['pays']);
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($nom) || empty($prenom) || empty($email)) {
        $error = "Les champs nom, prénom et email sont obligatoires";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email invalide";
    } elseif (!empty($new_password) && $new_password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas";
    } else {
        try {
            // Mise à jour des infos utilisateur
            if (!empty($new_password)) {
                $hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET nom=?, prenom=?, email=?, telephone=?, password=? WHERE id=?");
                $stmt->execute([$nom, $prenom, $email, $telephone, $hash, $_SESSION['user_id']]);
            } else {
                $stmt = $pdo->prepare("UPDATE users SET nom=?, prenom=?, email=?, telephone=? WHERE id=?");
                $stmt->execute([$nom, $prenom, $email, $telephone, $_SESSION['user_id']]);
            }
            
            // Mise à jour des infos client
            $stmt = $pdo->prepare("
                UPDATE clients 
                SET entreprise=?, adresse=?, ville=?, code_postal=?, pays=? 
                WHERE user_id=?
            ");
            $stmt->execute([$entreprise, $adresse, $ville, $code_postal, $pays, $_SESSION['user_id']]);
            
            // Mise à jour de la session
            $_SESSION['nom'] = $nom;
            $_SESSION['prenom'] = $prenom;
            $_SESSION['email'] = $email;
            
            $success = "Profil mis à jour avec succès";
            
            // Recharger les données
            $stmt = $pdo->prepare("
                SELECT u.*, c.entreprise, c.adresse, c.ville, c.code_postal, c.pays
                FROM users u
                LEFT JOIN clients c ON u.id = c.user_id
                WHERE u.id = ?
            ");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            
        } catch (PDOException $e) {
            $error = "Erreur lors de la mise à jour : " . $e->getMessage();
        }
    }
}

include '../admin/includes/header.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.sidebar .user-menu small');
    if (sidebar) sidebar.textContent = 'Client';
    
    const nav = document.querySelector('.sidebar .nav');
    if (nav) {
        nav.innerHTML = `
            <li class="nav-item">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="achats.php">
                    <i class="fas fa-shopping-bag"></i>Mes Achats
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="telechargements.php">
                    <i class="fas fa-download"></i>Téléchargements
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="profil.php">
                    <i class="fas fa-user-edit"></i>Mon Profil
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="litiges.php">
                    <i class="fas fa-exclamation-circle"></i>Support
                </a>
            </li>
            <li class="nav-item mt-4">
                <a class="nav-link" href="../index.php">
                    <i class="fas fa-globe"></i>Site public
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../tarifs.php">
                    <i class="fas fa-tags"></i>Nos offres
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-danger" href="../logout.php">
                    <i class="fas fa-sign-out-alt"></i>Déconnexion
                </a>
            </li>
        `;
    }
});
</script>

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

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Modifier mon profil</h5>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="">
                    <h6 class="fw-bold mb-3">Informations personnelles</h6>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nom *</label>
                            <input type="text" class="form-control" name="nom" value="<?php echo e($user['nom']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Prénom *</label>
                            <input type="text" class="form-control" name="prenom" value="<?php echo e($user['prenom']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control" name="email" value="<?php echo e($user['email']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" name="telephone" value="<?php echo e($user['telephone']); ?>">
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <h6 class="fw-bold mb-3">Informations entreprise</h6>
                    
                    <div class="mb-3">
                        <label class="form-label">Entreprise</label>
                        <input type="text" class="form-control" name="entreprise" value="<?php echo e($user['entreprise']); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Adresse</label>
                        <textarea class="form-control" name="adresse" rows="2"><?php echo e($user['adresse']); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ville</label>
                            <input type="text" class="form-control" name="ville" value="<?php echo e($user['ville']); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Code Postal</label>
                            <input type="text" class="form-control" name="code_postal" value="<?php echo e($user['code_postal']); ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Pays</label>
                            <input type="text" class="form-control" name="pays" value="<?php echo e($user['pays'] ?? 'France'); ?>">
                        </div>
                    </div>
                    
                    <hr class="my-4">
                    
                    <h6 class="fw-bold mb-3">Changer le mot de passe (optionnel)</h6>
                    
                    <div class="alert alert-info">
                        <small><i class="fas fa-info-circle me-2"></i>Laissez vide si vous ne souhaitez pas changer votre mot de passe</small>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nouveau mot de passe</label>
                            <input type="password" class="form-control" name="new_password" minlength="8">
                            <small class="text-muted">Minimum 8 caractères</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Confirmer le mot de passe</label>
                            <input type="password" class="form-control" name="confirm_password" minlength="8">
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Enregistrer les modifications
                        </button>
                        <a href="dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Annuler
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../admin/includes/footer.php'; ?>