<?php
/**
 * functions.php — Fonctions métier de myDailyTasks
 *
 * Contient :
 *  - db_connect()      : connexion PDO à la base de données
 *  - get_tasks_list()  : récupère les tâches d'une date donnée
 *  - create_task()     : insère une nouvelle tâche
 *  - edit_task()       : modifie une tâche existante
 *  - delete_task()     : supprime une tâche
 *  - get_task_by_id()  : récupère une tâche par son identifiant
 */

require_once __DIR__ . '/config.php';

// ════════════════════════════════════════════════════════════
// Connexion
// ════════════════════════════════════════════════════════════

/**
 * Établit et retourne une connexion PDO à la base de données.
 *
 * La connexion est configurée pour :
 *  - lever des exceptions en cas d'erreur (ERRMODE_EXCEPTION)
 *  - retourner les résultats sous forme de tableaux associatifs
 *  - ne PAS émuler les requêtes préparées (sécurité accrue)
 *
 * @return PDO Instance de connexion à la base de données.
 * @throws PDOException Si la connexion échoue.
 */
function db_connect(): PDO
{
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_NAME,
        DB_CHARSET
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    return new PDO($dsn, DB_USER, DB_PASS, $options);
}

// ════════════════════════════════════════════════════════════
// Lecture
// ════════════════════════════════════════════════════════════

/**
 * Retourne la liste de toutes les tâches pour une date donnée,
 * triées de la plus récente à la plus ancienne (par task_id DESC).
 *
 * @param string $date Date au format 'Y-m-d'.
 * @return array       Tableau associatif des tâches trouvées.
 */
function get_tasks_list(string $date): array
{
    $pdo = db_connect();

    $sql  = 'SELECT task_id, name, duration, date
             FROM tasks
             WHERE date = :date
             ORDER BY task_id DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':date' => $date]);

    return $stmt->fetchAll();
}

/**
 * Retourne une tâche unique identifiée par son ID.
 *
 * @param int $task_id Identifiant de la tâche.
 * @return array|false Tableau associatif de la tâche, ou false si introuvable.
 */
function get_task_by_id(int $task_id): array|false
{
    $pdo = db_connect();

    $sql  = 'SELECT task_id, name, duration, date
             FROM tasks
             WHERE task_id = :task_id
             LIMIT 1';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':task_id' => $task_id]);

    return $stmt->fetch();
}

// ════════════════════════════════════════════════════════════
// Création
// ════════════════════════════════════════════════════════════

/**
 * Insère une nouvelle tâche en base de données.
 *
 * @param string $name     Nom de la tâche (max 255 caractères).
 * @param int    $duration Durée en minutes (multiple de TASK_DURATION_STEP).
 * @param string $date     Date au format 'Y-m-d'.
 * @return bool            true si l'insertion a réussi, false sinon.
 */
function create_task(string $name, int $duration, string $date): bool
{
    $pdo = db_connect();

    $sql  = 'INSERT INTO tasks (name, duration, date)
             VALUES (:name, :duration, :date)';

    $stmt = $pdo->prepare($sql);

    return $stmt->execute([
        ':name'     => $name,
        ':duration' => $duration,
        ':date'     => $date,
    ]);
}

// ════════════════════════════════════════════════════════════
// Modification
// ════════════════════════════════════════════════════════════

/**
 * Modifie tout ou partie des informations d'une tâche existante.
 *
 * Seuls les champs fournis dans $fields sont mis à jour.
 * Les clés autorisées sont : 'name', 'duration', 'date'.
 *
 * @param int   $task_id Identifiant de la tâche à modifier.
 * @param array $fields  Tableau associatif ['colonne' => 'valeur'].
 * @return bool          true si la mise à jour a réussi, false sinon.
 * @throws InvalidArgumentException Si aucun champ valide n'est fourni.
 */
function edit_task(int $task_id, array $fields): bool
{
    // Colonnes autorisées à la modification (liste blanche)
    $allowed_columns = ['name', 'duration', 'date'];

    // Filtrage : on ne conserve que les clés autorisées
    $filtered = array_filter(
        $fields,
        fn($key) => in_array($key, $allowed_columns, true),
        ARRAY_FILTER_USE_KEY
    );

    if (empty($filtered)) {
        throw new \InvalidArgumentException(
            'edit_task() : aucun champ valide fourni pour la mise à jour.'
        );
    }

    // Construction dynamique des clauses SET
    $set_clauses = implode(
        ', ',
        array_map(fn($col) => "`$col` = :$col", array_keys($filtered))
    );

    $pdo = db_connect();

    $sql  = "UPDATE tasks SET $set_clauses WHERE task_id = :task_id";
    $stmt = $pdo->prepare($sql);

    // Ajout de l'identifiant aux paramètres
    $filtered[':task_id'] = $task_id;

    // Préfixage des clés avec ':'
    $params = [];
    foreach ($filtered as $key => $value) {
        $params_key          = str_starts_with($key, ':') ? $key : ":$key";
        $params[$params_key] = $value;
    }

    return $stmt->execute($params);
}

// ════════════════════════════════════════════════════════════
// Suppression
// ════════════════════════════════════════════════════════════

/**
 * Supprime une tâche de la base de données.
 *
 * @param int $task_id Identifiant de la tâche à supprimer.
 * @return bool        true si la suppression a réussi, false sinon.
 */
function delete_task(int $task_id): bool
{
    $pdo = db_connect();

    $sql  = 'DELETE FROM tasks WHERE task_id = :task_id';
    $stmt = $pdo->prepare($sql);

    return $stmt->execute([':task_id' => $task_id]);
}

// ════════════════════════════════════════════════════════════
// Utilitaires
// ════════════════════════════════════════════════════════════

/**
 * Nettoie et valide une chaîne de caractères saisie par l'utilisateur.
 *
 * @param string $input   Valeur brute du champ formulaire.
 * @param int    $max_len Longueur maximale autorisée.
 * @return string         Chaîne nettoyée et tronquée.
 */
function sanitize_string(string $input, int $max_len = 255): string
{
    $clean = trim($input);
    $clean = strip_tags($clean);
    $clean = htmlspecialchars($clean, ENT_QUOTES, 'UTF-8');
    return mb_substr($clean, 0, $max_len);
}

/**
 * Valide une durée : doit être un entier positif, multiple de
 * TASK_DURATION_STEP, compris entre TASK_DURATION_MIN et TASK_DURATION_MAX.
 *
 * @param mixed $duration Valeur à valider.
 * @return bool
 */
function validate_duration(mixed $duration): bool
{
    if (!is_numeric($duration)) {
        return false;
    }

    $d = (int) $duration;

    return $d >= TASK_DURATION_MIN
        && $d <= TASK_DURATION_MAX
        && $d % TASK_DURATION_STEP === 0;
}

/**
 * Valide une date au format 'Y-m-d'.
 *
 * @param string $date Valeur à valider.
 * @return bool
 */
function validate_date(string $date): bool
{
    $dt = \DateTime::createFromFormat(DATE_FORMAT_DB, $date);
    return $dt && $dt->format(DATE_FORMAT_DB) === $date;
}