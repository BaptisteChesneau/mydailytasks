<?php
/**
 * config.example.php — Modèle de configuration de myDailyTasks
 *
 * ⚠️  NE PAS modifier ce fichier directement.
 *     Copiez-le en "config.php" et renseignez vos propres valeurs.
 *
 *     cp config.example.php config.php
 *
 * config.php est ignoré par Git (.gitignore) pour ne pas exposer
 * vos identifiants de base de données sur GitHub.
 */

// ── Base de données ──────────────────────────────────────────
/** Hôte du serveur MySQL */
define('DB_HOST', 'localhost');

/** Nom de la base de données */
define('DB_NAME', 'timetracking');

/** Utilisateur MySQL — à remplacer par votre identifiant */
define('DB_USER', 'votre_utilisateur');

/** Mot de passe MySQL — à remplacer par votre mot de passe */
define('DB_PASS', 'votre_mot_de_passe');

/** Encodage de la connexion */
define('DB_CHARSET', 'utf8mb4');

// ── Application ──────────────────────────────────────────────
/** Nom affiché de l'application */
define('APP_NAME', 'myDailyTasks');

/** Version de l'application */
define('APP_VERSION', '1.0.0');

/** Durée minimale d'une tâche (en minutes) */
define('TASK_DURATION_MIN', 10);

/** Durée maximale d'une tâche (en minutes) */
define('TASK_DURATION_MAX', 480);

/** Incrément de durée (en minutes) */
define('TASK_DURATION_STEP', 10);

/** Format de date interne (base de données) */
define('DATE_FORMAT_DB', 'Y-m-d');

/** Format de date affiché à l'utilisateur */
define('DATE_FORMAT_DISPLAY', 'd/m/Y');