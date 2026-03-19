<?php
/**
 * functions.php — Business logic for myDailyTasks
 *
 * Contains:
 *  - db_connect()      : PDO database connection
 *  - get_tasks_list()  : retrieve all tasks for a given date
 *  - create_task()     : insert a new task
 *  - edit_task()       : update an existing task
 *  - delete_task()     : delete a task
 *  - get_task_by_id()  : retrieve a single task by its ID
 */

require_once __DIR__ . '/config.php';

// ════════════════════════════════════════════════════════════
// Connection
// ════════════════════════════════════════════════════════════

/**
 * Establishes and returns a PDO connection to the database.
 *
 * The connection is configured to:
 *  - throw exceptions on error (ERRMODE_EXCEPTION)
 *  - return results as associative arrays
 *  - disable emulated prepared statements (better security)
 *
 * @return PDO Database connection instance.
 * @throws PDOException If the connection fails.
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
// Read
// ════════════════════════════════════════════════════════════

/**
 * Returns all tasks for a given date, sorted from newest to oldest.
 *
 * @param string $date Date in 'Y-m-d' format.
 * @return array       Associative array of tasks.
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
 * Returns a single task identified by its ID.
 *
 * @param int $task_id Task identifier.
 * @return array|false Associative array of the task, or false if not found.
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
// Create
// ════════════════════════════════════════════════════════════

/**
 * Inserts a new task into the database.
 *
 * @param string $name     Task name (max 255 characters).
 * @param int    $duration Duration in minutes (multiple of TASK_DURATION_STEP).
 * @param string $date     Date in 'Y-m-d' format.
 * @return bool            true if insertion succeeded, false otherwise.
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
// Update
// ════════════════════════════════════════════════════════════

/**
 * Updates some or all fields of an existing task.
 *
 * Only fields provided in $fields are updated.
 * Allowed keys are: 'name', 'duration', 'date'.
 *
 * @param int   $task_id Task identifier.
 * @param array $fields  Associative array ['column' => 'value'].
 * @return bool          true if update succeeded, false otherwise.
 * @throws InvalidArgumentException If no valid field is provided.
 */
function edit_task(int $task_id, array $fields): bool
{
    // Whitelist of columns allowed for update
    $allowed_columns = ['name', 'duration', 'date'];

    // Filter: keep only allowed keys
    $filtered = array_filter(
        $fields,
        fn($key) => in_array($key, $allowed_columns, true),
        ARRAY_FILTER_USE_KEY
    );

    if (empty($filtered)) {
        throw new \InvalidArgumentException(
            'edit_task(): no valid field provided for update.'
        );
    }

    // Dynamically build SET clauses
    $set_clauses = implode(
        ', ',
        array_map(fn($col) => "`$col` = :$col", array_keys($filtered))
    );

    $pdo = db_connect();

    $sql  = "UPDATE tasks SET $set_clauses WHERE task_id = :task_id";
    $stmt = $pdo->prepare($sql);

    // Add the task ID to the parameters
    $filtered[':task_id'] = $task_id;

    // Prefix keys with ':'
    $params = [];
    foreach ($filtered as $key => $value) {
        $params_key          = str_starts_with($key, ':') ? $key : ":$key";
        $params[$params_key] = $value;
    }

    return $stmt->execute($params);
}

// ════════════════════════════════════════════════════════════
// Delete
// ════════════════════════════════════════════════════════════

/**
 * Deletes a task from the database.
 *
 * @param int $task_id Task identifier.
 * @return bool        true if deletion succeeded, false otherwise.
 */
function delete_task(int $task_id): bool
{
    $pdo = db_connect();

    $sql  = 'DELETE FROM tasks WHERE task_id = :task_id';
    $stmt = $pdo->prepare($sql);

    return $stmt->execute([':task_id' => $task_id]);
}

// ════════════════════════════════════════════════════════════
// Utilities
// ════════════════════════════════════════════════════════════

/**
 * Sanitizes a user-submitted string.
 *
 * @param string $input   Raw form field value.
 * @param int    $max_len Maximum allowed length.
 * @return string         Cleaned and truncated string.
 */
function sanitize_string(string $input, int $max_len = 255): string
{
    $clean = trim($input);
    $clean = strip_tags($clean);
    $clean = htmlspecialchars($clean, ENT_QUOTES, 'UTF-8');
    return mb_substr($clean, 0, $max_len);
}

/**
 * Validates a duration value: must be a positive integer, a multiple of
 * TASK_DURATION_STEP, between TASK_DURATION_MIN and TASK_DURATION_MAX.
 *
 * @param mixed $duration Value to validate.
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
 * Validates a date string in 'Y-m-d' format.
 *
 * @param string $date Value to validate.
 * @return bool
 */
function validate_date(string $date): bool
{
    $dt = \DateTime::createFromFormat(DATE_FORMAT_DB, $date);
    return $dt && $dt->format(DATE_FORMAT_DB) === $date;
}