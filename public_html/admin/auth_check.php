<?php
// Проверка авторизации и роли администратора
// Если сессия не запущена — запускаем
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Если не авторизован или не администратор — редирект на логин
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /login.php'); // Исправлен баг: добавлено двоеточие после Location
    exit;
}