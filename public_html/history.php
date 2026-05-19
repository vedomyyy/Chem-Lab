<?php
require_once 'includes/db.php';

// === ЭКСПОРТ CSV — ДОЛЖЕН БЫТЬ САМЫМ ПЕРВЫМ ===
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    
    $db = getDB();

    // Параметры фильтрации (копируем из основного кода)
    $filter_type     = $_GET['type']        ?? '';
    $filter_material = trim($_GET['material'] ?? '');
    $filter_user     = $_GET['user_id']     ?? '';
    $filter_from     = $_GET['date_from']   ?? '';
    $filter_to       = $_GET['date_to']     ?? '';

    $where  = 'WHERE 1=1';
    $params = [];

    if (in_array($filter_type, ['in', 'out'])) {
        $where .= ' AND t.type = ?';
        $params[] = $filter_type;
    }
    if ($filter_material !== '') {
        $where .= ' AND m.name LIKE ?';
        $params[] = '%' . $filter_material . '%';
    }
    if ($filter_user !== '') {
        $where .= ' AND t.user_id = ?';
        $params[] = $filter_user;
    }
    if ($filter_from !== '') {
        $where .= ' AND DATE(t.created_at) >= ?';
        $params[] = $filter_from;
    }
    if ($filter_to !== '') {
        $where .= ' AND DATE(t.created_at) <= ?';
        $params[] = $filter_to;
    }

    $stmt = $db->prepare(
        "SELECT t.created_at, t.type, m.name AS material, m.unit, t.quantity,
                t.purpose, u.full_name AS operator, s.name AS supplier
         FROM transactions t
         JOIN materials m ON t.material_id = m.id
         JOIN users u ON t.user_id = u.id
         LEFT JOIN suppliers s ON t.supplier_id = s.id
         $where
         ORDER BY t.created_at DESC"
    );
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="history_' . date('Y-m-d_H-i') . '.csv"');
    echo "\xEF\xBB\xBF"; // BOM для нормального отображения в Excel

    $out = fopen('php://output', 'w');
    fputcsv($out, ['Дата', 'Тип', 'Материал', 'Кол-во', 'Ед.изм.', 'Цель/Поставщик', 'Ответственный'], ';');

    foreach ($rows as $r) {
        fputcsv($out, [
            date('d.m.Y H:i', strtotime($r['created_at'])),
            $r['type'] === 'in' ? 'Приход' : 'Расход',
            $r['material'],
            $r['quantity'],
            $r['unit'],
            $r['purpose'] ?? $r['supplier'] ?? '—',
            $r['operator'],
        ], ';');
    }
    fclose($out);
    exit;
}

$db = getDB();
require_once 'includes/header.php';

