<?php
/**
 * Gestion des utilisateurs - Admin seulement
 * Version: 2.0 Secure
 */
require_once __DIR__ . '/../config.php';

// === VÉRIFICATION RÔLE ADMIN ===
requireRole(['admin']);

$pageTitle = 'Gestion des Utilisateurs';
$message = '';
$messageType = '';

// === TRAITEMENT DES ACTIONS ===
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // VÉRIFICATION CSRF
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $message = 'Erreur de sécurité. Veuillez réessayer.';
        $messageType = 'error';
    } else {
        $action = $_POST['action'] ?? '';
        
        try {
            $pdo = getPdo();
            
            if ($action === 'add') {
                // === AJOUT UTILISATEUR ===
                $name = trim($_POST['name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $role = $_POST['role'] ?? '';
                $status = $_POST['status'] ?? 'actif';
                
                // Validation
                if (empty($name) || empty($email) || empty($password) || empty($role)) {
                    throw new Exception('Tous les champs sont requis');
                }
                
                if (!isValidEmail($email)) {
                    throw new Exception('Format d\'email invalide');
                }
                
                if (strlen($password) < 8) {
                    throw new Exception('Le mot de passe doit contenir au moins 8 caractères');
                }
                
                if (!in_array($role, ['admin', 'conseiller', 'client'], true)) {
                    throw new Exception('Rôle invalide');
                }
                
                // Vérifier si l'email existe déjà
                $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $checkStmt->execute([$email]);
                if ($checkStmt->fetch()) {
                    throw new Exception('Cet email est déjà utilisé');
                }
                
                // Hash du mot de passe
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Insertion
                $stmt = $pdo->prepare("
                    INSERT INTO users (name, email, password, role, status) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$name, $email, $hashedPassword, $role, $status]);
                
                $message = 'Utilisateur ajouté avec succès';
                $messageType = 'success';
                
                // Logger l'action
                logActivity($_SESSION['user_id'], 'user_created', "Utilisateur créé: $email ($role)");
                
            } elseif ($action === 'update') {
                // === MODIFICATION UTILISATEUR ===
                $id = (int)$_POST['user_id'];
                $name = trim($_POST['name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $role = $_POST['role'] ?? '';
                $status = $_POST['status'] ?? 'actif';
                $password = $_POST['password'] ?? '';
                
                // Validation
                if (empty($name) || empty($email) || empty($role)) {
                    throw new Exception('Tous les champs sont requis');
                }
                
                if (!isValidEmail($email)) {
                    throw new Exception('Format d\'email invalide');
                }
                
                if (!in_array($role, ['admin', 'conseiller', 'client'], true)) {
                    throw new Exception('Rôle invalide');
                }
                
                // Vérifier si l'email existe déjà (sauf pour cet utilisateur)
                $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $checkStmt->execute([$email, $id]);
                if ($checkStmt->fetch()) {
                    throw new Exception('Cet email est déjà utilisé');
                }
                
                // Empêcher de se désactiver soi-même
                if ($id === $_SESSION['user_id'] && $status !== 'actif') {
                    throw new Exception('Vous ne pouvez pas désactiver votre propre compte');
                }
                
                // Mise à jour avec ou sans mot de passe
                if (!empty($password)) {
                    if (strlen($password) < 8) {
                        throw new Exception('Le mot de passe doit contenir au moins 8 caractères');
                    }
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET name = ?, email = ?, password = ?, role = ?, status = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$name, $email, $hashedPassword, $role, $status, $id]);
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE users 
                        SET name = ?, email = ?, role = ?, status = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$name, $email, $role, $status, $id]);
                }
                
                $message = 'Utilisateur modifié avec succès';
                $messageType = 'success';
                
                // Logger l'action
                logActivity($_SESSION['user_id'], 'user_updated', "Utilisateur modifié: $email");
                
            } elseif ($action === 'delete') {
                // === SUPPRESSION UTILISATEUR ===
                $id = (int)$_POST['user_id'];
                
                // Empêcher de se supprimer soi-même
                if ($id === $_SESSION['user_id']) {
                    throw new Exception('Vous ne pouvez pas supprimer votre propre compte');
                }
                
                // Récupérer l'email avant suppression pour le log
                $getUserStmt = $pdo->prepare("SELECT email FROM users WHERE id = ?");
                $getUserStmt->execute([$id]);
                $userToDelete = $getUserStmt->fetch();
                
                if (!$userToDelete) {
                    throw new Exception('Utilisateur non trouvé');
                }
                
                // Suppression
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$id]);
                
                $message = 'Utilisateur supprimé avec succès';
                $messageType = 'success';
                
                // Logger l'action
                logActivity($_SESSION['user_id'], 'user_deleted', "Utilisateur supprimé: " . $userToDelete['email']);
            }
            
        } catch (Exception $e) {
            $message = $e->getMessage();
            $messageType = 'error';
            error_log("Admin users error: " . $e->getMessage());
        }
    }
}

// === RÉCUPÉRATION DE TOUS LES UTILISATEURS ===
try {
    $pdo = getPdo();
    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Fetch users error: " . $e->getMessage());
    $users = [];
    $message = 'Erreur lors du chargement des utilisateurs';
    $messageType = 'error';
}

