<?php
/**
 * Page d'accueil publique
 */
require_once 'config.php';
$page_title = 'Accueil';

// Récupération des statistiques en temps réel
$stmt = $pdo->query("SELECT COUNT(*) as total FROM clients");
$nb_clients = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM transactions WHERE statut = 'payee'");
$nb_packs_vendus = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT SUM(montant_total) as total FROM transactions WHERE statut = 'payee'");
$ca_total = $stmt->fetch()['total'] ?? 0;

// Récupération des services
$stmt = $pdo->query("SELECT * FROM services WHERE actif = 1 ORDER BY ordre ASC");
$services = $stmt->fetchAll();

// Récupération des dernières actualités (on simulera ici)
$actualites = [
    [
        'titre' => 'Nouvelle offre E-commerce',
        'date' => '2025-09-20',
        'extrait' => 'Découvrez notre nouvelle solution complète pour créer votre boutique en ligne.',
        'image' => 'https://via.placeholder.com/400x250/007bff/ffffff?text=E-commerce'
    ],
    [
        'titre' => 'Guide du SEO en 2025',
        'date' => '2025-09-15',
        'extrait' => 'Les meilleures pratiques pour optimiser votre référencement naturel.',
        'image' => 'https://via.placeholder.com/400x250/28a745/ffffff?text=SEO+2025'
    ],
    [
        'titre' => 'Réseaux sociaux : tendances',
        'date' => '2025-09-10',
        'extrait' => 'Comment maximiser votre présence sur les réseaux sociaux cette année.',
        'image' => 'https://via.placeholder.com/400x250/ffc107/ffffff?text=Social+Media'
    ]
];

include 'header.php';
?>

<!-- Section Hero -->
<section class="hero-section bg-primary text-white py-5">
    <div class="container py-5">
        <div class="row align-items-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <h1 class="display-4 fw-bold mb-3 animate-fade-in">Transformez votre présence digitale</h1>
                <p class="lead mb-4">Des solutions sur-mesure pour propulser votre entreprise vers le succès numérique</p>
                <div class="d-flex gap-3 flex-wrap">
                    <a href="tarifs.php" class="btn btn-light btn-lg">
                        <i class="fas fa-tags me-2"></i>Découvrir nos offres
                    </a>
                    <a href="contact.php" class="btn btn-outline-light btn-lg">
                        <i class="fas fa-envelope me-2"></i>Nous contacter
                    </a>
                </div>
            </div>
            <div class="col-lg-6">
                <img src="https://via.placeholder.com/600x400/ffffff/007bff?text=Digital+Success" 
                     alt="Digital Success" class="img-fluid rounded shadow-lg animate-slide-in">
            </div>
        </div>
    </div>
</section>

<!-- Section Statistiques -->
<section class="stats-section py-5 bg-light">
    <div class="container">
        <div class="row text-center">
            <div class="col-md-4 mb-4 mb-md-0">
                <div class="stat-card">
                    <i class="fas fa-users fa-3x text-primary mb-3"></i>
                    <h2 class="display-4 fw-bold counter" data-target="<?php echo $nb_clients; ?>">0</h2>
                    <p class="text-muted">Clients satisfaits</p>
                </div>
            </div>
            <div class="col-md-4 mb-4 mb-md-0">
                <div class="stat-card">
                    <i class="fas fa-box fa-3x text-success mb-3"></i>
                    <h2 class="display-4 fw-bold counter" data-target="<?php echo $nb_packs_vendus; ?>">0</h2>
                    <p class="text-muted">Packs vendus</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <i class="fas fa-euro-sign fa-3x text-warning mb-3"></i>
                    <h2 class="display-4 fw-bold counter" data-target="<?php echo number_format($ca_total, 0, '', ''); ?>">0</h2>
                    <p class="text-muted">Chiffre d'affaires (€)</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Section Services -->
