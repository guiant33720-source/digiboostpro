<?php
/**
 * Page d'accueil publique avec compteur de clients en AJAX
 */
require_once 'config.php';

$pageTitle = 'Accueil';
include 'header.php';
?>

<section class="hero">
    <div class="container">
        <div class="hero-content">
            <h2>Bienvenue sur DigiBoostPro</h2>
            <p class="hero-subtitle">Votre plateforme de gestion professionnelle</p>
            
            <div class="stats-box">
                <div class="stat-item">
                    <span class="stat-number" id="clientCount">
                        <span class="loading-spinner"></span>
                    </span>
                    <span class="stat-label">Clients satisfaits</span>
                </div>
            </div>
            
            <?php if (!isLogged()): ?>
                <div class="cta-buttons">
                    <a href="login.php" class="btn btn-primary">Se connecter</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="features">
    <div class="container">
        <h3>Nos Services</h3>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h4>Gestion Complète</h4>
                <p>Gérez tous vos clients et transactions en un seul endroit</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">🔒</div>
                <h4>Sécurité Maximale</h4>
                <p>Vos données sont protégées avec les dernières technologies</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">📱</div>
                <h4>Interface Responsive</h4>
                <p>Accessible sur tous vos appareils, partout et à tout moment</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">⚡</div>
                <h4>Rapide et Efficace</h4>
                <p>Des performances optimales pour une expérience fluide</p>
            </div>
        </div>
    </div>
</section>

<script>
// Chargement du compteur de clients via AJAX
document.addEventListener('DOMContentLoaded', function() {
    const countElement = document.getElementById('clientCount');
    
    fetch('/ajax/count_clients.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                animateCounter(countElement, data.count);
            } else {
                countElement.textContent = '--';
            }
        })
        .catch(error => {
            console.error('Erreur lors du chargement du compteur:', error);
            countElement.textContent = '--';
        });
});

function animateCounter(element, targetValue) {
    let currentValue = 0;
    const duration = 2000; // 2 secondes
    const increment = targetValue / (duration / 16); // 60 FPS
    
    const timer = setInterval(() => {
        currentValue += increment;
        if (currentValue >= targetValue) {
            currentValue = targetValue;
            clearInterval(timer);
        }
        element.textContent = Math.floor(currentValue);
    }, 16);
}
</script>

<?php include 'footer.php'; ?>