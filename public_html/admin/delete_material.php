<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../includes/db.php';

// Проверяем, что передан id
$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: /admin/materials.php');
    exit;
}

$db = getDB();

// Удаляем материал
$stmt = $db->prepare('DELETE FROM materials WHERE id = ?');
$stmt->execute([$id]);

// Перенаправляем обратно на список
header('Location: /admin/materials.php');
exit;
?>