<section class="services-section py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold mb-3">Nos Services</h2>
            <p class="lead text-muted">Des solutions complètes pour tous vos besoins digitaux</p>
        </div>
        <div class="row g-4">
            <?php foreach ($services as $service): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 service-card shadow-sm border-0">
                    <div class="card-body text-center p-4">
                        <div class="service-icon mb-3">
                            <i class="fas <?php echo e($service['icone']); ?> fa-3x text-primary"></i>
                        </div>
                        <h5 class="card-title fw-bold mb-3"><?php echo e($service['titre']); ?></h5>
                        <p class="card-text text-muted"><?php echo e($service['description']); ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Section CTA -->
<section class="cta-section bg-primary text-white py-5">
    <div class="container text-center py-4">
        <h2 class="display-5 fw-bold mb-3">Prêt à démarrer votre projet ?</h2>
        <p class="lead mb-4">Nos conseillers sont disponibles pour vous accompagner</p>
        <a href="tarifs.php" class="btn btn-light btn-lg me-3">
            <i class="fas fa-rocket me-2"></i>Choisir un pack
        </a>
        <a href="contact.php" class="btn btn-outline-light btn-lg">
            <i class="fas fa-comments me-2"></i>Chat gratuit avec un conseiller
        </a>
    </div>
</section>

<!-- Section Témoignages -->
<section class="testimonials-section py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold mb-3">Ils nous font confiance</h2>
            <p class="lead text-muted">Découvrez les retours de nos clients</p>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 testimonial-card shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                        </div>
                        <p class="card-text mb-3">"Service exceptionnel ! L'équipe DigiboostPro a transformé notre présence en ligne. Résultats visibles en quelques semaines."</p>
                        <div class="d-flex align-items-center">
                            <img src="https://via.placeholder.com/50/007bff/ffffff?text=PD" class="rounded-circle me-3" alt="Client">
                            <div>
                                <h6 class="mb-0 fw-bold">Pierre Durand</h6>
                                <small class="text-muted">CEO, Tech Solutions</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 testimonial-card shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                        </div>
                        <p class="card-text mb-3">"Un accompagnement personnalisé et des outils performants. Notre chiffre d'affaires a doublé en 6 mois !"</p>
                        <div class="d-flex align-items-center">
                            <img src="https://via.placeholder.com/50/28a745/ffffff?text=JM" class="rounded-circle me-3" alt="Client">
                            <div>
                                <h6 class="mb-0 fw-bold">Julie Moreau</h6>
                                <small class="text-muted">Fondatrice, Web Agency Pro</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 testimonial-card shadow-sm border-0">
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                        </div>
                        <p class="card-text mb-3">"Professionnalisme et réactivité. Le support est toujours disponible et les conseils sont précieux."</p>
                        <div class="d-flex align-items-center">
                            <img src="https://via.placeholder.com/50/ffc107/ffffff?text=TL" class="rounded-circle me-3" alt="Client">
                            <div>
                                <h6 class="mb-0 fw-bold">Thomas Laurent</h6>
                                <small class="text-muted">Directeur, Digital Start</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Section Actualités -->
<section class="news-section py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold mb-3">Dernières Actualités</h2>
            <p class="lead text-muted">Restez informé des dernières tendances digitales</p>
        </div>
        <div class="row g-4">
            <?php foreach (array_slice($actualites, 0, 3) as $actu): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 news-card shadow-sm border-0">
                    <img src="<?php echo e($actu['image']); ?>" class="card-img-top" alt="<?php echo e($actu['titre']); ?>">
                    <div class="card-body">
                        <div class="mb-2">
                            <small class="text-muted">
                                <i class="fas fa-calendar me-1"></i><?php echo date('d/m/Y', strtotime($actu['date'])); ?>
                            </small>
                        </div>
                        <h5 class="card-title fw-bold"><?php echo e($actu['titre']); ?></h5>
                        <p class="card-text text-muted"><?php echo e($actu['extrait']); ?></p>
                        <a href="actualites.php" class="btn btn-outline-primary btn-sm">
                            Lire la suite <i class="fas fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="actualites.php" class="btn btn-primary">
                <i class="fas fa-newspaper me-2"></i>Voir toutes les actualités
            </a>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>