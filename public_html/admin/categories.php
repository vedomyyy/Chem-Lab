<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';

$db = getDB();

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['action'])) {

        if ($_POST['action'] === 'create') {
            // Добавление новой категории
            $name = trim($_POST['name'] ?? '');
            if (!$name) {
                $error = 'Введите название категории.';
            } else {
                try {
                    $db->prepare('INSERT INTO categories (name) VALUES (?)')->execute([$name]);
                    $success = 'Категория добавлена.';
                } catch (PDOException $e) {
                    $error = 'Такая категория уже существует.';
                }
            }
        }

        if ($_POST['action'] === 'delete' && !empty($_POST['cat_id'])) {
            $id = (int)$_POST['cat_id'];
            // Проверяем: есть ли материалы в этой категории
            $count = $db->prepare('SELECT COUNT(*) FROM materials WHERE category_id = ?');
            $count->execute([$id]);
            if ($count->fetchColumn() > 0) {
                $error = 'Нельзя удалить категорию, в которой есть материалы.';
            } else {
                $db->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
                $success = 'Категория удалена.';
            }
        }

        if ($_POST['action'] === 'rename' && !empty($_POST['cat_id'])) {
            $id      = (int)$_POST['cat_id'];
            $newName = trim($_POST['new_name'] ?? '');
            if ($newName) {
                $db->prepare('UPDATE categories SET name = ? WHERE id = ?')->execute([$newName, $id]);
                $success = 'Категория переименована.';
            }
        }
    }
}

$categories = $db->query(
    'SELECT c.id, c.name, COUNT(m.id) AS material_count
     FROM categories c
     LEFT JOIN materials m ON m.category_id = c.id
     GROUP BY c.id, c.name
     ORDER BY c.name'
)->fetchAll();
?>

<h2>Управление категориями</h2>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<!-- Добавление категории -->
<div class="card mb-4">
    <div class="card-body">
        <form method="post" class="row g-2">
            <input type="hidden" name="action" value="create">
            <div class="col-md-8">
                <input type="text" name="name" class="form-control" placeholder="Название новой категории" required>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-success w-100">Добавить</button>
            </div>
        </form>
    </div>
</div>

<!-- Список категорий -->
<table class="table table-bordered">
    <thead>
        <tr>
            <th>Название</th>
            <th>Материалов</th>
            <th>Действия</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($categories as $cat): ?>
        <tr>
            <td>
                <!-- Форма переименования -->
                <form method="post" class="d-inline">
                    <input type="hidden" name="action" value="rename">
                    <input type="hidden" name="cat_id" value="<?= $cat['id'] ?>">
                    <input type="text" name="new_name" class="form-control form-control-sm d-inline w-auto"
                           value="<?= htmlspecialchars($cat['name']) ?>">
                    <button type="submit" class="btn btn-sm btn-warning">Сохранить</button>
                </form>
            </td>
            <td><?= $cat['material_count'] ?></td>
            <td>
                <?php if ($cat['material_count'] == 0): ?>
                    <form method="post" class="d-inline"
                          onsubmit="return confirm('Удалить категорию «<?= htmlspecialchars($cat['name']) ?>»?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="cat_id" value="<?= $cat['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Удалить</button>
                    </form>
                <?php else: ?>
                    <span class="text-muted small">Есть материалы</span>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<a href="/admin/index.php" class="btn btn-outline-secondary">← В админку</a>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>