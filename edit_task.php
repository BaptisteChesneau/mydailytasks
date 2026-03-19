<?php
/**
 * edit_task.php — Task creation and editing screen
 *
 * Behaviour:
 *  - No GET parameter  → CREATE a new task.
 *  - GET ?id=N         → EDIT existing task N.
 *
 * Form processing (POST) is handled in this same file
 * using the PRG pattern (Post / Redirect / Get) to prevent re-submission.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// ── Determine mode: create or edit ───────────────────────────
$edit_mode = false;   // true = edit mode, false = create mode
$task      = null;    // existing task data (edit mode only)
$task_id   = 0;

if (isset($_GET['id']) && ctype_digit($_GET['id'])) {
    $task_id = (int) $_GET['id'];

    try {
        $task = get_task_by_id($task_id);
    } catch (\PDOException $e) {
        $task = false;
    }

    if ($task === false) {
        // Task not found: redirect to dashboard
        header('Location: index.php');
        exit;
    }

    $edit_mode = true;
}

// ── Initial form values ──────────────────────────────────────
// In edit mode, pre-fill with existing data.
// In create mode, use default values.
$form = [
    'name'     => $edit_mode ? $task['name']          : '',
    'duration' => $edit_mode ? (int) $task['duration'] : TASK_DURATION_MIN,
    'date'     => $edit_mode ? $task['date']           : date(DATE_FORMAT_DB),
];

// ── Form processing (POST) ───────────────────────────────────
$errors        = [];
$flash_message = '';
$flash_type    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // — 1. Sanitize inputs —
    $input_name     = sanitize_string($_POST['name']     ?? '');
    $input_duration = $_POST['duration'] ?? '';
    $input_date     = trim($_POST['date'] ?? '');

    // Keep submitted values for re-display on validation error
    $form['name']     = $input_name;
    $form['duration'] = $input_duration;
    $form['date']     = $input_date;

    // — 2. Validate inputs —

    // Name: required, minimum 2 characters
    if (mb_strlen($input_name) < 2) {
        $errors['name'] = 'Le nom de la tâche doit comporter au moins 2 caractères.';
    }

    // Duration: must be a valid integer and a multiple of TASK_DURATION_STEP
    if (!validate_duration($input_duration)) {
        $errors['duration'] = sprintf(
            'La durée doit être un multiple de %d min, entre %d et %d min.',
            TASK_DURATION_STEP,
            TASK_DURATION_MIN,
            TASK_DURATION_MAX
        );
    }

    // Date: Y-m-d format required
    if (!validate_date($input_date)) {
        $errors['date'] = 'La date saisie est invalide.';
    }

    // — 3. Persist if no errors —
    if (empty($errors)) {
        $duration_int = (int) $input_duration;

        try {
            if ($edit_mode) {
                // Update the existing task
                edit_task($task_id, [
                    'name'     => $input_name,
                    'duration' => $duration_int,
                    'date'     => $input_date,
                ]);
            } else {
                // Insert a new task
                create_task($input_name, $duration_int, $input_date);
            }

            // PRG pattern: redirect after success to prevent form re-submission
            header('Location: index.php?success=' . ($edit_mode ? 'edited' : 'created'));
            exit;

        } catch (\PDOException $e) {
            $flash_message = 'Erreur base de données : ' . htmlspecialchars($e->getMessage());
            $flash_type    = 'error';
        }
    }
}

// ── Build list of available durations ────────────────────────
// Generates <select> options from TASK_DURATION_MIN to TASK_DURATION_MAX
// in steps of TASK_DURATION_STEP.
$duration_options = range(TASK_DURATION_MIN, TASK_DURATION_MAX, TASK_DURATION_STEP);

// ── Page title based on mode ──────────────────────────────────
$page_title = $edit_mode ? 'Modifier une tâche' : 'Nouvelle tâche';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= APP_NAME ?> — <?= htmlspecialchars($page_title) ?>">
    <title><?= APP_NAME ?> — <?= htmlspecialchars($page_title) ?></title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="app-wrapper">

    <!-- ── Header ──────────────────────────────────────────── -->
    <header class="app-header" role="banner">
        <h1 class="app-logo">my<span>Daily</span>Tasks</h1>
        <a href="index.php" class="btn btn--ghost">← Retour au tableau de bord</a>
    </header>

    <main id="main-content">

        <div class="form-card">
            <h2 class="form-title">
                <?= $edit_mode ? '✏️ Modifier la tâche' : '➕ Nouvelle tâche' ?>
            </h2>

            <?php if ($flash_message): ?>
            <!-- Database error message -->
            <div class="alert alert--<?= $flash_type ?>" role="alert">
                <?= $flash_message ?>
            </div>
            <?php endif; ?>

            <!--
                Create / edit form
                Action : this same file (edit_task.php)
                Method : POST (data does not appear in the URL)
            -->
            <form method="POST"
                  action="<?= $edit_mode ? 'edit_task.php?id=' . $task_id : 'edit_task.php' ?>"
                  novalidate>

                <!-- Field: task name -->
                <div class="form-group">
                    <label class="form-label" for="name">Nom de la tâche</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        class="form-control"
                        value="<?= htmlspecialchars($form['name'], ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="Ex : Revue de code, Réunion client…"
                        maxlength="255"
                        required
                        aria-describedby="<?= isset($errors['name']) ? 'name-error' : '' ?>"
                        aria-invalid="<?= isset($errors['name']) ? 'true' : 'false' ?>">
                    <?php if (isset($errors['name'])): ?>
                    <p id="name-error" class="alert alert--error" role="alert" style="margin-top:.5rem;padding:.5rem .8rem">
                        <?= htmlspecialchars($errors['name']) ?>
                    </p>
                    <?php endif; ?>
                </div>

                <!-- Field: duration (dropdown, multiples of 10 min) -->
                <div class="form-group">
                    <label class="form-label" for="duration">Durée</label>
                    <select
                        id="duration"
                        name="duration"
                        class="form-control"
                        required
                        aria-describedby="<?= isset($errors['duration']) ? 'duration-error' : '' ?>"
                        aria-invalid="<?= isset($errors['duration']) ? 'true' : 'false' ?>">
                        <?php foreach ($duration_options as $opt): ?>
                        <option
                            value="<?= $opt ?>"
                            <?= (int) $form['duration'] === $opt ? 'selected' : '' ?>>
                            <?php
                            // Human-readable format: "30 min" or "1h 30min"
                            $h = intdiv($opt, 60);
                            $m = $opt % 60;
                            if ($h > 0) {
                                echo $h . 'h' . ($m > 0 ? ' ' . $m . 'min' : '');
                            } else {
                                echo $opt . ' min';
                            }
                            ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['duration'])): ?>
                    <p id="duration-error" class="alert alert--error" role="alert" style="margin-top:.5rem;padding:.5rem .8rem">
                        <?= htmlspecialchars($errors['duration']) ?>
                    </p>
                    <?php endif; ?>
                </div>

                <!-- Field: date (defaults to today) -->
                <div class="form-group">
                    <label class="form-label" for="date">Date</label>
                    <input
                        type="date"
                        id="date"
                        name="date"
                        class="form-control"
                        value="<?= htmlspecialchars($form['date'], ENT_QUOTES, 'UTF-8') ?>"
                        required
                        aria-describedby="<?= isset($errors['date']) ? 'date-error' : '' ?>"
                        aria-invalid="<?= isset($errors['date']) ? 'true' : 'false' ?>">
                    <?php if (isset($errors['date'])): ?>
                    <p id="date-error" class="alert alert--error" role="alert" style="margin-top:.5rem;padding:.5rem .8rem">
                        <?= htmlspecialchars($errors['date']) ?>
                    </p>
                    <?php endif; ?>
                </div>

                <!-- Action buttons -->
                <div class="form-actions">
                    <button type="submit" class="btn btn--primary">
                        <?= $edit_mode ? '💾 Enregistrer les modifications' : '✅ Créer la tâche' ?>
                    </button>
                    <a href="index.php" class="btn btn--ghost">Annuler</a>
                </div>

            </form>
        </div>

    </main>
</div><!-- /.app-wrapper -->

</body>
</html>