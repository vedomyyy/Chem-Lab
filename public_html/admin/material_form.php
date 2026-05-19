<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../includes/db.php';

$db = getDB();

// Получаем категории для выпадающего списка
$categories = $db->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();

// Если передан id — значит, редактирование
$material = null;
$is_edit = false;
if (!empty($_GET['id'])) {
    $stmt = $db->prepare('SELECT * FROM materials WHERE id = ?');
    $stmt->execute([$_GET['id']]);
    $material = $stmt->fetch();
    if ($material) {
        $is_edit = true;
    } else {
        die('Материал не найден.');
    }
}

// Получаем ошибки и старые значения из сессии (если были)
session_start(); // auth_check уже запустил, но на всякий случай
$error = $_SESSION['material_error'] ?? '';
$old = $_SESSION['material_old'] ?? [];
unset($_SESSION['material_error'], $_SESSION['material_old']);

// Если редактируем и нет старых значений, используем данные из БД
if ($is_edit && empty($old)) {
    $old = $material;
}

require_once __DIR__ . '/../includes/header.php';
?>

<h2><?= $is_edit ? 'Редактирование' : 'Добавление' ?> материала</h2>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="post" action="/admin/save_material.php<?= $is_edit ? '?id=' . $material['id'] : '' ?>">
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Название *</label>
            <input type="text" name="name" class="form-control" required
                   value="<?= htmlspecialchars($old['name'] ?? '') ?>">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Формула</label>
            <input type="text" name="formula" class="form-control"
                   value="<?= htmlspecialchars($old['formula'] ?? '') ?>">
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-4 mb-3">
            <label class="form-label">Категория *</label>
            <select name="category_id" class="form-select" required>
                <option value="">Выберите...</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" 
                        <?= ($old['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 mb-3">
            <label class="form-label">Ед. изм. *</label>
            <input type="text" name="unit" class="form-control" required
                   placeholder="мл, г, шт"
                   value="<?= htmlspecialchars($old['unit'] ?? '') ?>">
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label">Количество *</label>
            <input type="number" step="0.001" name="quantity" class="form-control" required
                   value="<?= htmlspecialchars($old['quantity'] ?? '0') ?>">
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label">Порог (мин. запас)</label>
            <input type="number" step="0.001" name="threshold" class="form-control"
                   value="<?= htmlspecialchars($old['threshold'] ?? '0') ?>">
        </div>
    </div>
    
    <div class="mb-3">
        <label class="form-label">Место хранения *</label>
        <input type="text" name="location" class="form-control" required
               value="<?= htmlspecialchars($old['location'] ?? '') ?>">
    </div>
    
    <button type="submit" class="btn btn-primary"><?= $is_edit ? 'Сохранить' : 'Добавить' ?></button>
    <a href="/admin/materials.php" class="btn btn-outline-secondary">Отмена</a>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>