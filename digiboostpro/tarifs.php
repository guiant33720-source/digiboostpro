<?php
/**
 * Page des tarifs et packs
 */
require_once 'config.php';
$page_title = 'Tarifs';

// Récupération des packs
$stmt = $pdo->query("SELECT * FROM packs WHERE actif = 1 ORDER BY prix ASC");
$packs = $stmt->fetchAll();

// Récupération des options
$stmt = $pdo->query("SELECT * FROM options_pack WHERE actif = 1 ORDER BY prix ASC");
$options = $stmt->fetchAll();

include 'header.php';
?>

<section class="page-header bg-primary text-white py-5">
    <div class="container text-center">
        <h1 class="display-4 fw-bold mb-3">Nos Tarifs</h1>
        <p class="lead">Des solutions adaptées à tous les budgets</p>
    </div>
</section>

<!-- Section Packs Fixes -->
<section class="packs-section py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold mb-3">Packs Fixes</h2>
            <p class="lead text-muted">Des offres complètes clés en main</p>
        </div>
        <div class="row g-4">
            <?php 
            $colors = ['primary', 'success', 'warning', 'info', 'danger'];
            $i = 0;
            foreach ($packs as $pack): 
                if ($pack['type'] === 'fixe'):
                    $color = $colors[$i % count($colors)];
                    $i++;
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 pack-card shadow border-<?php echo $color; ?>">
                    <div class="card-header bg-<?php echo $color; ?> text-white text-center py-3">
                        <h4 class="mb-0 fw-bold"><?php echo e($pack['nom']); ?></h4>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <div class="text-center mb-4">
                            <div class="price-tag">
                                <span class="h2 fw-bold"><?php echo number_format($pack['prix'], 0, ',', ' '); ?>€</span>
                                <span class="text-muted">/unique</span>
                            </div>
                        </div>
                        <p class="text-muted mb-4"><?php echo e($pack['description']); ?></p>
                        <div class="mt-auto">
                            <a href="contact.php?pack=<?php echo $pack['id']; ?>" class="btn btn-<?php echo $color; ?> w-100">
                                <i class="fas fa-shopping-cart me-2"></i>Commander
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php 
                endif;
            endforeach; 
            ?>
        </div>
    </div>
</section>

<!-- Section Packs Modulaires -->
<section class="modular-packs-section py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold mb-3">Packs Modulaires</h2>
            <p class="lead text-muted">Créez votre offre personnalisée</p>
        </div>
        <div class="row g-4">
            <?php foreach ($packs as $pack): 
                if ($pack['type'] === 'modulaire'):
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 pack-card shadow border-info">
                    <div class="card-header bg-info text-white text-center py-3">
                        <h4 class="mb-0 fw-bold"><?php echo e($pack['nom']); ?></h4>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <div class="text-center mb-4">
                            <div class="price-tag">
                                <span class="h2 fw-bold">À partir de <?php echo number_format($pack['prix'], 0, ',', ' '); ?>€</span>
                            </div>
                        </div>
                        <p class="text-muted mb-4"><?php echo e($pack['description']); ?></p>
                        <div class="mt-auto">
                            <a href="contact.php?pack=<?php echo $pack['id']; ?>" class="btn btn-info w-100">
                                <i class="fas fa-cog me-2"></i>Personnaliser
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php 
                endif;
            endforeach; 
            ?>
        </div>
    </div>
</section>

<!-- Section Options à la carte -->
<section class="options-section py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold mb-3">Options à la Carte</h2>
            <p class="lead text-muted">Complétez votre pack avec des services supplémentaires</p>
        </div>
        <div class="row g-4">
            <?php foreach ($options as $option): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 option-card shadow-sm border-0">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h5 class="card-title fw-bold mb-0"><?php echo e($option['nom']); ?></h5>
                            <span class="badge bg-primary fs-6"><?php echo number_format($option['prix'], 0, ',', ' '); ?>€</span>
                        </div>
                        <p class="card-text text-muted"><?php echo e($option['description']); ?></p>
                        <a href="contact.php?option=<?php echo $option['id']; ?>" class="btn btn-outline-primary btn-sm w-100">
                            <i class="fas fa-plus me-2"></i>Ajouter
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Section Chat Conseiller -->
<section class="chat-cta-section bg-primary text-white py-5">
    <div class="container text-center">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <i class="fas fa-comments fa-4x mb-4"></i>
                <h2 class="display-5 fw-bold mb-3">Besoin de conseils ?</h2>
<p class="lead mb-4">Discutez gratuitement avec l'un de nos conseillers pour trouver l'offre qui vous correspond</p>
<a href="contact.php?chat=1" class="btn btn-light btn-lg">
<i class="fas fa-headset me-2"></i>Démarrer un chat gratuit
</a>
</div>
</div>
</div>
</section>
<!-- Section Comparatif -->
<section class="comparison-section py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="display-5 fw-bold mb-3">Tableau Comparatif</h2>
            <p class="lead text-muted">Comparez nos différents packs</p>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover bg-white shadow-sm">
                <thead class="table-primary">
                    <tr>
                        <th>Fonctionnalités</th>
                        <th class="text-center">Starter</th>
                        <th class="text-center">Business</th>
                        <th class="text-center">Premium</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><i class="fas fa-check text-success me-2"></i>Site web responsive</td>
                        <td class="text-center"><i class="fas fa-check text-success"></i></td>
                        <td class="text-center"><i class="fas fa-check text-success"></i></td>
                        <td class="text-center"><i class="fas fa-check text-success"></i></td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-check text-success me-2"></i>Optimisation SEO de base</td>
                        <td class="text-center"><i class="fas fa-check text-success"></i></td>
                        <td class="text-center"><i class="fas fa-check text-success"></i></td>
                        <td class="text-center"><i class="fas fa-check text-success"></i></td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-check text-success me-2"></i>Formation utilisateur</td>
                        <td class="text-center"><i class="fas fa-times text-danger"></i></td>
                        <td class="text-center"><i class="fas fa-check text-success"></i></td>
                        <td class="text-center"><i class="fas fa-check text-success"></i></td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-check text-success me-2"></i>Campagnes publicitaires</td>
                        <td class="text-center"><i class="fas fa-times text-danger"></i></td>
                        <td class="text-center"><i class="fas fa-check text-success"></i></td>
                        <td class="text-center"><i class="fas fa-check text-success"></i></td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-check text-success me-2"></i>Support prioritaire 24/7</td>
                        <td class="text-center"><i class="fas fa-times text-danger"></i></td>
                        <td class="text-center"><i class="fas fa-times text-danger"></i></td>
                        <td class="text-center"><i class="fas fa-check text-success"></i></td>
                    </tr>
                    <tr>
                        <td><i class="fas fa-check text-success me-2"></i>Accompagnement personnalisé</td>
                        <td class="text-center"><i class="fas fa-times text-danger"></i></td>
                        <td class="text-center"><i class="fas fa-times text-danger"></i></td>
                        <td class="text-center"><i class="fas fa-check text-success"></i></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?php include 'footer.php'; ?>