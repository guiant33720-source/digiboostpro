/**
 * DigiBoostPro - Scripts JavaScript Premium
 */

document.addEventListener('DOMContentLoaded', function() {
    // ==================== MENU MOBILE ====================
    const mobileToggle = document.querySelector('.mobile-menu-toggle');
    const mainNav = document.querySelector('.main-nav');
    
    if (mobileToggle && mainNav) {
        mobileToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            mainNav.classList.toggle('active');
            this.classList.toggle('active');
            
            // Animation du hamburger
            const spans = this.querySelectorAll('span');
            if (this.classList.contains('active')) {
                spans[0].style.transform = 'rotate(45deg) translateY(8px)';
                spans[1].style.opacity = '0';
                spans[2].style.transform = 'rotate(-45deg) translateY(-8px)';
            } else {
                spans[0].style.transform = '';
                spans[1].style.opacity = '';
                spans[2].style.transform = '';
            }
        });
        
        // Fermer au clic sur un lien
        const navLinks = mainNav.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                mainNav.classList.remove('active');
                mobileToggle.classList.remove('active');
                const spans = mobileToggle.querySelectorAll('span');
                spans.forEach(span => span.style.transform = '');
                spans[1].style.opacity = '';
            });
        });
        
        // Fermer au clic extérieur
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.main-nav') && !event.target.closest('.mobile-menu-toggle')) {
                mainNav.classList.remove('active');
                mobileToggle.classList.remove('active');
                const spans = mobileToggle.querySelectorAll('span');
                spans.forEach(span => span.style.transform = '');
                spans[1].style.opacity = '';
            }
        });
    }
    
    // ==================== SMOOTH SCROLL ====================
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href !== '#') {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            }
        });
    });
    
    // ==================== ANIMATION DES COMPTEURS ====================
    function animateCounter(element, start, end, duration) {
        if (!element) return;
        
        const range = end - start;
        const increment = range / (duration / 16);
        let current = start;
        
        const timer = setInterval(() => {
            current += increment;
            if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
                current = end;
                clearInterval(timer);
            }
            element.textContent = Math.floor(current).toLocaleString('fr-FR');
        }, 16);
    }
    
    // Observer d'intersection pour les stats
    const statNumbers = document.querySelectorAll('.stat-content h3');
    if ('IntersectionObserver' in window && statNumbers.length > 0) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting && !entry.target.classList.contains('animated')) {
                    entry.target.classList.add('animated');
                    const targetValue = parseInt(entry.target.textContent.replace(/\s/g, '')) || 0;
                    entry.target.textContent = '0';
                    animateCounter(entry.target, 0, targetValue, 2000);
                }
            });
        }, { threshold: 0.5 });
        
        statNumbers.forEach(stat => observer.observe(stat));
    }
    
    // ==================== PARALLAX LÉGER SUR HERO ====================
    const hero = document.querySelector('.hero');
    if (hero) {
        window.addEventListener('scroll', function() {
            const scrolled = window.pageYOffset;
            const parallaxSpeed = 0.5;
            hero.style.transform = `translateY(${scrolled * parallaxSpeed}px)`;
        });
    }
    
    // ==================== GESTION DES ALERTES ====================
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        // Ajouter une icône selon le type
        const type = alert.classList.contains('alert-error') ? '❌' :
                     alert.classList.contains('alert-success') ? '✅' :
                     alert.classList.contains('alert-warning') ? '⚠️' : 'ℹ️';
        
        if (!alert.textContent.trim().startsWith(type)) {
            alert.innerHTML = `<span style="font-size:1.2rem;">${type}</span>` + alert.innerHTML;
        }
        
        // Auto-dismiss après 5 secondes
        setTimeout(() => {
            alert.style.opacity = '0';
            alert.style.transform = 'translateX(100px)';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });
    
    // ==================== CONFIRMATION DE SUPPRESSION ====================
    const deleteButtons = document.querySelectorAll('[data-confirm]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(event) {
            const message = this.getAttribute('data-confirm') || 'Êtes-vous sûr de vouloir effectuer cette action ?';
            if (!confirm(message)) {
                event.preventDefault();
            }
        });
    });
    
    // ==================== VALIDATION FORMULAIRES ====================
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        const requiredFields = form.querySelectorAll('[required]');
        
        form.addEventListener('submit', function(event) {
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = 'var(--danger-color)';
                    field.style.animation = 'shake 0.5s';
                } else {
                    field.style.borderColor = '';
                    field.style.animation = '';
                }
            });
            
            if (!isValid) {
                event.preventDefault();
                showNotification('Veuillez remplir tous les champs obligatoires', 'error');
            }
        });
        
        // Retirer l'erreur au focus
        requiredFields.forEach(field => {
            field.addEventListener('input', function() {
                if (this.value.trim()) {
                    this.style.borderColor = '';
                    this.style.animation = '';
                }
            });
        });
    });
    
    // ==================== TABLEAUX INTERACTIFS ====================
    const tables = document.querySelectorAll('.data-table');
    tables.forEach(table => {
        const rows = table.querySelectorAll('tbody tr[data-href]');
        rows.forEach(row => {
            row.style.cursor = 'pointer';
            row.addEventListener('click', function() {
                window.location.href = this.getAttribute('data-href');
            });
        });
    });
    
   // ==================== LOADING STATE POUR BOUTONS ====================
