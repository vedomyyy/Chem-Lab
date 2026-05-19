<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';

$db = getDB();
$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'create') {
        $name    = trim($_POST['name']    ?? '');
        $contact = trim($_POST['contact'] ?? '');
        $phone   = trim($_POST['phone']   ?? '');

        if (!$name) {
            $error = 'Введите название поставщика.';
        } else {
            $db->prepare('INSERT INTO suppliers (name, contact, phone) VALUES (?, ?, ?)')
               ->execute([$name, $contact ?: null, $phone ?: null]);
            $success = 'Поставщик добавлен.';
        }
    }

    if ($_POST['action'] === 'delete' && !empty($_POST['supplier_id'])) {
        $id = (int)$_POST['supplier_id'];
        $db->prepare('DELETE FROM suppliers WHERE id = ?')->execute([$id]);
        $success = 'Поставщик удалён.';
    }
}

$suppliers = $db->query('SELECT * FROM suppliers ORDER BY name')->fetchAll();
?>

<h2>Управление поставщиками</h2>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-body">
        <form method="post" class="row g-2">
            <input type="hidden" name="action" value="create">
            <div class="col-md-4">
                <input type="text" name="name" class="form-control" placeholder="Название организации *" required>
            </div>
            <div class="col-md-3">
                <input type="text" name="contact" class="form-control" placeholder="Контактное лицо">
            </div>
            <div class="col-md-3">
                <input type="text" name="phone" class="form-control" placeholder="Телефон">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-success w-100">Добавить</button>
            </div>
        </form>
    </div>
</div>

<table class="table table-bordered">
    <thead>
        <tr><th>Название</th><th>Контакт</th><th>Телефон</th><th></th></tr>
    </thead>
    <tbody>
    <?php foreach ($suppliers as $s): ?>
        <tr>
            <td><?= htmlspecialchars($s['name']) ?></td>
            <td><?= htmlspecialchars($s['contact'] ?? '—') ?></td>
            <td><?= htmlspecialchars($s['phone'] ?? '—') ?></td>
            <td>
                <form method="post" class="d-inline"
                      onsubmit="return confirm('Удалить поставщика?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="supplier_id" value="<?= $s['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger">Удалить</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<a href="/admin/index.php" class="btn btn-outline-secondary">← В админку</a>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>