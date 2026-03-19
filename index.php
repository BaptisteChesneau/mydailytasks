<?php
/**
 * index.php — Dashboard for myDailyTasks
 *
 * Features:
 *  - Displays today's statistics (task count, total time)
 *  - Generates a Doughnut chart using Chart.js
 *  - Lists all tasks for today (newest to oldest)
 *  - Allows task deletion via a secure GET link
 *  - Links to task creation and editing screens
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

// ── Flash messages (success / deletion) ──────────────────────
$flash_message = '';
$flash_type    = '';

// Success message after task creation or edit
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'created') {
        $flash_message = '✅ Tâche créée avec succès !';
        $flash_type    = 'success';
    } elseif ($_GET['success'] === 'edited') {
        $flash_message = '✅ Tâche modifiée avec succès !';
        $flash_type    = 'success';
    }
}

// ── Task deletion ────────────────────────────────────────────
if (isset($_GET['delete']) && ctype_digit($_GET['delete'])) {
    $task_id_to_delete = (int) $_GET['delete'];

    try {
        if (delete_task($task_id_to_delete)) {
            $flash_message = '🗑 Tâche supprimée avec succès.';
            $flash_type    = 'success';
        } else {
            $flash_message = 'Impossible de supprimer cette tâche.';
            $flash_type    = 'error';
        }
    } catch (\PDOException $e) {
        $flash_message = 'Erreur base de données : ' . htmlspecialchars($e->getMessage());
        $flash_type    = 'error';
    }
}

// ── Fetch today's tasks ──────────────────────────────────────
$today         = date(DATE_FORMAT_DB);       // e.g. "2026-03-19"
$today_display = date(DATE_FORMAT_DISPLAY);  // e.g. "19/03/2026"

try {
    $tasks = get_tasks_list($today);
} catch (\PDOException $e) {
    $tasks         = [];
    $flash_message = 'Erreur de connexion à la base de données.';
    $flash_type    = 'error';
}

// ── Compute statistics ───────────────────────────────────────
$task_count    = count($tasks);
$total_minutes = array_sum(array_column($tasks, 'duration'));
$total_hours   = floor($total_minutes / 60);
$remain_min    = $total_minutes % 60;

// Format total time for display
$total_time_display = $total_hours > 0
    ? "{$total_hours}h" . ($remain_min > 0 ? " {$remain_min}min" : '')
    : "{$total_minutes}min";

// ── Prepare data for Chart.js ────────────────────────────────
// Labels and data encoded as JSON to prevent XSS injection
$chart_labels = json_encode(array_column($tasks, 'name'));
$chart_data   = json_encode(array_column($tasks, 'duration'));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= APP_NAME ?> — Suivez votre temps de travail au quotidien.">
    <title><?= APP_NAME ?> — Tableau de bord</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="app-wrapper">

    <!-- ── Header ──────────────────────────────────────────── -->
    <header class="app-header" role="banner">
        <h1 class="app-logo">my<span>Daily</span>Tasks</h1>
        <p class="app-date">
            <time datetime="<?= $today ?>">📅 <?= $today_display ?></time>
        </p>
    </header>

    <main id="main-content">

        <?php if ($flash_message): ?>
        <!-- Flash message (success / error) -->
        <div class="alert alert--<?= $flash_type ?>" role="alert">
            <?= $flash_message ?>
        </div>
        <?php endif; ?>

        <!-- ── Statistics ──────────────────────────────────── -->
        <section aria-label="Statistiques du jour">
            <div class="stats-grid">
                <div class="stat-card">
                    <p class="stat-card__label">Tâches réalisées</p>
                    <p class="stat-card__value"><?= $task_count ?></p>
                    <p class="stat-card__unit">tâche<?= $task_count > 1 ? 's' : '' ?> aujourd'hui</p>
                </div>
                <div class="stat-card">
                    <p class="stat-card__label">Temps total</p>
                    <p class="stat-card__value"><?= $total_minutes > 0 ? $total_time_display : '—' ?></p>
                    <p class="stat-card__unit"><?= $total_minutes ?> minutes</p>
                </div>
            </div>
        </section>

        <!-- ── Doughnut chart ───────────────────────────────── -->
        <section class="chart-section" aria-label="Répartition visuelle des tâches">
            <h2 class="section-title">Répartition du temps</h2>
            <?php if ($task_count > 0): ?>
                <div class="chart-container">
                    <canvas id="taskChart" role="img" aria-label="Graphique en anneau des tâches du jour"></canvas>
                </div>
            <?php else: ?>
                <p class="chart-empty">Aucune tâche enregistrée aujourd'hui.</p>
            <?php endif; ?>
        </section>

        <!-- ── Task list ────────────────────────────────────── -->
        <section class="tasks-section" aria-label="Liste des tâches du jour">
            <div class="tasks-header">
                <h2 class="section-title" style="margin-bottom:0">Tâches du jour</h2>
                <a href="edit_task.php" class="btn btn--primary">
                    ＋ Nouvelle tâche
                </a>
            </div>

            <?php if ($task_count > 0): ?>
            <table class="tasks-table">
                <thead>
                    <tr>
                        <th scope="col">Nom</th>
                        <th scope="col">Date</th>
                        <th scope="col">Durée</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tasks as $task): ?>
                    <tr>
                        <td class="task-name">
                            <?= htmlspecialchars($task['name'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <time datetime="<?= htmlspecialchars($task['date']) ?>">
                                <?= date(DATE_FORMAT_DISPLAY, strtotime($task['date'])) ?>
                            </time>
                        </td>
                        <td>
                            <span class="badge-duration">
                                <?= (int) $task['duration'] ?> min
                            </span>
                        </td>
                        <td class="actions">
                            <!-- Edit button -->
                            <a href="edit_task.php?id=<?= (int) $task['task_id'] ?>"
                               class="btn btn--ghost btn--sm"
                               aria-label="Modifier la tâche <?= htmlspecialchars($task['name'], ENT_QUOTES, 'UTF-8') ?>">
                                ✏️ Modifier
                            </a>
                            <!-- Delete button (JS confirmation) -->
                            <a href="index.php?delete=<?= (int) $task['task_id'] ?>"
                               class="btn btn--danger btn--sm"
                               aria-label="Supprimer la tâche <?= htmlspecialchars($task['name'], ENT_QUOTES, 'UTF-8') ?>"
                               onclick="return confirm('Supprimer cette tâche ?')">
                                🗑 Supprimer
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div class="empty-state">
                <p class="empty-state__icon">📋</p>
                <p>Aucune tâche pour aujourd'hui.<br>
                   <a href="edit_task.php" class="btn btn--primary" style="margin-top:.75rem">
                       Créer une tâche
                   </a>
                </p>
            </div>
            <?php endif; ?>
        </section>

    </main>
</div><!-- /.app-wrapper -->

<?php if ($task_count > 0): ?>
<!-- Chart.js — Dynamically generated Doughnut chart -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function () {
    'use strict';

    // Data injected from PHP (native JSON encoding prevents XSS)
    const labels    = <?= $chart_labels ?>;
    const durations = <?= $chart_data ?>;

    // Color palette for chart segments
    const colors = [
        '#f5a623', '#4c9fff', '#4caf7d', '#e05c5c',
        '#a78bfa', '#fb7185', '#34d399', '#60a5fa',
        '#fbbf24', '#f472b6'
    ];

    // Assign colors cyclically if more tasks than colors
    const bgColors = labels.map((_, i) => colors[i % colors.length]);

    const ctx = document.getElementById('taskChart');

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: durations,
                backgroundColor: bgColors,
                borderColor: '#1a1d27',
                borderWidth: 3,
                hoverOffset: 8
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '#eef0f6',
                        font: { family: "'DM Sans', sans-serif", size: 13 },
                        padding: 14,
                        boxWidth: 12,
                        boxHeight: 12
                    }
                },
                tooltip: {
                    callbacks: {
                        /**
                         * Displays the duration in minutes in the tooltip.
                         * @param {object} context - Chart.js context
                         * @returns {string}
                         */
                        label: function (context) {
                            return ' ' + context.parsed + ' min';
                        }
                    }
                }
            },
            cutout: '60%'
        }
    });
})();
</script>
<?php endif; ?>

</body>
</html>