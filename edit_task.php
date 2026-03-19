<?php
/**
 * edit_task.php — Création et modification d'une tâche
 *
 * Comportement :
 *  - Sans paramètre GET  → formulaire de CRÉATION d'une nouvelle tâche.
 *  - Avec GET ?id=N      → formulaire de MODIFICATION de la tâche N.
 *
 * Le traitement du formulaire (POST) est géré dans ce même fichier
 * (pattern PRG : Post / Redirect / Get) pour éviter la re-soumission.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// ── Détermination du mode : création ou modification ─────────
$edit_mode = false;   // true = modification, false = création
$task      = null;    // données de la tâche existante (mode modification)
$task_id   = 0;

if (isset($_GET['id']) && ctype_digit($_GET['id'])) {
    $task_id = (int) $_GET['id'];

    try {
        $task = get_task_by_id($task_id);
    } catch (\PDOException $e) {
        $task = false;
    }

    if ($task === false) {
        // Tâche introuvable : on redirige vers l'accueil
        header('Location: index.php');
        exit;
    }

    $edit_mode = true;
}

// ── Valeurs initiales du formulaire ─────────────────────────
// En modification, on pré-remplit avec les données existantes.
// En création, on utilise des valeurs par défaut.
$form = [
    'name'     => $edit_mode ? $task['name']     : '',
    'duration' => $edit_mode ? (int) $task['duration'] : TASK_DURATION_MIN,
    'date'     => $edit_mode ? $task['date']     : date(DATE_FORMAT_DB),
];

// ── Gestion du formulaire (méthode POST) ─────────────────────
$errors        = [];
$flash_message = '';
$flash_type    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // — 1. Nettoyage des entrées —
    $input_name     = sanitize_string($_POST['name']     ?? '');
    $input_duration = $_POST['duration'] ?? '';
    $input_date     = trim($_POST['date'] ?? '');

    // Conservation des valeurs saisies pour re-affichage en cas d'erreur
    $form['name']     = $input_name;
    $form['duration'] = $input_duration;
    $form['date']     = $input_date;

    // — 2. Validation —

    // Nom : obligatoire, 2 caractères minimum
    if (mb_strlen($input_name) < 2) {
        $errors['name'] = 'Le nom de la tâche doit comporter au moins 2 caractères.';
    }

    // Durée : doit être un entier valide et multiple de TASK_DURATION_STEP
    if (!validate_duration($input_duration)) {
        $errors['duration'] = sprintf(
            'La durée doit être un multiple de %d min, entre %d et %d min.',
            TASK_DURATION_STEP,
            TASK_DURATION_MIN,
            TASK_DURATION_MAX
        );
    }

    // Date : format Y-m-d requis
    if (!validate_date($input_date)) {
        $errors['date'] = 'La date saisie est invalide.';
    }

    // — 3. Persistance si aucune erreur —
    if (empty($errors)) {
        $duration_int = (int) $input_duration;

        try {
            if ($edit_mode) {
                // Modification de la tâche existante
                edit_task($task_id, [
                    'name'     => $input_name,
                    'duration' => $duration_int,
                    'date'     => $input_date,
                ]);
            } else {
                // Création d'une nouvelle tâche
                create_task($input_name, $duration_int, $input_date);
            }

            // Pattern PRG : redirection après succès pour éviter la re-soumission
            header('Location: index.php?success=' . ($edit_mode ? 'edited' : 'created'));
            exit;

        } catch (\PDOException $e) {
            $flash_message = 'Erreur base de données : ' . htmlspecialchars($e->getMessage());
            $flash_type    = 'error';
        }
    }
}

// ── Construction de la liste des durées disponibles ──────────
// Génère les options <select> de TASK_DURATION_MIN à TASK_DURATION_MAX
// par pas de TASK_DURATION_STEP.
$duration_options = range(TASK_DURATION_MIN, TASK_DURATION_MAX, TASK_DURATION_STEP);

// ── Titre de la page selon le mode ───────────────────────────
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

    <!-- ── En-tête ─────────────────────────────────────────── -->
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
            <!-- Message d'erreur de base de données -->
            <div class="alert alert--<?= $flash_type ?>" role="alert">
                <?= $flash_message ?>
            </div>
            <?php endif; ?>

            <!--
                Formulaire de création / modification
                Action : ce même fichier (edit_task.php)
                Méthode : POST (les données ne transitent pas dans l'URL)
            -->
            <form method="POST"
                  action="<?= $edit_mode ? 'edit_task.php?id=' . $task_id : 'edit_task.php' ?>"
                  novalidate>

                <!-- Champ : nom de la tâche -->
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

                <!-- Champ : durée (liste déroulante, multiples de 10 min) -->
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
                            // Formatage lisible : "30 min" ou "1h 30min"
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

                <!-- Champ : date (par défaut = aujourd'hui) -->
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

                <!-- Boutons d'action -->
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