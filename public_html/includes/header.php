<?php
// Запускаем сессию (если ещё не запущена)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ХимЛаб — Лабораторный журнал</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/style.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand fw-bold" href="/index.php">ХимЛаб</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav me-auto">
                <?php if (!empty($_SESSION['user_id'])): ?>
                    <!-- Главная (с учётом роли) -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?= ($_SESSION['role'] ?? '') === 'admin' ? '/admin/index.php' : '/index.php' ?>">
                            Главная
                        </a>
                    </li>
                    <!-- Склад — для всех авторизованных -->
                    <li class="nav-item">
                        <a class="nav-link" href="/materials.php">Склад</a>
                    </li>
                    <!-- Журнал — для всех -->
                    <li class="nav-item">
                        <a class="nav-link" href="/history.php">История операций</a>
                    </li>
                    <!-- Отчёты — только для преподавателей и администраторов -->
                    <?php if (in_array($_SESSION['role'], ['teacher', 'admin'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/reports.php">Отчёты</a>
                    </li>
                    <?php endif; ?>
                    <!-- Уведомления -->
                    <li class="nav-item">
                        <a class="nav-link" href="/notifications.php">
                            Уведомления
                            <?php
                            // Показываем счётчик материалов с низким остатком
                            try {
                                $db = getDB();
                                $low = $db->query('SELECT COUNT(*) FROM materials WHERE quantity < threshold')->fetchColumn();
                                if ($low > 0): ?>
                                    <span class="badge bg-warning text-dark"><?= $low ?></span>
                            <?php endif;
                            } catch (Exception $e) {}
                            ?>
                        </a>
                    </li>
                    <!-- Администрирование — только для admin -->
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Администрирование</a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/admin/materials.php">Управление материалами</a></li>
                            <li><a class="dropdown-item" href="/admin/categories.php">Категории</a></li>
                            <li><a class="dropdown-item" href="/admin/users.php">Пользователи</a></li>
                            <li><a class="dropdown-item" href="/admin/suppliers.php">Поставщики</a></li>
                        </ul>
                    </li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav ms-auto">
                <?php if (!empty($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <span class="nav-link text-light">
                             <?= htmlspecialchars($_SESSION['full_name']) ?>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/logout.php">Выйти</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/login.php">Войти</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">