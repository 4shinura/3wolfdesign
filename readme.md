# 3wolfdesign - Aéromodèles & Impression 3D

![Symfony](https://img.shields.io/badge/Symfony-6.4-black?style=for-the-badge&logo=symfony)
![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php)
![MariaDB](https://img.shields.io/badge/MariaDB-003545?style=for-the-badge&logo=mariadb&logoColor=white)

## 🎯 Objectifs du Projet
Le site a été développé pour répondre à plusieurs besoins :
* **Visibilité** : Valorisation du savoir-faire en impression 3D et prototypage.
* **Plateforme** : Tunnel de vente optimisé pour les maquettes d'aéromodélisme.
* **Crédibilité** : Mise en avant de partenaires et de retours client.
* **Autonomie** : Interface d'administration complète (Back-office) pour la gestion du catalogue et des commandes.

## 🛠️ Architecture & Fonctionnalités

### 🌐 Front-Office
* **Partie Vitrine** : Catalogue de prototypes 3D, galerie de réalisations sur-mesure pour démontrer le savoir-faire de la marque.
* **E-Commerce** : 
    * Listing produits dynamique avec filtres de recherche.
    * Panier fluide et tunnel d'achat sécurisé.
    * Espace client avec historique de commandes.

### 🔐 Back-Office 
* **Gestion du Catalogue** : CRUD complet pour les aéromodèles et impressions 3D.
* **Gestion des Médias** : Système d'upload pour les fiches produits et la galerie client.
* **Suivi Commercial** : Gestion du statut des commandes et statistiques de ventes (mensuelles/globales).
* **Partenariats** : Gestion dynamique des logos partenaires affichés en footer.

## 💻 Spécifications Techniques
* **Framework** : Symfony 8.0.3 (Architecture MVC).
* **PHP** : PHP 8.4
* **Base de données** : MariaDB via Doctrine ORM (Requêtes optimisées avec QueryBuilder).
* **Sécurité** : Composant Symfony Security pour la protection du Back-office.
* **Paiement** : Intégration de l'API PayPal Checkout
* **Performance** : Pagination avancée via [KnpPaginator](https://github.com/KnpLabs/KnpPaginatorBundle) pour les listes volumineuses (utilisateurs, produits, commandes).
* **Design & Ergonomie** : Intégration d'une charte graphique personnalisée sous Twig avec une préoccupation sur le Responsive Design.
* **Conformité RGPD** : 
    * Mise en œuvre d'une politique de protection des données personnelles.
    * Information du consentement utilisateur (CGU) pour l'usages des cookies techniques 'fonctionnels'.
    * Sécurisation des données clients (hachage des mots de passe, protection des transactions par PayPal).