include __DIR__ . '/../header.php';
?>

<section class="dashboard">
    <div class="container">
        <h2>Gestion des Utilisateurs</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo e($messageType); ?>">
                <?php echo e($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="dashboard-actions">
            <button class="btn btn-primary" onclick="showAddModal()">➕ Ajouter un utilisateur</button>
            <a href="<?php echo url('admin/dashboard.php'); ?>" class="btn btn-secondary">← Retour au dashboard</a>
        </div>
        
        <?php if (count($users) > 0): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Rôle</th>
                            <th>Statut</th>
                            <th>Date de création</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo e($user['id']); ?></td>
                                <td><?php echo e($user['name']); ?></td>
                                <td><?php echo e($user['email']); ?></td>
                                <td><span class="badge badge-<?php echo e($user['role']); ?>"><?php echo e($user['role']); ?></span></td>
                                <td><span class="badge badge-<?php echo e($user['status']); ?>"><?php echo e($user['status']); ?></span></td>
                                <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-sm" onclick='editUser(<?php echo json_encode($user, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>✏️ Modifier</button>
                                    
                                    <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                        <form method="POST" style="display:inline;" onsubmit="return confirm('Confirmer la suppression ?')">
                                            <?php echo csrfField(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="user_id" value="<?php echo e($user['id']); ?>">
                                            <button type="submit" class="btn btn-sm" style="background:#ef4444;">🗑️ Supprimer</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="no-data">Aucun utilisateur trouvé.</p>
        <?php endif; ?>
    </div>
</section>

<!-- Modal Ajout/Modification -->
<div id="userModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; overflow-y:auto;">
    <div style="background:white; max-width:500px; margin:50px auto; padding:30px; border-radius:12px;">
        <h3 id="modalTitle">Ajouter un utilisateur</h3>
        <form method="POST" id="userForm">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="user_id" id="userId">
            
            <div class="form-group">
                <label>Nom complet <span style="color:red;">*</span></label>
                <input type="text" name="name" id="userName" class="form-control" required maxlength="100">
            </div>
            
            <div class="form-group">
                <label>Email <span style="color:red;">*</span></label>
                <input type="email" name="email" id="userEmail" class="form-control" required maxlength="150">
            </div>
            
            <div class="form-group">
                <label>Mot de passe <span id="passwordOptional" style="display:none;">(laisser vide pour ne pas modifier)</span><span id="passwordRequired" style="color:red;">*</span></label>
                <input type="password" name="password" id="userPassword" class="form-control" minlength="8" maxlength="255">
                <small style="color:#64748b;">Minimum 8 caractères</small>
            </div>
            
            <div class="form-group">
                <label>Rôle <span style="color:red;">*</span></label>
                <select name="role" id="userRole" class="form-control" required>
                    <option value="client">Client</option>
                    <option value="conseiller">Conseiller</option>
                    <option value="admin">Admin</option>
                </select>
            </div>
            
            <div class="form-group">
                <label>Statut <span style="color:red;">*</span></label>
                <select name="status" id="userStatus" class="form-control" required>
                    <option value="actif">Actif</option>
                    <option value="inactif">Inactif</option>
                </select>
            </div>
            
            <div style="display:flex; gap:10px; margin-top:20px;">
                <button type="submit" class="btn btn-primary">💾 Enregistrer</button>
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Annuler</button>
            </div>
        </form>
    </div>
</div>

<script>
function showAddModal() {
    document.getElementById('modalTitle').textContent = 'Ajouter un utilisateur';
    document.getElementById('formAction').value = 'add';
    document.getElementById('userForm').reset();
    document.getElementById('userPassword').required = true;
    document.getElementById('passwordOptional').style.display = 'none';
    document.getElementById('passwordRequired').style.display = 'inline';
    
    // Régénérer le token CSRF
    fetch('<?php echo url('ajax/refresh_csrf.php'); ?>')
        .then(r => r.json())
        .then(data => {
            if (data.token) {
                document.querySelector('input[name="csrf_token"]').value = data.token;
            }
        });
    
    document.getElementById('userModal').style.display = 'block';
}

function editUser(user) {
    document.getElementById('modalTitle').textContent = 'Modifier l\'utilisateur';
    document.getElementById('formAction').value = 'update';
    document.getElementById('userId').value = user.id;
    document.getElementById('userName').value = user.name;
    document.getElementById('userEmail').value = user.email;
    document.getElementById('userRole').value = user.role;
    document.getElementById('userStatus').value = user.status;
    document.getElementById('userPassword').value = '';
    document.getElementById('userPassword').required = false;
    document.getElementById('passwordOptional').style.display = 'inline';
    document.getElementById('passwordRequired').style.display = 'none';
    
    // Régénérer le token CSRF
    fetch('<?php echo url('ajax/refresh_csrf.php'); ?>')
        .then(r => r.json())
        .then(data => {
            if (data.token) {
                document.querySelector('input[name="csrf_token"]').value = data.token;
            }
        });
    
    document.getElementById('userModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('userModal').style.display = 'none';
}

// Fermer modal au clic extérieur
document.getElementById('userModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});
</script>

<?php include __DIR__ . '/../footer.php'; ?>