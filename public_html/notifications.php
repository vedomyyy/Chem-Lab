<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

// Только авторизованные
if (empty($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$db = getDB();

// Получаем все материалы, у которых количество ниже порогового значения
$lowStock = $db->query(
    'SELECT m.name, m.formula, m.unit, m.quantity, m.threshold, m.location,
            c.name AS category
     FROM materials m
     JOIN categories c ON m.category_id = c.id
     WHERE m.quantity < m.threshold
     ORDER BY (m.threshold - m.quantity) DESC'
)->fetchAll();
?>

<h2>Уведомления — Что нужно заказать</h2>

<?php if (empty($lowStock)): ?>
    <div class="alert alert-success">
        Все материалы в норме! Ничего заказывать не нужно.
    </div>
<?php else: ?>
    <div class="alert alert-warning">
        Обнаружено <strong><?= count($lowStock) ?></strong> материал(а/ов) с недостаточным запасом.
    </div>

    <table class="table table-bordered table-hover">
        <thead class="table-danger">
            <tr>
                <th>Материал</th>
                <th>Формула</th>
                <th>Категория</th>
                <th>Текущий остаток</th>
                <th>Минимальный запас</th>
                <th>Нехватка</th>
                <th>Место хранения</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($lowStock as $m): ?>
            <tr class="table-warning">
                <td>
                    <a href="/material.php?id=<?php
                        // Получаем id материала
                        $idStmt = $db->prepare('SELECT id FROM materials WHERE name = ? LIMIT 1');
                        $idStmt->execute([$m['name']]);
                        echo $idStmt->fetchColumn();
                    ?>">
                        <?= htmlspecialchars($m['name']) ?>
                    </a>
                </td>
                <td><?= htmlspecialchars($m['formula'] ?? '—') ?></td>
                <td><?= htmlspecialchars($m['category']) ?></td>
                <td class="text-danger fw-bold"><?= $m['quantity'] ?> <?= $m['unit'] ?></td>
                <td><?= $m['threshold'] ?> <?= $m['unit'] ?></td>
                <td class="text-danger">
                    <?= number_format($m['threshold'] - $m['quantity'], 3) ?> <?= $m['unit'] ?>
                </td>
                <td><?= htmlspecialchars($m['location'] ?? '—') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>