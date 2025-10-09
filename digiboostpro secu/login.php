<?php
/**
 * Page de connexion sécurisée
 * Version: 2.0 Secure
 */
require_once 'config.php';

// Si déjà connecté, rediriger
if (isLogged()) {
    $role = $_SESSION['user_role'];
    redirect(match($role) {
        'admin' => 'admin/dashboard.php',
        'conseiller' => 'conseiller/dashboard.php',
        'client' => 'client/dashboard.php',
        default => 'index.php'
    });
}

$error = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification CSRF
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "Erreur de sécurité. Veuillez réessayer.";
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        
        // Validation basique
        if (empty($email) || empty($password)) {
            $error = "Veuillez remplir tous les champs.";
        } elseif (!isValidEmail($email)) {
            $error = "Format d'email invalide.";
        } else {
            // Protection brute force par IP
            if (!checkRateLimit($ip, MAX_LOGIN_ATTEMPTS, LOGIN_TIMEOUT)) {
                $error = "Trop de tentatives de connexion. Veuillez réessayer dans 15 minutes.";
                error_log("Brute force attempt from IP: $ip");
            } else {
                try {
                    $pdo = getPdo();
                    
                    // Recherche de l'utilisateur
                    $stmt = $pdo->prepare("
                        SELECT id, name, email, password, role, status 
                        FROM users 
                        WHERE email = ? 
                        LIMIT 1
                    ");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch();
                    
                    // Vérification du mot de passe avec password_verify()
                    if ($user && password_verify($password, $user['password'])) {
                        // Vérifier que le compte est actif
                        if ($user['status'] !== 'actif') {
                            $error = "Votre compte est désactivé. Contactez l'administrateur.";
                        } else {
                            // === CONNEXION RÉUSSIE ===
                            
                            // 1. Régénérer l'ID de session (protection session fixation)
                            session_regenerate_id(true);
                            
                            // 2. Générer un token d'authentification unique
                            $authToken = generateAuthToken();
                            
                            // 3. Stocker les informations en session
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['user_name'] = $user['name'];
                            $_SESSION['user_email'] = $user['email'];
                            $_SESSION['user_role'] = $user['role'];
                            $_SESSION['user_auth_token'] = $authToken;
                            $_SESSION['last_activity'] = time();
                            $_SESSION['login_time'] = time();
                            $_SESSION['login_ip'] = $ip;
                            
                            // 4. Logger la connexion
                            try {
                                $logStmt = $pdo->prepare("
                                    INSERT INTO login_logs (user_id, ip_address, user_agent, login_time) 
                                    VALUES (?, ?, ?, NOW())
                                ");
                                $logStmt->execute([
                                    $user['id'],
                                    $ip,
                                    $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
                                ]);
                            } catch (PDOException $e) {
                                error_log("Login log error: " . $e->getMessage());
                            }
                            
                            // 5. Logger l'activité
                            logActivity($user['id'], 'login', 'Connexion réussie');
                            
                            // 6. Nettoyer les tentatives de rate limit
                            try {
                                $cleanStmt = $pdo->prepare("DELETE FROM rate_limits WHERE identifier = ?");
                                $cleanStmt->execute([$ip]);
                            } catch (PDOException $e) {
                                // Silencieux
                            }
                            
                            // 7. Redirection selon le rôle
                            redirect(match($user['role']) {
                                'admin' => 'admin/dashboard.php',
                                'conseiller' => 'conseiller/dashboard.php',
                                'client' => 'client/dashboard.php',
                                default => 'index.php'
                            });
                        }
                    } else {
                        $error = "Email ou mot de passe incorrect.";
                        
                        // Logger la tentative échouée
                        error_log("Failed login attempt for email: $email from IP: $ip");
                    }
                } catch (PDOException $e) {
                    error_log("Login error: " . $e->getMessage());
                    $error = "Une erreur est survenue. Veuillez réessayer.";
                }
            }
        }
    }
}

$pageTitle = 'Connexion';
include 'header.php';
?>

<section class="login-section">
    <div class="container">
        <div class="login-box">
            <h2>Connexion</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo e($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-error">
                    <?php echo e($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="login-form">
                <?php echo csrfField(); ?>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        class="form-control" 
                        required 
                        autocomplete="email"
                        maxlength="150"
                        value="<?php echo isset($_POST['email']) ? e($_POST['email']) : ''; ?>"
                    >
                </div>
                
                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        required 
                        autocomplete="current-password"
                        maxlength="255"
                    >
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    Se connecter
                </button>
            </form>
            
            <div class="login-footer">
                <p><a href="<?php echo url('index.php'); ?>">← Retour à l'accueil</a></p>
            </div>
            
            <?php if (IS_DEV_MODE): ?>
            <div style="margin-top:30px; padding:15px; background:#f0f9ff; border-radius:8px; font-size:13px;">
                <strong>📋 Comptes de test :</strong><br>
                <strong>Admin :</strong> admin@digiboostpro.com / password123<br>
                <strong>Conseiller :</strong> conseiller1@digiboostpro.com / password123<br>
                <strong>Client :</strong> client1@example.com / password123
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>