// Только авторизованные
if (empty($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

$db = getDB();


// ====================== ФИЛЬТРЫ ======================
$filter_type     = $_GET['type']        ?? '';
$filter_material = trim($_GET['material'] ?? '');
$filter_user     = $_GET['user_id']     ?? '';
$filter_from     = $_GET['date_from']   ?? '';
$filter_to       = $_GET['date_to']     ?? '';

// Строим WHERE
$where  = 'WHERE 1=1';
$params = [];

if (in_array($filter_type, ['in', 'out'])) {
    $where .= ' AND t.type = ?';
    $params[] = $filter_type;
}
if ($filter_material !== '') {
    $where .= ' AND m.name LIKE ?';
    $params[] = '%' . $filter_material . '%';
}
if ($filter_user !== '') {
    $where .= ' AND t.user_id = ?';
    $params[] = $filter_user;
}
if ($filter_from !== '') {
    $where .= ' AND DATE(t.created_at) >= ?';
    $params[] = $filter_from;
}
if ($filter_to !== '') {
    $where .= ' AND DATE(t.created_at) <= ?';
    $params[] = $filter_to;
}

// ====================== ДАННЫЕ ДЛЯ МОДАЛКИ ======================
$materials = $db->query('SELECT id, name, unit, quantity FROM materials ORDER BY name')->fetchAll();
$suppliers = $db->query('SELECT id, name FROM suppliers ORDER BY name')->fetchAll();
$users     = $db->query('SELECT id, full_name FROM users ORDER BY full_name')->fetchAll();

// ====================== ПАГИНАЦИЯ ======================
$perPage = 20;
$page   = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$countStmt = $db->prepare(
    "SELECT COUNT(*) FROM transactions t
     JOIN materials m ON t.material_id = m.id
     $where"
);
$countStmt->execute($params);
$total      = $countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

$stmt = $db->prepare(
    "SELECT t.id, t.type, t.quantity, t.purpose, t.created_at,
            m.name AS material, m.unit,
            u.full_name AS operator,
            s.name AS supplier
     FROM transactions t
     JOIN materials m ON t.material_id = m.id
     JOIN users u ON t.user_id = u.id
     LEFT JOIN suppliers s ON t.supplier_id = s.id
     $where
     ORDER BY t.created_at DESC
     LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

if (isset($_SESSION['tx_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= $_SESSION['tx_error'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['tx_error']); ?>
<?php endif; ?>


<h2> Журнал операций</h2>

<!-- Кнопка добавления операции -->
<div class="mb-3">
    <button class="btn btn-success btn-lg" data-bs-toggle="modal" data-bs-target="#txModal">
        <i class="bi bi-plus-circle"></i> Новая операция
    </button>
</div>

<!-- Фильтры -->
<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-3 align-items-end">
            
            <div class="col-md-2">
                <label class="form-label">Тип операции</label>
                <select name="type" class="form-select">
                    <option value="">Все типы</option>
                    <option value="in"  <?= $filter_type === 'in'  ? 'selected' : '' ?>>Приход</option>
                    <option value="out" <?= $filter_type === 'out' ? 'selected' : '' ?>>Расход</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">Материал</label>
                <input type="text" name="material" class="form-control"
                       placeholder="Название материала..." 
                       value="<?= htmlspecialchars($filter_material) ?>">
            </div>

            <div class="col-md-2">
                <label class="form-label">Сотрудник</label>
                <select name="user_id" class="form-select">
                    <option value="">Все сотрудники</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= $filter_user == $u['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['full_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">Дата с</label>
                <input type="date" name="date_from" class="form-control"
                       value="<?= htmlspecialchars($filter_from) ?>">
            </div>

            <div class="col-md-2">
                <label class="form-label">Дата по</label>
                <input type="date" name="date_to" class="form-control"
                       value="<?= htmlspecialchars($filter_to) ?>">
            </div>

            <div class="col-md-1 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">Фильтровать</button>
            </div>

            <!-- Кнопки действий -->
            <div class="col-12 col-md-auto d-flex gap-2 mt-md-4">
                <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" 
                   class="btn btn-outline-success">
                     CSV
                </a>
                <a href="/history.php" class="btn btn-outline-secondary">
                    Сброс
                </a>
            </div>
        </form>
    </div>
</div>

<p class="text-muted">Найдено записей: <strong><?= $total ?></strong></p>

<table class="table table-bordered table-hover table-sm">
    <thead>
        <tr>
            <th>Дата</th>
            <th>Тип</th>
            <th>Материал</th>
            <th>Количество</th>
            <th>Цель / Поставщик</th>
            <th>Ответственный</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($transactions)): ?>
        <tr><td colspan="6" class="text-center text-muted">Ничего не найдено</td></tr>
    <?php endif; ?>
    <?php foreach ($transactions as $t): ?>
        <tr>
            <td><?= date('d.m.Y H:i', strtotime($t['created_at'])) ?></td>
            <td>
                <?= $t['type'] === 'in'
                    ? '<span class="badge bg-success">Приход</span>'
                    : '<span class="badge bg-danger">Расход</span>' ?>
            </td>
            <td><?= htmlspecialchars($t['material']) ?></td>
            <td><?= $t['quantity'] ?> <?= $t['unit'] ?></td>
            <td>
                <?php if ($t['type'] === 'in'): ?>
                    <?= htmlspecialchars($t['supplier'] ?? '—') ?>
                <?php else: ?>
                    <?= htmlspecialchars($t['purpose'] ?? '—') ?>
                <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($t['operator']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<!-- Пагинация -->
<?php if ($totalPages > 1): ?>
<nav>
    <ul class="pagination justify-content-center">
        <?php
        $qp = $_GET;
        unset($qp['page']);
        $qs = !empty($qp) ? '&' . http_build_query($qp) : '';
        ?>
        <?php if ($page > 1): ?>
            <li class="page-item"><a class="page-link" href="?page=<?= $page - 1 ?><?= $qs ?>">← Назад</a></li>
        <?php endif; ?>
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="?page=<?= $i ?><?= $qs ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
            <li class="page-item"><a class="page-link" href="?page=<?= $page + 1 ?><?= $qs ?>">Вперёд →</a></li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>

<!-- ====================== МОДАЛЬНОЕ ОКНО ====================== -->
<div class="modal fade" id="txModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Новая операция</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="/save_transaction.php" id="txForm">
                <div class="modal-body">
                    <!-- Здесь тот же код модального окна из transactions.php -->
                    <div class="mb-3">
                        <label class="form-label">Тип операции *</label>
                        <select name="type" id="typeSelect" class="form-select" required>
                            <option value="">Выберите...</option>
                            <option value="in">Приход (пополнение)</option>
                            <option value="out">Расход (списание)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Материал *</label>
                        <select name="material_id" id="materialSelect" class="form-select" required>
                            <option value="">Выберите...</option>
                            <?php foreach ($materials as $m): ?>
                                <option value="<?= $m['id'] ?>"
                                        data-unit="<?= htmlspecialchars($m['unit']) ?>"
                                        data-qty="<?= $m['quantity'] ?>">
                                    <?= htmlspecialchars($m['name']) ?> (<?= $m['quantity'] ?> <?= $m['unit'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            Количество * <span id="unitLabel" class="text-muted"></span>
                        </label>
                        <input type="number" name="quantity" id="quantityInput" class="form-control"
                               step="0.001" min="0.001" required>
                        <div id="stockHint" class="form-text"></div>
                    </div>

                    <div class="mb-3" id="supplierBlock" style="display:none;">
                        <label class="form-label">Поставщик</label>
                        <select name="supplier_id" class="form-select">
                            <option value="">— не указан —</option>
                            <?php foreach ($suppliers as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" id="purposeLabel">Цель / комментарий</label>
                        <input type="text" name="purpose" class="form-control"
                               placeholder="Например: Лаб. работа №1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// JS из transactions.php (с небольшими улучшениями)
const typeSelect     = document.getElementById('typeSelect');
const materialSelect = document.getElementById('materialSelect');
const supplierBlock  = document.getElementById('supplierBlock');
const unitLabel      = document.getElementById('unitLabel');
const stockHint      = document.getElementById('stockHint');
const purposeLabel   = document.getElementById('purposeLabel');
const quantityInput  = document.getElementById('quantityInput');

function updateUI() {
    const type = typeSelect.value;
    const option = materialSelect.options[materialSelect.selectedIndex];

    supplierBlock.style.display = (type === 'in') ? '' : 'none';
    purposeLabel.textContent = (type === 'in') ? 'Комментарий к поставке' : 'Цель списания';

    if (option && option.value) {
        const unit = option.dataset.unit;
        const qty  = parseFloat(option.dataset.qty);
        unitLabel.textContent = `(${unit})`;
        
        if (type === 'out') {
            stockHint.textContent = `Доступно: ${qty} ${unit}`;
            stockHint.className = qty > 0 ? 'form-text text-muted' : 'form-text text-danger fw-bold';
        } else {
            stockHint.textContent = `Текущий остаток: ${qty} ${unit}`;
            stockHint.className = 'form-text text-muted';
        }
    } else {
        unitLabel.textContent = '';
        stockHint.textContent = '';
    }
}

// Восстановление данных после ошибки
<?php if (isset($_SESSION['tx_old'])): ?>
const oldData = <?= json_encode($_SESSION['tx_old']) ?>;
if (oldData.type)        typeSelect.value = oldData.type;
if (oldData.material_id) materialSelect.value = oldData.material_id;
if (oldData.quantity)    quantityInput.value = oldData.quantity;
if (oldData.supplier_id) document.querySelector('select[name="supplier_id"]').value = oldData.supplier_id || '';
if (oldData.purpose)     document.querySelector('input[name="purpose"]').value = oldData.purpose;

updateUI();
<?php unset($_SESSION['tx_old']); ?>
<?php endif; ?>

typeSelect.addEventListener('change', updateUI);
materialSelect.addEventListener('change', updateUI);
</script>

<?php require_once 'includes/footer.php'; ?>