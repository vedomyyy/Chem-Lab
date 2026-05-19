<?php
require_once 'includes/db.php';
require_once 'includes/header.php';
?>

<h2>Лабораторный журнал</h2>

<?php if (!empty($_SESSION['user_id'])): ?>
    <div class="alert alert-success">
        Добро пожаловать, <strong><?= htmlspecialchars($_SESSION['full_name']) ?></strong>!
    </div>

    <div class="row mt-3">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Ваш профиль</div>
                <div class="card-body">
                    <p><strong>Логин:</strong> <?= htmlspecialchars($_SESSION['username']) ?></p>
                    <p><strong>Роль:</strong>
                        <?php
                        $roles = [
                            'admin'         => 'Администратор',
                            'teacher'       => 'Преподаватель',
                            'lab_assistent' => 'Лаборант',
                        ];
                        echo $roles[$_SESSION['role']] ?? $_SESSION['role'];
                        ?>
                    </p>
                    <a href="/logout.php" class="btn btn-outline-danger btn-sm">Выйти</a>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <p class="text-muted">Для работы с системой необходимо <a href="/login.php">войти</a>.</p>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>