# myDailyTasks

Application de time tracking quotidien développée en HTML, CSS, PHP et SQL dans le cadre d'un test d'admission « Développeur IOT ».

## Fonctionnalités

- Tableau de bord avec statistiques du jour (nombre de tâches, temps total)
- Graphique Doughnut généré dynamiquement avec Chart.js
- Création, modification et suppression de tâches
- Tâches filtrées par date et triées de la plus récente à la plus ancienne
- Interface responsive et optimisée pour mobile

## Technologies utilisées

- **PHP** natif (sans framework)
- **MySQL** via PDO
- **HTML5** sémantique
- **CSS3** responsive (sans framework)
- **Chart.js** (CDN) pour le graphique

## Prérequis

- PHP 8.0 ou supérieur
- MySQL 5.7 ou supérieur
- Serveur local type XAMPP, WampServer ou Laragon

## Installation

### 1. Cloner le dépôt

```bash
git clone https://github.com/BaptisteChesneau/mydailytasks.git
```

### 2. Placer le dossier dans le répertoire de votre serveur local

```
C:/xampp/htdocs/mydailytasks/
```

### 3. Importer la base de données

Via phpMyAdmin :
- Ouvrir `http://localhost/phpmyadmin`
- Onglet **Importer** → sélectionner `sql/mydailytasks.sql`
- Cliquer **Exécuter**

Ou en ligne de commande :

```bash
mysql -u root -p < sql/mydailytasks.sql
```

### 4. Configurer la connexion à la base de données

Copier le fichier exemple et renseigner vos identifiants :

```bash
cp config.example.php config.php
```

Ouvrir `config.php` et modifier les valeurs suivantes :

```php
define('DB_USER', 'votre_utilisateur');
define('DB_PASS', 'votre_mot_de_passe');
```

### 5. Lancer l'application

Démarrer Apache et MySQL depuis le panneau de contrôle XAMPP puis ouvrir :

```
http://localhost/mydailytasks/
```

## Structure du projet

```
mydailytasks/
├── css/
│   └── style.css         # Feuille de styles responsive
├── sql/
│   └── mydailytasks.sql  # Script de création de la BDD
├── config.example.php    # Modèle de configuration (à copier en config.php)
├── edit_task.php         # Création et modification d'une tâche
├── functions.php         # Fonctions PDO (connexion, CRUD, validation)
└── index.php             # Tableau de bord principal
```

## Sécurité

- Requêtes SQL préparées via PDO (protection contre les injections SQL)
- Nettoyage et validation de toutes les entrées formulaire
- `config.php` exclu du dépôt Git via `.gitignore`
- Pattern PRG (Post/Redirect/Get) pour éviter la re-soumission des formulaires