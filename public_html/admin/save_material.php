<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../includes/db.php';

// Проверяем, что данные отправлены
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /admin/materials.php');
    exit;
}

$is_edit = !empty($_GET['id']);
$id = $_GET['id'] ?? null;

// Получаем данные из формы
$name = trim($_POST['name'] ?? '');
$formula = trim($_POST['formula'] ?? '');
$category_id = $_POST['category_id'] ?? '';
$unit = trim($_POST['unit'] ?? '');
$quantity = $_POST['quantity'] ?? '';
$threshold = $_POST['threshold'] ?? '0';
$location = trim($_POST['location'] ?? '');

// Валидация
$errors = [];

if ($name === '') {
    $errors[] = 'Название обязательно.';
}
if ($category_id === '') {
    $errors[] = 'Выберите категорию.';
}
if ($unit === '') {
    $errors[] = 'Укажите единицу измерения.';
}
if (!is_numeric($quantity) || $quantity < 0) {
    $errors[] = 'Количество должно быть числом ≥ 0.';
}
if (!is_numeric($threshold) || $threshold < 0) {
    $errors[] = 'Порог должен быть числом ≥ 0.';
}

// Если есть ошибки — сохраняем их в сессию и возвращаем на форму
if (!empty($errors)) {
    $_SESSION['material_error'] = implode('<br>', $errors);
    $_SESSION['material_old'] = [
        'name'        => $name,
        'formula'     => $formula,
        'category_id' => $category_id,
        'unit'        => $unit,
        'quantity'    => $quantity,
        'threshold'   => $threshold,
        'location'    => $location,
    ];
    $redirect = $is_edit ? "/admin/material_form.php?id=$id" : '/admin/material_form.php';
    header("Location: $redirect");
    exit;
}

// Сохраняем в БД
$db = getDB();

if ($is_edit) {
    $stmt = $db->prepare(
        'UPDATE materials 
         SET name = ?, formula = ?, category_id = ?, unit = ?, 
             quantity = ?, threshold = ?, location = ?
         WHERE id = ?'
    );
    $stmt->execute([$name, $formula, $category_id, $unit, $quantity, $threshold, $location, $id]);
} else {
    $stmt = $db->prepare(
        'INSERT INTO materials (name, formula, category_id, unit, quantity, threshold, location) 
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$name, $formula, $category_id, $unit, $quantity, $threshold, $location]);
}

// Успешно — на список материалов
header('Location: /admin/materials.php');
exit;
?>