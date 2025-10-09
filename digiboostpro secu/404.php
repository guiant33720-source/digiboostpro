<?php
$pageTitle = 'Page non trouvée';
require_once 'config.php';
include 'header.php';
?>

<section class="error-page" style="min-height: calc(100vh - 90px); display: flex; align-items: center; justify-content: center; text-align: center; padding: 60px 20px;">
    <div class="error-content">
        <div style="font-size: 8rem; font-weight: 900; background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; line-height: 1; margin-bottom: 20px;">
            404
        </div>
        <h2 style="font-size: 2rem; margin-bottom: 16px; color: var(--dark);">Page non trouvée</h2>
        <p style="color: var(--gray); margin-bottom: 32px; font-size: 1.1rem;">La page que vous recherchez n'existe pas ou a été déplacée.</p>
        <a href="<?php echo url('index.php'); ?>" class="btn btn-primary" style="display: inline-flex;">
            ← Retour à l'accueil
        </a>
    </div>
</section>

<?php include 'footer.php'; ?>