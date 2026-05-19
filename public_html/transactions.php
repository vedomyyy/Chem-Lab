<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

$db = getDB();

// === ВЫВОД ОШИБОК ВАЛИДАЦИИ ===
if (isset($_SESSION['tx_error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= $_SESSION['tx_error'] ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['tx_error']); ?>
<?php endif; ?>

<?php
// Загружаем данные
$transactions = $db->query(
    'SELECT t.id, t.type, t.quantity, t.purpose, t.created_at,
            m.name AS material, m.unit,
            u.full_name AS operator,
            s.name AS supplier
     FROM transactions t
     JOIN materials m ON t.material_id = m.id
     JOIN users u ON t.user_id = u.id
     LEFT JOIN suppliers s ON t.supplier_id = s.id
     ORDER BY t.created_at DESC'
)->fetchAll();

$materials = $db->query('SELECT id, name, unit, quantity FROM materials ORDER BY name')->fetchAll();
$suppliers = $db->query('SELECT id, name FROM suppliers ORDER BY name')->fetchAll();
?>

<h2>Журнал операций</h2>

<div class="mb-3">
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#txModal">
        + Новая операция
    </button>
</div>

<table class="table table-bordered table-hover">
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

<div class="modal fade" id="txModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Новая операция</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post" action="/save_transaction.php" id="txForm">
                <div class="modal-body">

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
                                    <?= htmlspecialchars($m['name']) ?>
                                    (<?= $m['quantity'] ?> <?= $m['unit'] ?>)
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
                                <option value="<?= $s['id'] ?>">
                                    <?= htmlspecialchars($s['name']) ?>
                                </option>
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
const typeSelect     = document.getElementById('typeSelect');
const materialSelect = document.getElementById('materialSelect');
const supplierBlock  = document.getElementById('supplierBlock');
const unitLabel      = document.getElementById('unitLabel');
const stockHint      = document.getElementById('stockHint');
const purposeLabel   = document.getElementById('purposeLabel');
const quantityInput  = document.getElementById('quantityInput');

function updateUI() {
    const type   = typeSelect.value;
    const option = materialSelect.options[materialSelect.selectedIndex];

    supplierBlock.style.display = (type === 'in') ? '' : 'none';
    purposeLabel.textContent = (type === 'in') ? 'Комментарий к поставке' : 'Цель списания';

    if (option && option.value) {
        const unit = option.dataset.unit;
        const qty  = parseFloat(option.dataset.qty);
        unitLabel.textContent = '(' + unit + ')';
        
        if (type === 'out') {
            stockHint.textContent = 'Доступно: ' + qty + ' ' + unit;
            stockHint.className = qty > 0 ? 'form-text text-muted' : 'form-text text-danger fw-bold';
        } else {
            stockHint.textContent = 'Текущий остаток: ' + qty + ' ' + unit;
            stockHint.className = 'form-text text-muted';
        }
    } else {
        unitLabel.textContent = '';
        stockHint.textContent = '';
    }
}

// Восстановление данных после ошибки валидации
<?php if (isset($_SESSION['tx_old'])): ?>
const oldData = <?= json_encode($_SESSION['tx_old']) ?>;

if (oldData.type)        typeSelect.value = oldData.type;
if (oldData.material_id) materialSelect.value = oldData.material_id;
if (oldData.quantity)    quantityInput.value = oldData.quantity;
if (oldData.supplier_id) document.querySelector('select[name="supplier_id"]').value = oldData.supplier_id || '';
if (oldData.purpose)     document.querySelector('input[name="purpose"]').value = oldData.purpose;

updateUI(); // обновляем интерфейс
<?php unset($_SESSION['tx_old']); ?>
<?php endif; ?>

typeSelect.addEventListener('change', updateUI);
materialSelect.addEventListener('change', updateUI);
</script>

<?php require_once 'includes/footer.php'; ?>