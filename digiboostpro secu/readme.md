# DigiBoostPro - Plateforme de Gestion

## 📋 Description

DigiBoostPro est une plateforme web complète de gestion de clients, transactions et utilisateurs avec un système d'authentification multi-rôles sécurisé.

## 🚀 Fonctionnalités

### Général
- Authentification sécurisée (email + mot de passe hashé)
- Gestion des sessions avec tokens CSRF
- Protection contre les injections SQL (requêtes préparées)
- Interface responsive et moderne
- Mode maintenance

### Rôles

**Admin**
- Dashboard avec statistiques globales
- Gestion des utilisateurs (CRUD complet)
- Gestion des clients
- Rapports et analyses avancées
- Configuration système
- Export de données (CSV)

**Conseiller**
- Dashboard personnel
- Gestion de ses clients assignés
- Visualisation des transactions
- Messagerie interne
- Export de données

**Client**
- Espace personnel
- Consultation des transactions
- Profil modifiable
- Historique complet

## 🛠️ Technologies

- **Backend** : PHP 8.3.14
- **Base de données** : MySQL 9.1.0
- **Frontend** : HTML5, CSS3, JavaScript Vanilla
- **Architecture** : MVC minimal
- **Sécurité** : PDO, password_hash, CSRF tokens, session_regenerate_id

## 📦 Installation

### Prérequis
```bash
PHP >= 8.3
MySQL >= 9.0
Apache/Nginx avec mod_rewrite
Extensions PHP : PDO, PDO_MySQL