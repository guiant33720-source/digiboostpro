<?php
/**
 * Page de maintenance
 * Affichée lorsque le mode maintenance est activé
 */

// Permettre aux admins de passer (optionnel)
session_start();
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    // Les admins peuvent accéder normalement
    // header('Location: index.php');
    // exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="300"> <!-- Actualise toutes les 5 minutes -->
    <title>Maintenance en cours - DigiboostPro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }
        .maintenance-card {
            background: white;
            border-radius: 20px;
            padding: 50px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
            max-width: 700px;
            width: 100%;
        }
        .maintenance-icon {
            font-size: 100px;
            color: #667eea;
            margin-bottom: 30px;
            animation: rotate 4s linear infinite;
        }
        @keyframes rotate {
            0%, 100% { transform: rotate(0deg); }
            50% { transform: rotate(180deg); }
        }
        .progress {
            height: 30px;
            border-radius: 15px;
            overflow: hidden;
            background: #e9ecef;
        }
        .progress-bar {
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            animation: progress 3s ease-in-out infinite;
        }
        @keyframes progress {
            0% { width: 0%; }
            50% { width: 75%; }
            100% { width: 100%; }
        }
        .social-links a {
            display: inline-block;
            width: 50px;
            height: 50px;
            line-height: 50px;
            border-radius: 50%;
            background: #667eea;
            color: white;
            margin: 0 10px;
            transition: all 0.3s;
        }
        .social-links a:hover {
            background: #764ba2;
            transform: translateY(-5px);
        }
        .countdown {
            font-size: 48px;
            font-weight: bold;
            color: #667eea;
            margin: 20px 0;
        }
        .info-box {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 30px;
        }
        @media (max-width: 576px) {
            .maintenance-card {
                padding: 30px 20px;
            }
            .maintenance-icon {
                font-size: 70px;
            }
            .countdown {
                font-size: 32px;
            }
        }
    </style>
</head>
<body>
    <div class="maintenance-card">
        <i class="fas fa-cog maintenance-icon"></i>
        
        <h1 class="mb-3">🚧 Maintenance en cours</h1>
        <p class="lead mb-4">
            Nous effectuons actuellement des mises à jour pour améliorer votre expérience.
            Le site sera de retour très bientôt !
        </p>
        
        <div class="progress mb-4">
            <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"></div>
        </div>
        
        <div class="countdown" id="countdown">
            <i class="fas fa-clock"></i>
            <span id="timer">Retour imminent</span>
        </div>
        
        <div class="info-box">
            <h5 class="mb-3"><i class="fas fa-wrench me-2"></i>Ce que nous faisons :</h5>
            <div class="row text-start">
                <div class="col-md-6 mb-2">
                    <i class="fas fa-check text-success me-2"></i>Optimisation des performances
                </div>
                <div class="col-md-6 mb-2">
                    <i class="fas fa-check text-success me-2"></i>Mises à jour de sécurité
                </div>
                <div class="col-md-6 mb-2">
                    <i class="fas fa-check text-success me-2"></i>Nouvelles fonctionnalités
                </div>
                <div class="col-md-6 mb-2">
                    <i class="fas fa-check text-success me-2"></i>Corrections de bugs
                </div>
            </div>
        </div>
        
        <hr class="my-4">
        
        <h5 class="mb-3">Suivez-nous</h5>
        <div class="social-links mb-4">
            <a href="#" title="Facebook">
                <i class="fab fa-facebook-f"></i>
            </a>
            <a href="#" title="Twitter">
                <i class="fab fa-twitter"></i>
            </a>
            <a href="#" title="LinkedIn">
                <i class="fab fa-linkedin-in"></i>
            </a>
            <a href="#" title="Instagram">
                <i class="fab fa-instagram"></i>
            </a>
        </div>
        
        <div class="alert alert-info mb-0">
            <i class="fas fa-envelope me-2"></i>
            <strong>Besoin d'aide ?</strong><br>
            Contactez-nous : <a href="mailto:support@digiboostpro.fr" class="alert-link">support@digiboostpro.fr</a><br>
            <small class="text-muted">ou appelez-nous au +33 1 23 45 67 89</small>
        </div>
        
        <div class="mt-4">
            <small class="text-muted">
                © <?php echo date('Y'); ?> DigiboostPro - Tous droits réservés
            </small>
        </div>
    </div>
    
    <script>
        // Actualisation automatique toutes les 30 secondes
        setTimeout(function() {
            location.reload();
        }, 30000);
        
        // Compteur de temps estimé (optionnel)
        let minutes = 15; // Temps estimé en minutes
        let seconds = minutes * 60;
        
        function updateCountdown() {
            const mins = Math.floor(seconds / 60);
            const secs = seconds % 60;
            
            if (seconds > 0) {
                document.getElementById('timer').textContent = 
                    `Environ ${mins}:${secs.toString().padStart(2, '0')} restantes`;
                seconds--;
            } else {
                document.getElementById('timer').textContent = 'Retour imminent...';
            }
        }
        
        // Décommenter pour activer le compte à rebours
        // setInterval(updateCountdown, 1000);
        // updateCountdown();
    </script>
</body>
</html>