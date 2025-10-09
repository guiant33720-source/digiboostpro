<?php
/**
 * Page de contact
 */
require_once 'config.php';
$page_title = 'Contact';

$success = false;
$error = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $sujet = trim($_POST['sujet'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Validation
    if (empty($nom) || empty($prenom) || empty($email) || empty($sujet) || empty($message)) {
        $error = 'Veuillez remplir tous les champs obligatoires';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email invalide';
    } else {
        // Ici, vous pourriez envoyer un email ou sauvegarder dans la base
        // Pour la démo, on simule un succès
        $success = true;
        
        // Envoi d'email (exemple commenté)
        /*
        $to = CONTACT_EMAIL;
        $subject = "Nouveau message: " . $sujet;
        $body = "Nom: $nom $prenom\nEmail: $email\nTéléphone: $telephone\n\nMessage:\n$message";
        $headers = "From: $email\r\nReply-To: $email";
        mail($to, $subject, $body, $headers);
        */
    }
}

include 'header.php';
?>

<section class="page-header bg-primary text-white py-5">
    <div class="container text-center">
        <h1 class="display-4 fw-bold mb-3">Contactez-nous</h1>
        <p class="lead">Notre équipe est à votre écoute</p>
    </div>
</section>

<section class="contact-section py-5">
    <div class="container">
        <div class="row g-5">
            <!-- Formulaire de contact -->
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <h3 class="fw-bold mb-4">Envoyez-nous un message</h3>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="fas fa-check-circle me-2"></i>Votre message a été envoyé avec succès ! Nous vous répondrons dans les plus brefs délais.
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo e($error); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="nom" class="form-label">Nom *</label>
                                    <input type="text" class="form-control" id="nom" name="nom" required 
                                           value="<?php echo isset($_POST['nom']) ? e($_POST['nom']) : ''; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="prenom" class="form-label">Prénom *</label>
                                    <input type="text" class="form-control" id="prenom" name="prenom" required
                                           value="<?php echo isset($_POST['prenom']) ? e($_POST['prenom']) : ''; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" required
                                           value="<?php echo isset($_POST['email']) ? e($_POST['email']) : ''; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="telephone" class="form-label">Téléphone</label>
                                    <input type="tel" class="form-control" id="telephone" name="telephone"
                                           value="<?php echo isset($_POST['telephone']) ? e($_POST['telephone']) : ''; ?>">
                                </div>
                                <div class="col-12">
                                    <label for="sujet" class="form-label">Sujet *</label>
                                    <select class="form-select" id="sujet" name="sujet" required>
                                        <option value="">Choisissez un sujet</option>
                                        <option value="information">Demande d'information</option>
                                        <option value="devis">Demande de devis</option>
                                        <option value="support">Support technique</option>
                                        <option value="autre">Autre</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="message" class="form-label">Message *</label>
                                    <textarea class="form-control" id="message" name="message" rows="5" required><?php echo isset($_POST['message']) ? e($_POST['message']) : ''; ?></textarea>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-paper-plane me-2"></i>Envoyer le message
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Informations de contact -->
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-4">
                        <h4 class="fw-bold mb-4">Informations</h4>
                        <ul class="list-unstyled">
                            <li class="mb-3">
                                <div class="d-flex">
                                    <i class="fas fa-map-marker-alt text-primary me-3 mt-1"></i>
                                    <div>
                                        <strong>Adresse</strong><br>
                                        123 Avenue de la République<br>
                                        75011 Paris, France
                                    </div>
                                </div>
                            </li>
                            <li class="mb-3">
                                <div class="d-flex">
                                    <i class="fas fa-phone text-primary me-3 mt-1"></i>
                                    <div>
                                        <strong>Téléphone</strong><br>
                                        +33 1 23 45 67 89
                                    </div>
                                </div>
                            </li>
                            <li class="mb-3">
                                <div class="d-flex">
                                    <i class="fas fa-envelope text-primary me-3 mt-1"></i>
                                    <div>
                                        <strong>Email</strong><br>
                                        <?php echo CONTACT_EMAIL; ?>
                                    </div>
                                </div>
                            </li>
                            <li class="mb-3">
                                <div class="d-flex">
                                    <i class="fas fa-clock text-primary me-3 mt-1"></i>
                                    <div>
                                        <strong>Horaires</strong><br>
                                        Lun - Ven: 9h - 18h<br>
                                        Sam: 10h - 16h
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
                
                <div class="card shadow-sm border-0 bg-primary text-white">
                    <div class="card-body p-4 text-center">
                        <i class="fas fa-headset fa-3x mb-3"></i>
                        <h5 class="fw-bold mb-3">Chat en direct</h5>
                        <p class="mb-3">Discutez gratuitement avec un conseiller</p>
                        <button class="btn btn-light w-100">
                            <i class="fas fa-comments me-2"></i>Démarrer le chat
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Section Map -->
<section class="map-section py-0">
    <div class="container-fluid p-0">
        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d2624.9916256937595!2d2.370634615674895!3d48.858370079287466!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x47e66e2964e34e2d%3A0x8ddca9ee380ef7e0!2sEiffel%20Tower!5e0!3m2!1sen!2sfr!4v1234567890" 
                width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
    </div>
</section>

<?php include 'footer.php'; ?>