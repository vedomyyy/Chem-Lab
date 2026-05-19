<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';

$db = getDB();

// Статистика для дашборда
$totalMaterials  = $db->query('SELECT COUNT(*) FROM materials')->fetchColumn();
$lowStockCount   = $db->query('SELECT COUNT(*) FROM materials WHERE quantity < threshold')->fetchColumn();
$totalUsers      = $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
$totalTodayTx    = $db->query('SELECT COUNT(*) FROM transactions WHERE DATE(created_at) = CURDATE()')->fetchColumn();
?>

<h2>Панель администратора</h2>

<p>Добро пожаловать, <strong><?= htmlspecialchars($_SESSION['full_name']) ?></strong>!</p>

<!-- Дашборд со статистикой -->
<div class="row mt-4 mb-4">
    <div class="col-md-3 mb-3">
        <div class="card text-center border-primary">
            <div class="card-body">
                <h2 class="text-primary"><?= $totalMaterials ?></h2>
                <p class="mb-0">Материалов на складе</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card text-center border-warning">
            <div class="card-body">
                <h2 class="text-warning"><?= $lowStockCount ?></h2>
                <p class="mb-0">Мало на складе</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card text-center border-success">
            <div class="card-body">
                <h2 class="text-success"><?= $totalUsers ?></h2>
                <p class="mb-0">Пользователей</p>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card text-center border-info">
            <div class="card-body">
                <h2 class="text-info"><?= $totalTodayTx ?></h2>
                <p class="mb-0">Операций сегодня</p>
            </div>
        </div>
    </div>
</div>

<!-- Ссылки на разделы -->
<div class="row">
    <div class="col-md-4 mb-3">
        <div class="card">
            <div class="card-body text-center">
                <h5 class="card-title">Материалы</h5>
                <p class="card-text">Добавление, редактирование, удаление</p>
                <a href="/admin/materials.php" class="btn btn-primary">Перейти</a>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card">
            <div class="card-body text-center">
                <h5 class="card-title">Пользователи</h5>
                <p class="card-text">Управление учётными записями</p>
                <a href="/admin/users.php" class="btn btn-primary">Перейти</a>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card">
            <div class="card-body text-center">
                <h5 class="card-title">Категории</h5>
                <p class="card-text">Управление категориями материалов</p>
                <a href="/admin/categories.php" class="btn btn-primary">Перейти</a>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card">
            <div class="card-body text-center">
                <h5 class="card-title">Поставщики</h5>
                <p class="card-text">Управление поставщиками</p>
                <a href="/admin/suppliers.php" class="btn btn-primary">Перейти</a>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card">
            <div class="card-body text-center">
                <h5 class="card-title">История операций</h5>
                <p class="card-text">Все движения материалов</p>
                <a href="/history.php" class="btn btn-outline-primary">Перейти</a>
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-3">
        <div class="card">
            <div class="card-body text-center">
                <h5 class="card-title">Отчёты</h5>
                <p class="card-text">Аналитика и экспорт данных</p>
                <a href="/reports.php" class="btn btn-outline-primary">Перейти</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>