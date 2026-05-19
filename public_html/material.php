<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

// Если не авторизован — на вход
if (empty($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: /materials.php');
    exit;
}

$db = getDB();

// Получаем материал
$stmt = $db->prepare(
    'SELECT m.*, c.name AS category_name
     FROM materials m
     JOIN categories c ON m.category_id = c.id
     WHERE m.id = ?'
);
$stmt->execute([$id]);
$material = $stmt->fetch();

if (!$material) {
    echo '<div class="alert alert-danger">Материал не найден.</div>';
    require_once 'includes/footer.php';
    exit;
}

// Получаем историю операций по этому материалу
$stmt = $db->prepare(
    'SELECT t.type, t.quantity, t.purpose, t.created_at,
            u.full_name AS operator,
            s.name AS supplier
     FROM transactions t
     JOIN users u ON t.user_id = u.id
     LEFT JOIN suppliers s ON t.supplier_id = s.id
     WHERE t.material_id = ?
     ORDER BY t.created_at DESC
     LIMIT 20'
);
$stmt->execute([$id]);
$transactions = $stmt->fetchAll();
?>

<h2><?= htmlspecialchars($material['name']) ?></h2>

<div class="row">
    <div class="col-md-6">
        <table class="table table-bordered">
            <tr>
                <th>Формула</th>
                <td><?= htmlspecialchars($material['formula'] ?? '—') ?></td>
            </tr>
            <tr>
                <th>Категория</th>
                <td><?= htmlspecialchars($material['category_name']) ?></td>
            </tr>
            <tr>
                <th>Количество</th>
                <td>
                    <?= $material['quantity'] ?> <?= $material['unit'] ?>
                    <?php if ($material['quantity'] < $material['threshold']): ?>
                        <span class="badge bg-warning text-dark">Мало</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th>Мин. запас</th>
                <td><?= $material['threshold'] ?> <?= $material['unit'] ?></td>
            </tr>
            <tr>
                <th>Место хранения</th>
                <td><?= htmlspecialchars($material['location'] ?? '—') ?></td>
            </tr>
        </table>
        
        <a href="/materials.php" class="btn btn-outline-secondary">← Назад к списку</a>
    </div>
</div>

<h4 class="mt-4">История операций</h4>
<?php if (empty($transactions)): ?>
    <p class="text-muted">Операций пока не было.</p>
<?php else: ?>
    <table class="table table-bordered table-sm">
        <thead>
            <tr>
                <th>Дата</th>
                <th>Тип</th>
                <th>Количество</th>
                <th>Цель / Поставщик</th>
                <th>Оператор</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($transactions as $t): ?>
            <tr>
                <td><?= date('d.m.Y H:i', strtotime($t['created_at'])) ?></td>
                <td>
                    <?= $t['type'] === 'in'
                        ? '<span class="badge bg-success">Приход</span>'
                        : '<span class="badge bg-danger">Расход</span>' ?>
                </td>
                <td><?= $t['quantity'] ?> <?= $material['unit'] ?></td>
                <td><?= htmlspecialchars($t['supplier'] ?? $t['purpose'] ?? '—') ?></td>
                <td><?= htmlspecialchars($t['operator']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>