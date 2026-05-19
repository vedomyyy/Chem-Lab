<?php
require_once 'includes/db.php';

// ====================== ЭКСПОРТ CSV ======================
// Этот блок ДОЛЖЕН быть в самом начале файла!
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    
    $db = getDB();

    $date_from = $_GET['date_from'] ?? date('Y-m-01');
    $date_to   = $_GET['date_to']   ?? date('Y-m-d');

    $stmt = $db->prepare(
        'SELECT m.name, m.unit, m.formula,
                SUM(CASE WHEN t.type = "out" THEN t.quantity ELSE 0 END) AS total_out,
                SUM(CASE WHEN t.type = "in"  THEN t.quantity ELSE 0 END) AS total_in,
                COUNT(CASE WHEN t.type = "out" THEN 1 END) AS ops_count
         FROM transactions t
         JOIN materials m ON t.material_id = m.id
         WHERE DATE(t.created_at) BETWEEN ? AND ?
         GROUP BY m.id, m.name, m.unit, m.formula
         ORDER BY total_out DESC'
    );
    $stmt->execute([$date_from, $date_to]);
    $consumption = $stmt->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="report_' . $date_from . '_' . $date_to . '.csv"');
    echo "\xEF\xBB\xBF"; // BOM для Excel

    $out = fopen('php://output', 'w');
    fputcsv($out, ['Материал', 'Формула', 'Ед.изм.', 'Приход', 'Расход', 'Кол-во операций'], ';');

    foreach ($consumption as $row) {
        fputcsv($out, [
            $row['name'],
            $row['formula'] ?? '—',
            $row['unit'],
            $row['total_in'],
            $row['total_out'],
            $row['ops_count'],
        ], ';');
    }
    fclose($out);
    exit;
}

// ====================== ОСНОВНОЙ КОД СТРАНИЦЫ ======================
require_once 'includes/header.php';

// Только преподаватели и администраторы
if (empty($_SESSION['user_id']) || !in_array($_SESSION['role'], ['teacher', 'admin'])) {
    header('Location: /login.php');
    exit;
}

$db = getDB();

// Параметры периода
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to   = $_GET['date_to']   ?? date('Y-m-d');

// --- Отчёт 1: Общий расход по материалам ---
$stmt = $db->prepare(
    'SELECT m.name, m.unit, m.formula,
            SUM(CASE WHEN t.type = "out" THEN t.quantity ELSE 0 END) AS total_out,
            SUM(CASE WHEN t.type = "in"  THEN t.quantity ELSE 0 END) AS total_in,
            COUNT(CASE WHEN t.type = "out" THEN 1 END) AS ops_count
     FROM transactions t
     JOIN materials m ON t.material_id = m.id
     WHERE DATE(t.created_at) BETWEEN ? AND ?
     GROUP BY m.id, m.name, m.unit, m.formula
     ORDER BY total_out DESC'
);
$stmt->execute([$date_from, $date_to]);
$consumption = $stmt->fetchAll();

// --- Отчёт 2: Топ-5 материалов ---
$topMaterials = $db->query(
    'SELECT m.name, m.unit,
            SUM(t.quantity) AS total_used,
            COUNT(*) AS ops_count
     FROM transactions t
     JOIN materials m ON t.material_id = m.id
     WHERE t.type = "out"
     GROUP BY m.id, m.name, m.unit
     ORDER BY total_used DESC
     LIMIT 5'
)->fetchAll();

// --- Отчёт 3: Статистика по лабораторным работам ---
$labStats = $db->query(
    'SELECT t.purpose, COUNT(*) AS ops_count,
            GROUP_CONCAT(DISTINCT m.name SEPARATOR ", ") AS materials_used
     FROM transactions t
     JOIN materials m ON t.material_id = m.id
     WHERE t.type = "out" AND t.purpose IS NOT NULL AND t.purpose != ""
     GROUP BY t.purpose
     ORDER BY ops_count DESC
     LIMIT 10'
)->fetchAll();
?>

<h2>Отчёты</h2>

<!-- Фильтр по периоду -->
<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Дата с</label>
                <input type="date" name="date_from" class="form-control"
                       value="<?= htmlspecialchars($date_from) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Дата по</label>
                <input type="date" name="date_to" class="form-control"
                       value="<?= htmlspecialchars($date_to) ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">Применить</button>
            </div>
            <div class="col-md-2">
                <a href="?date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>&export=csv"
                   class="btn btn-outline-success w-100">CSV</a>
            </div>
        </form>
    </div>
</div>

<!-- Отчёт 1: Расход за период -->
<h4>Расход материалов за период: <?= htmlspecialchars($date_from) ?> — <?= htmlspecialchars($date_to) ?></h4>
<?php if (empty($consumption)): ?>
    <div class="alert alert-info">За выбранный период операций не найдено.</div>
<?php else: ?>
<table class="table table-bordered table-hover mb-5">
    <thead class="table-dark">
        <tr>
            <th>Материал</th>
            <th>Формула</th>
            <th>Ед. изм.</th>
            <th>Приход</th>
            <th>Расход</th>
            <th>Кол-во операций</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($consumption as $row): ?>
        <tr>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['formula'] ?? '—') ?></td>
            <td><?= $row['unit'] ?></td>
            <td class="text-success"><?= $row['total_in'] ?></td>
            <td class="text-danger"><?= $row['total_out'] ?></td>
            <td><?= $row['ops_count'] ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<!-- Отчёт 2: Топ-5 материалов -->
<h4>Топ-5 самых используемых материалов (за всё время)</h4>
<table class="table table-bordered table-hover mb-5">
    <thead class="table-warning">
        <tr>
            <th>#</th>
            <th>Материал</th>
            <th>Суммарный расход</th>
            <th>Операций</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($topMaterials as $i => $row): ?>
        <tr>
            <td><strong><?= $i + 1 ?></strong></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= $row['total_used'] ?> <?= $row['unit'] ?></td>
            <td><?= $row['ops_count'] ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<!-- Отчёт 3: Статистика по лабораторным работам -->
<h4>Статистика по лабораторным работам</h4>
<?php if (empty($labStats)): ?>
    <div class="alert alert-info">Данных о лабораторных работах не найдено.</div>
<?php else: ?>
<table class="table table-bordered table-hover">
    <thead class="table-info">
        <tr>
            <th>Лабораторная работа / Цель</th>
            <th>Кол-во списаний</th>
            <th>Использованные материалы</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($labStats as $row): ?>
        <tr>
            <td><?= htmlspecialchars($row['purpose']) ?></td>
            <td><?= $row['ops_count'] ?></td>
            <td><small class="text-muted"><?= htmlspecialchars($row['materials_used']) ?></small></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>