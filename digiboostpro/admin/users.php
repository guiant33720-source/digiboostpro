<?php
/**
 * Gestion des utilisateurs (tous rôles)
 */
require_once '../config.php';
requireLogin('admin');

$page_title = 'Gestion des Utilisateurs';

$success = '';
$error = '';

// Suppression d'un utilisateur
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Vérifier qu'on ne supprime pas l'admin connecté
    if ($id !== $_SESSION['user_id']) {
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $success = "Utilisateur supprimé avec succès";
        } catch (PDOException $e) {
            $error = "Erreur lors de la suppression : " . $e->getMessage();
        }
    } else {
        $error = "Vous ne pouvez pas supprimer votre propre compte";
    }
}

// Activation/Désactivation d'un utilisateur
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET actif = NOT actif WHERE id = ?");
        $stmt->execute([$id]);
        $success = "Statut modifié avec succès";
    } catch (PDOException $e) {
        $error = "Erreur lors de la modification";
    }
}

// Ajout/Modification d'un utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $email = trim($_POST['email']);
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $telephone = trim($_POST['telephone']);
    $role = $_POST['role'];
    $password = $_POST['password'] ?? '';
    $actif = isset($_POST['actif']) ? 1 : 0;
    
    // Validation
    if (empty($email) || empty($nom) || empty($prenom) || empty($role)) {
        $error = "Tous les champs obligatoires doivent être remplis";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email invalide";
    } else {
        try {
            if ($id) {
                // Modification
                if (!empty($password)) {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET email=?, nom=?, prenom=?, telephone=?, role=?, password=?, actif=? WHERE id=?");
                    $stmt->execute([$email, $nom, $prenom, $telephone, $role, $hash, $actif, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET email=?, nom=?, prenom=?, telephone=?, role=?, actif=? WHERE id=?");
                    $stmt->execute([$email, $nom, $prenom, $telephone, $role, $actif, $id]);
                }
                $success = "Utilisateur modifié avec succès";
            } else {
                // Ajout
                if (empty($password)) {
                    $error = "Le mot de passe est obligatoire pour un nouvel utilisateur";
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (email, password, nom, prenom, telephone, role, actif) VALUES (?,?,?,?,?,?,?)");
                    $stmt->execute([$email, $hash, $nom, $prenom, $telephone, $role, $actif]);
                    
                    $new_user_id = $pdo->lastInsertId();
                    
                    // Créer l'entrée correspondante selon le rôle
                    if ($role === 'client') {
                        $stmt = $pdo->prepare("INSERT INTO clients (user_id) VALUES (?)");
                        $stmt->execute([$new_user_id]);
                    } elseif ($role === 'conseiller') {
                        $stmt = $pdo->prepare("INSERT INTO conseillers (user_id) VALUES (?)");
                        $stmt->execute([$new_user_id]);
                    }
                    
                    $success = "Utilisateur créé avec succès";
                }
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "Cet email existe déjà";
            } else {
                $error = "Erreur : " . $e->getMessage();
            }
        }
    }
}

// Récupération de tous les utilisateurs
$stmt = $pdo->query("SELECT * FROM users ORDER BY role, nom, prenom");
$users = $stmt->fetchAll();

// Statistiques
$stmt = $pdo->query("SELECT role, COUNT(*) as total FROM users GROUP BY role");
$stats = [];
while ($row = $stmt->fetch()) {
    $stats[$row['role']] = $row['total'];
}

include 'includes/header.php';
?>

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

<!-- Statistiques -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-users fa-2x text-primary mb-2"></i>
                <h4 class="fw-bold"><?php echo array_sum($stats); ?></h4>
                <p class="text-muted mb-0">Total Utilisateurs</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-user-shield fa-2x text-danger mb-2"></i>
                <h4 class="fw-bold"><?php echo $stats['admin'] ?? 0; ?></h4>
                <p class="text-muted mb-0">Administrateurs</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-user-tie fa-2x text-warning mb-2"></i>
                <h4 class="fw-bold"><?php echo $stats['conseiller'] ?? 0; ?></h4>
                <p class="text-muted mb-0">Conseillers</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="fas fa-user fa-2x text-success mb-2"></i>
                <h4 class="fw-bold"><?php echo $stats['client'] ?? 0; ?></h4>
                <p class="text-muted mb-0">Clients</p>
            </div>
        </div>
    </div>
</div>

<!-- Actions -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-0"><i class="fas fa-users me-2"></i>Liste des Utilisateurs</h5>
            </div>
            <div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal" onclick="resetForm()">
                    <i class="fas fa-plus me-2"></i>Nouvel Utilisateur
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Tableau des utilisateurs -->
<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="mb-3">
            <input type="text" id="searchInput" class="form-control" placeholder="Rechercher un utilisateur...">
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover" id="usersTable">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Nom Complet</th>
                        <th>Email</th>
                        <th>Téléphone</th>
                        <th>Rôle</th>
                        <th>Statut</th>
                        <th>Date création</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['id']; ?></td>
                        <td class="fw-bold"><?php echo e($user['prenom'] . ' ' . $user['nom']); ?></td>
                        <td><?php echo e($user['email']); ?></td>
                        <td><?php echo e($user['telephone'] ?? '-'); ?></td>
                        <td>
                            <?php
                            $role_badges = [
                                'admin' => 'danger',
                                'conseiller' => 'warning',
                                'client' => 'success'
                            ];
                            ?>
                            <span class="badge bg-<?php echo $role_badges[$user['role']]; ?>">
                                <?php echo ucfirst($user['role']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($user['actif']): ?>
                                <span class="badge bg-success">Actif</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactif</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                        <td>
                            <div class="btn-group" role="group">
                                <button class="btn btn-sm btn-outline-primary" onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)" data-bs-toggle="modal" data-bs-target="#userModal">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <a href="?toggle=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-warning" onclick="return confirm('Changer le statut de cet utilisateur ?')">
                                    <i class="fas fa-power-off"></i>
                                </a>
                                <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                                <a href="?delete=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer cet utilisateur ?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Utilisateur -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="userForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Nouvel Utilisateur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="userId">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Prénom *</label>
                            <input type="text" class="form-control" name="prenom" id="prenom" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nom *</label>
                            <input type="text" class="form-control" name="nom" id="nom" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control" name="email" id="email" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Téléphone</label>
                            <input type="tel" class="form-control" name="telephone" id="telephone">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Rôle *</label>
                            <select class="form-select" name="role" id="role" required>
                                <option value="">Sélectionner...</option>
                                <option value="admin">Administrateur</option>
                                <option value="conseiller">Conseiller</option>
                                <option value="client">Client</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Mot de passe <span id="passwordRequired">*</span></label>
                            <input type="password" class="form-control" name="password" id="password">
                            <small class="text-muted" id="passwordHelp">Laisser vide pour ne pas modifier</small>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="actif" id="actif" checked>
                                <label class="form-check-label" for="actif">
                                    Compte actif
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function resetForm() {
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
    document.getElementById('modalTitle').textContent = 'Nouvel Utilisateur';
    document.getElementById('password').required = true;
    document.getElementById('passwordRequired').style.display = 'inline';
    document.getElementById('passwordHelp').style.display = 'none';
}

function editUser(user) {
    document.getElementById('userId').value = user.id;
    document.getElementById('prenom').value = user.prenom;
    document.getElementById('nom').value = user.nom;
    document.getElementById('email').value = user.email;
    document.getElementById('telephone').value = user.telephone || '';
    document.getElementById('role').value = user.role;
    document.getElementById('actif').checked = user.actif == 1;
    document.getElementById('password').required = false;
    document.getElementById('passwordRequired').style.display = 'none';
    document.getElementById('passwordHelp').style.display = 'inline';
    document.getElementById('modalTitle').textContent = 'Modifier l\'utilisateur';
}

// Recherche dans le tableau
DigiboostPro.initTableSearch('searchInput', 'usersTable');
</script>

<?php include 'includes/footer.php'; ?>