const submitButtons = document.querySelectorAll('button[type="submit"]');
submitButtons.forEach(button => {
    const form = button.closest('form');
    if (!form) return;
    
    // Écouter la soumission du formulaire, pas le clic
    form.addEventListener('submit', function(e) {
        // Vérifier que le formulaire est valide
        if (form.checkValidity()) {
            // Sauvegarder le texte original
            if (!button.dataset.originalText) {
                button.dataset.originalText = button.innerHTML;
            }
            
            // Désactiver le bouton et afficher le spinner
            button.disabled = true;
            button.innerHTML = '<span class="loading-spinner" style="width:20px;height:20px;border-width:3px;"></span> Chargement...';
            
            // Réactiver après 10 secondes en cas d'erreur (sécurité)
            setTimeout(() => {
                if (button.disabled) {
                    button.disabled = false;
                    button.innerHTML = button.dataset.originalText;
                }
            }, 10000);
        }
    });
});
    
    // ==================== STICKY HEADER ON SCROLL ====================
    const header = document.querySelector('.main-header');
    let lastScroll = 0;
    
    window.addEventListener('scroll', function() {
        const currentScroll = window.pageYOffset;
        
        if (currentScroll > 100) {
            header.style.boxShadow = 'var(--shadow-md)';
        } else {
            header.style.boxShadow = 'var(--shadow-sm)';
        }
        
        lastScroll = currentScroll;
    });
    
    // ==================== COPIE DANS PRESSE-PAPIERS ====================
    window.copyToClipboard = function(text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(() => {
                showNotification('Copié dans le presse-papiers', 'success');
            }).catch(err => {
                console.error('Erreur lors de la copie:', err);
            });
        } else {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            showNotification('Copié dans le presse-papiers', 'success');
        }
    };
    
    // ==================== NOTIFICATIONS ====================
    window.showNotification = function(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = 'notification';
        
        const icons = {
            success: '✅',
            error: '❌',
            warning: '⚠️',
            info: 'ℹ️'
        };
        
        const colors = {
            success: 'linear-gradient(135deg, #10b981, #059669)',
            error: 'linear-gradient(135deg, #ef4444, #dc2626)',
            warning: 'linear-gradient(135deg, #f59e0b, #d97706)',
            info: 'linear-gradient(135deg, #3b82f6, #2563eb)'
        };
        
        notification.innerHTML = `
            <span style="font-size:1.2rem;">${icons[type]}</span>
            <span>${message}</span>
        `;
        
        notification.style.cssText = `
            position: fixed;
            top: 100px;
            right: -400px;
            padding: 18px 24px;
            border-radius: 12px;
            background: ${colors[type]};
            color: white;
            font-weight: 600;
            box-shadow: var(--shadow-xl);
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 300px;
            max-width: 400px;
            transition: right 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        `;
        
        document.body.appendChild(notification);
        
        // Animation d'entrée
        setTimeout(() => {
            notification.style.right = '24px';
        }, 100);
        
        // Animation de sortie
        setTimeout(() => {
            notification.style.right = '-400px';
            setTimeout(() => notification.remove(), 400);
        }, 3500);
    };
    
    // ==================== ANIMATION AU DÉFILEMENT ====================
    const animatedElements = document.querySelectorAll('.feature-card, .stat-card');
    if ('IntersectionObserver' in window && animatedElements.length > 0) {
        const fadeObserver = new IntersectionObserver((entries) => {
            entries.forEach((entry, index) => {
                if (entry.isIntersecting) {
                    setTimeout(() => {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }, index * 100);
                    fadeObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        
        animatedElements.forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            fadeObserver.observe(el);
        });
    }
});

// ==================== ANIMATION SHAKE ====================
const style = document.createElement('style');
style.textContent = `
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
        20%, 40%, 60%, 80% { transform: translateX(5px); }
    }
`;
document.head.appendChild(style);