<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

// Если не авторизован — отправляем на вход
if (empty($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$db = getDB();

// Получаем список категорий для фильтра
$categories = $db->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();

// Параметры пагинации
$perPage = 10; // записей на страницу
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

// Обрабатываем поиск и фильтр
$search = trim($_GET['search'] ?? '');
$category_id = $_GET['category_id'] ?? '';

// Строим WHERE
$where = 'WHERE 1=1';
$params = [];

if ($search !== '') {
    $where .= ' AND m.name LIKE ?';
    $params[] = '%' . $search . '%';
}
if ($category_id !== '') {
    $where .= ' AND m.category_id = ?';
    $params[] = $category_id;
}

// Считаем общее количество записей
$countSql = "SELECT COUNT(*) FROM materials m $where";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

// Получаем записи для текущей страницы
$sql = "SELECT m.id, m.name, m.formula, c.name AS category, 
               m.unit, m.quantity, m.threshold, m.location
        FROM materials m
        JOIN categories c ON m.category_id = c.id
        $where
        ORDER BY c.name, m.name
        LIMIT $perPage OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$materials = $stmt->fetchAll();
?>

<h2>Склад материалов</h2>

<!-- Форма поиска и фильтрации -->
<form method="get" class="row g-2 mb-3">
    <div class="col-md-6">
        <input type="text" name="search" class="form-control" 
               placeholder="Поиск по названию..." 
               value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="col-md-4">
        <select name="category_id" class="form-select">
            <option value="">Все категории</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?= $cat['id'] ?>" <?= $category_id == $cat['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($cat['name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <button type="submit" class="btn btn-primary w-100">Найти</button>
    </div>
</form>

<!-- Таблица материалов -->
<table class="table table-bordered table-hover">
    <thead>
        <tr>
            <th>Название</th>
            <th>Формула</th>
            <th>Категория</th>
            <th>Количество</th>
            <th>Место хранения</th>
            <th>Статус</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($materials)): ?>
        <tr>
            <td colspan="7" class="text-center text-muted">Ничего не найдено</td>
        </tr>
    <?php endif; ?>
    
    <?php foreach ($materials as $m): ?>
        <tr <?= $m['quantity'] < $m['threshold'] ? 'data-low="1"' : '' ?>>
            <td>
                <a href="/material.php?id=<?= $m['id'] ?>">
                    <?= htmlspecialchars($m['name']) ?>
                </a>
            </td>
            <td><?= htmlspecialchars($m['formula'] ?? '—') ?></td>
            <td><?= htmlspecialchars($m['category']) ?></td>
            <td><?= $m['quantity'] ?> <?= $m['unit'] ?></td>
            <td><?= htmlspecialchars($m['location']) ?></td>
            <td>
                <?php if ($m['quantity'] < $m['threshold']): ?>
                    <span class="badge bg-warning text-dark">Мало</span>
                <?php else: ?>
                    <span class="badge bg-success">В норме</span>
                <?php endif; ?>
            </td>
            <td>
                <a href="/material.php?id=<?= $m['id'] ?>" class="btn btn-sm btn-outline-primary">Подробнее</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<!-- Пагинация -->
<?php if ($totalPages > 1): ?>
<nav>
    <ul class="pagination justify-content-center">
        <?php
        // Сохраняем параметры поиска для ссылок
        $queryParams = [];
        if ($search !== '') $queryParams['search'] = $search;
        if ($category_id !== '') $queryParams['category_id'] = $category_id;
        $queryString = !empty($queryParams) ? '&' . http_build_query($queryParams) : '';
        ?>
        
        <?php if ($page > 1): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?= $page - 1 ?><?= $queryString ?>">← Назад</a>
            </li>
        <?php endif; ?>
        
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?><?= $queryString ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
        
        <?php if ($page < $totalPages): ?>
            <li class="page-item">
                <a class="page-link" href="?page=<?= $page + 1 ?><?= $queryString ?>">Вперёд →</a>
            </li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>