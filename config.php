<?php
/**
 * config.php — Constantes de configuration de myDailyTasks
 *
 * Centralise tous les paramètres de l'application :
 * connexion à la BDD, nom de l'app, format de dates, etc.
 * À adapter selon l'environnement (dev / prod).
 */

// ── Base de données ──────────────────────────────────────────
/** Hôte du serveur MySQL */
define('DB_HOST', 'localhost');

/** Nom de la base de données */
define('DB_NAME', 'timetracking');

/** Utilisateur MySQL */
define('DB_USER', 'root');

/** Mot de passe MySQL */
define('DB_PASS', '');

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