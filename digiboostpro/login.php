<?php
/**
 * Page de connexion unique pour tous les rôles
 */
require_once 'config.php';

// Activer l'affichage des erreurs pour le debug
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Si déjà connecté, redirection vers le dashboard approprié
if (isLoggedIn()) {
    redirectToDashboard();
}

$error = '';
$debug_info = '';

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validation côté serveur
    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs';
    } else {
        // Vérification dans la base de données
        $stmt = $pdo->prepare("SELECT id, email, password, role, nom, prenom, actif FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        // Debug : afficher les informations (à retirer en production)
        if ($user) {
            $debug_info = "✓ Utilisateur trouvé : " . $user['email'] . " (Rôle: " . $user['role'] . ")<br>";
            
            if ($user['actif'] != 1) {
                $error = 'Ce compte est désactivé';
                $debug_info .= "✗ Compte désactivé<br>";
            } else {
                $debug_info .= "✓ Compte actif<br>";
                
                // Vérification du mot de passe
                if (password_verify($password, $user['password'])) {
                    $debug_info .= "✓ Mot de passe correct<br>";
                    
                    // Connexion réussie
                    session_regenerate_id(true);
                    
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['nom'] = $user['nom'];
                    $_SESSION['prenom'] = $user['prenom'];
                    
                    $debug_info .= "✓ Session créée<br>";
                    $debug_info .= "Redirection vers : " ."digiboostpro/". $user['role'] . "/dashboard.php<br>";
                    
                    // Redirection
                    redirectToDashboard();
                } else {
                    $error = 'Mot de passe incorrect';
                    $debug_info .= "✗ Mot de passe incorrect<br>";
                    $debug_info .= "Hash stocké : " . substr($user['password'], 0, 20) . "...<br>";
                }
            }
        } else {
            $error = 'Aucun compte trouvé avec cet email';
            $debug_info = "✗ Email non trouvé : " . htmlspecialchars($email) . "<br>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-page">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-5">
                <div class="card shadow-lg">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <h1 class="h3 text-primary fw-bold"><?php echo SITE_NAME; ?></h1>
                            <p class="text-muted">Connectez-vous à votre espace</p>
                        </div>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo e($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($debug_info && isset($_GET['debug'])): ?>
                            <div class="alert alert-info">
                                <strong>Debug Info:</strong><br>
                                <?php echo $debug_info; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="" id="loginForm">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           placeholder="votre@email.com" required 
                                           value="<?php echo isset($_POST['email']) ? e($_POST['email']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Mot de passe</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           placeholder="••••••••" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                                        <i class="fas fa-eye" id="toggleIcon"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember">
                                <label class="form-check-label" for="remember">Se souvenir de moi</label>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="fas fa-sign-in-alt me-2"></i>Se connecter
                            </button>
                            
                            <div class="text-center">
                                <a href="index.php" class="text-decoration-none">
                                    <i class="fas fa-arrow-left me-1"></i>Retour à l'accueil
                                </a>
                            </div>
                        </form>
                        
                        <hr class="my-4">
                        
                        <div class="text-center text-muted small">
                            <p class="mb-2 fw-bold">Identifiants de test :</p>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <tr>
                                        <td class="text-start"><strong>Admin :</strong></td>
                                        <td class="text-start">admin@digiboostpro.fr</td>
                                    </tr>
                                    <tr>
                                        <td class="text-start"><strong>Conseiller :</strong></td>
                                        <td class="text-start">conseiller1@digiboostpro.fr</td>
                                    </tr>
                                    <tr>
                                        <td class="text-start"><strong>Client :</strong></td>
                                        <td class="text-start">client1@example.com</td>
                                    </tr>
                                </table>
                            </div>
                            <p class="mb-0"><em>Mot de passe : <strong>password123</strong></em></p>
                            <p class="mt-2 text-danger small">
                                <i class="fas fa-info-circle"></i> 
                                Problème de connexion ? 
                                <a href="?debug=1">Activer le mode debug</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>