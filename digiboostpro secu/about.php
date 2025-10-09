<?php
$pageTitle = 'À propos';
require_once 'config.php';
include 'header.php';
?>

<section class="about-hero" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); color: white; padding: 80px 0; text-align: center;">
    <div class="container">
        <h2 style="font-size: 3rem; font-weight: 800; margin-bottom: 20px;">À propos de DigiBoostPro</h2>
        <p style="font-size: 1.3rem; opacity: 0.95; max-width: 700px; margin: 0 auto;">
            Votre partenaire de confiance pour la gestion professionnelle
        </p>
    </div>
</section>

<section style="padding: 80px 0;">
    <div class="container">
        <div style="max-width: 800px; margin: 0 auto;">
            <div style="background: white; padding: 48px; border-radius: var(--border-radius-lg); box-shadow: var(--shadow); margin-bottom: 40px;">
                <h3 style="color: var(--primary-color); font-size: 2rem; margin-bottom: 24px;">Notre Mission</h3>
                <p style="color: var(--gray-dark); line-height: 1.8; font-size: 1.05rem; margin-bottom: 20px;">
                    DigiBoostPro est une plateforme innovante conçue pour simplifier la gestion de vos clients, transactions et équipes. 
                    Notre objectif est de vous offrir des outils puissants et intuitifs pour optimiser votre productivité.
                </p>
                <p style="color: var(--gray-dark); line-height: 1.8; font-size: 1.05rem;">
                    Fondée sur les principes de sécurité, performance et expérience utilisateur, notre solution accompagne 
                    les entreprises dans leur transformation digitale.
                </p>
            </div>
            
            <div class="stats-grid" style="margin-top: 60px;">
                <div class="stat-card">
                    <div class="stat-icon">🚀</div>
                    <div class="stat-content">
                        <h3>2025</h3>
                        <p>Année de création</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">🔒</div>
                    <div class="stat-content">
                        <h3>100%</h3>
                        <p>Sécurisé</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">⚡</div>
                    <div class="stat-content">
                        <h3>24/7</h3>
                        <p>Disponibilité</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>