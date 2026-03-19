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

### 1. Clone the repository

```bash
git clone https://github.com/BaptisteChesneau/mydailytasks.git
```

### 2. Place the folder in your local server directory

```
C:/xampp/htdocs/mydailytasks/
```

### 3. Import the database

Via phpMyAdmin :
- Ouvrir `http://localhost/phpmyadmin`
- Onglet **Importer** → sélectionner `sql/mydailytasks.sql`
- Cliquer **Exécuter**

Or via command line:

```bash
mysql -u root -p < sql/mydailytasks.sql
```

### 4. Configure the database connection

Copy the example file and fill in your credentials:

```bash
cp config.example.php config.php
```

Open `config.php` and update the following values:

```php
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

### 5. Run the application

Start Apache and MySQL from the XAMPP control panel then open:

```
http://localhost/mydailytasks/
```

## Project structure

```
mydailytasks/
├── css/
│   └── style.css         # Responsive stylesheet
├── sql/
│   └── mydailytasks.sql  # Database creation script
├── config.example.php    # Configuration template (copy to config.php)
├── edit_task.php         # Task creation and editing screen
├── functions.php         # PDO functions (connection, CRUD, validation)
└── index.php             # Main dashboard
```

## Security

- Prepared SQL statements via PDO (protection against SQL injection)
- Sanitization and validation of all form inputs
- `config.php` excluded from Git repository via `.gitignore`
- PRG pattern (Post/Redirect/Get) to prevent form re-submission