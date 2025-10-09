## 9. Page Actualités

**Chemin : `actualites.php`**
```php
<?php
/**
 * Page des actualités (blog)
 */
require_once 'config.php';
$page_title = 'Actualités';

// Actualités fictives
$actualites = [
    [
        'id' => 1,
        'titre' => 'Nouvelle offre E-commerce',
        'date' => '2025-09-20',
        'categorie' => 'Produits',
        'auteur' => 'Sophie Martin',
        'extrait' => 'Découvrez notre nouvelle solution complète pour créer votre boutique en ligne avec tous les outils nécessaires.',
        'image' => 'https://via.placeholder.com/800x400/007bff/ffffff?text=E-commerce',
        'contenu' => 'Notre pack E-commerce inclut maintenant des fonctionnalités avancées...'
    ],
    [
        'id' => 2,
        'titre' => 'Guide du SEO en 2025',
        'date' => '2025-09-15',
        'categorie' => 'Conseils',
        'auteur' => 'Luc Bernard',
        'extrait' => 'Les meilleures pratiques pour optimiser votre référencement naturel et gagner en visibilité.',
        'image' => 'https://via.placeholder.com/800x400/28a745/ffffff?text=SEO+2025',
        'contenu' => 'Le référencement naturel évolue constamment...'
    ],
    [
        'id' => 3,
        'titre' => 'Réseaux sociaux : tendances 2025',
        'date' => '2025-09-10',
        'categorie' => 'Marketing',
        'auteur' => 'Marie Petit',
        'extrait' => 'Comment maximiser votre présence sur les réseaux sociaux cette année avec les dernières tendances.',
        'image' => 'https://via.placeholder.com/800x400/ffc107/ffffff?text=Social+Media',
        'contenu' => 'Les réseaux sociaux continuent d\'être un levier essentiel...'
    ],
    [
        'id' => 4,
        'titre' => 'Intelligence Artificielle et Marketing',
        'date' => '2025-09-05',
        'categorie' => 'Technologie',
        'auteur' => 'Sophie Martin',
        'extrait' => 'L\'IA révolutionne le marketing digital. Découvrez comment l\'intégrer à votre stratégie.',
        'image' => 'https://via.placeholder.com/800x400/dc3545/ffffff?text=IA+Marketing',
        'contenu' => 'L\'intelligence artificielle transforme la façon dont nous faisons du marketing...'
    ],
    [
        'id' => 5,
        'titre' => 'Optimisation des conversions',
        'date' => '2025-09-01',
        'categorie' => 'Conseils',
        'auteur' => 'Luc Bernard',
        'extrait' => '10 techniques éprouvées pour augmenter le taux de conversion de votre site web.',
        'image' => 'https://via.placeholder.com/800x400/6f42c1/ffffff?text=Conversions',
        'contenu' => 'Améliorer votre taux de conversion est crucial...'
    ],
    [
        'id' => 6,
        'titre' => 'Sécurité web : les essentiels',
        'date' => '2025-08-25',
        'categorie' => 'Sécurité',
        'auteur' => 'Marie Petit',
        'extrait' => 'Protégez votre site web et les données de vos clients avec ces bonnes pratiques essentielles.',
        'image' => 'https://via.placeholder.com/800x400/17a2b8/ffffff?text=Securite',
        'contenu' => 'La sécurité de votre site web ne doit jamais être négligée...'
    ]
];

include 'header.php';
?>

<section class="page-header bg-primary text-white py-5">
    <div class="container text-center">
        <h1 class="display-4 fw-bold mb-3">Actualités & Blog</h1>
        <p class="lead">Restez informé des dernières tendances du digital</p>
    </div>
</section>

<!-- Section Catégories -->
<section class="categories-section py-4 bg-light">
    <div class="container">
        <div class="d-flex flex-wrap gap-2 justify-content-center">
            <button class="btn btn-outline-primary active" data-category="all">Toutes</button>
            <button class="btn btn-outline-primary" data-category="Produits">Produits</button>
            <button class="btn btn-outline-primary" data-category="Conseils">Conseils</button>
            <button class="btn btn-outline-primary" data-category="Marketing">Marketing</button>
            <button class="btn btn-outline-primary" data-category="Technologie">Technologie</button>
            <button class="btn btn-outline-primary" data-category="Sécurité">Sécurité</button>
        </div>
    </div>
</section>

<!-- Section Articles -->
<section class="articles-section py-5">
    <div class="container">
        <div class="row g-4" id="articlesContainer">
            <?php foreach ($actualites as $article): ?>
            <div class="col-md-6 col-lg-4 article-item" data-category="<?php echo e($article['categorie']); ?>">
                <article class="card h-100 article-card shadow-sm border-0">
                    <img src="<?php echo e($article['image']); ?>" class="card-img-top" alt="<?php echo e($article['titre']); ?>">
                    <div class="card-body d-flex flex-column">
                        <div class="mb-2">
                            <span class="badge bg-primary"><?php echo e($article['categorie']); ?></span>
                        </div>
                        <h5 class="card-title fw-bold mb-3"><?php echo e($article['titre']); ?></h5>
                        <p class="card-text text-muted flex-grow-1"><?php echo e($article['extrait']); ?></p>
                        <div class="article-meta d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                            <div class="author-info">
                                <small class="text-muted">
                                    <i class="fas fa-user me-1"></i><?php echo e($article['auteur']); ?>
                                </small>
                            </div>
                            <div class="date-info">
                                <small class="text-muted">
                                    <i class="fas fa-calendar me-1"></i><?php echo date('d/m/Y', strtotime($article['date'])); ?>
                                </small>
                            </div>
                        </div>
                        <a href="#article-<?php echo $article['id']; ?>" class="btn btn-outline-primary btn-sm mt-3">
                            Lire la suite <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </article>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Section Newsletter -->
<section class="newsletter-section bg-primary text-white py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 text-center">
                <i class="fas fa-envelope-open fa-3x mb-3"></i>
                <h2 class="fw-bold mb-3">Inscrivez-vous à notre newsletter</h2>
                <p class="mb-4">Recevez nos derniers articles et conseils directement dans votre boîte mail</p>
                <form class="d-flex gap-2">
                    <input type="email" class="form-control form-control-lg" placeholder="Votre email" required>
                    <button type="submit" class="btn btn-light btn-lg">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>