<?php
require_once 'includes/db.php';
session_start();

// Только авторизованные пользователи
if (empty($_SESSION['user_id'])) {
    header('Location: /login.php');
    exit;
}

// Только POST-запросы
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /history.php');
    exit;
}

$type        = $_POST['type']        ?? '';
$material_id = (int)($_POST['material_id'] ?? 0);
$quantity    = $_POST['quantity']    ?? '';
$supplier_id = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;
$purpose     = trim($_POST['purpose'] ?? '');

// --- Валидация входных данных ---
$errors = [];

if (!in_array($type, ['in', 'out'])) {
    $errors[] = 'Выберите тип операции.';
}
if ($material_id <= 0) {
    $errors[] = 'Выберите материал.';
}
if (!is_numeric($quantity) || $quantity <= 0) {
    $errors[] = 'Количество должно быть числом больше нуля.';
}

// Если базовая валидация прошла — проверяем остаток для расхода
if (empty($errors) && $type === 'out') {
    $db = getDB();
    $stmt = $db->prepare('SELECT quantity, name, unit FROM materials WHERE id = ?');
    $stmt->execute([$material_id]);
    $material = $stmt->fetch();

    if (!$material) {
        $errors[] = 'Материал не найден.';
    } elseif ($quantity > $material['quantity']) {
        // Запрет списания больше, чем есть на складе
        $errors[] = sprintf(
            'Недостаточно материала. Запрошено: %.3f, доступно: %.3f %s.',
            $quantity,
            $material['quantity'],
            $material['unit']
        );
    }
}

// --- Ошибки → обратно на форму ---
if (!empty($errors)) {
    $_SESSION['tx_error'] = implode('<br>', $errors);
    $_SESSION['tx_old']   = [
        'type'        => $type,
        'material_id' => $material_id,
        'quantity'    => $quantity,
        'supplier_id' => $supplier_id,
        'purpose'     => $purpose,
    ];
    header('Location: /history.php');
    exit;
}

// --- Сохраняем в SQL-транзакции (атомарно) ---
$db = getDB();
try {
    $db->beginTransaction();

    // Обновляем количество материала
    if ($type === 'in') {
        $db->prepare('UPDATE materials SET quantity = quantity + ? WHERE id = ?')
           ->execute([$quantity, $material_id]);
    } else {
        $db->prepare('UPDATE materials SET quantity = quantity - ? WHERE id = ?')
           ->execute([$quantity, $material_id]);
    }

    // Записываем операцию в журнал
    $db->prepare(
        'INSERT INTO transactions (material_id, type, quantity, supplier_id, purpose, user_id)
         VALUES (?, ?, ?, ?, ?, ?)'
    )->execute([
        $material_id,
        $type,
        $quantity,
        $supplier_id,
        $purpose ?: null,
        $_SESSION['user_id'],
    ]);

    $db->commit();
} catch (Exception $e) {
    // Если что-то пошло не так — откатываем изменения
    $db->rollBack();
    $_SESSION['tx_error'] = 'Ошибка при сохранении операции. Попробуйте снова.';
    header('Location: /history.php');
    exit;
}

header('Location: /history.php');
exit;