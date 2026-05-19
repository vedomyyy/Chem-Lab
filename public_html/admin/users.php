<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/header.php';

$db = getDB();

// Обработка создания нового пользователя
$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'create') {
        // Создание пользователя администратором
        $username  = trim($_POST['username']  ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $password  = $_POST['password']       ?? '';
        $role      = $_POST['role']           ?? 'lab_assistent';

        $validRoles = ['lab_assistent', 'teacher', 'admin'];

        if (!$username || !$full_name || !$password) {
            $error = 'Заполните все обязательные поля.';
        } elseif (!in_array($role, $validRoles)) {
            $error = 'Недопустимая роль.';
        } else {
            $stmt = $db->prepare('SELECT id FROM users WHERE username = ?');
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error = 'Логин уже занят.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $db->prepare(
                    'INSERT INTO users (username, password_hash, role, full_name) VALUES (?, ?, ?, ?)'
                )->execute([$username, $hash, $role, $full_name]);
                $success = 'Пользователь создан.';
            }
        }
    }

    if ($_POST['action'] === 'delete' && !empty($_POST['user_id'])) {
        $uid = (int)$_POST['user_id'];
        // Нельзя удалить самого себя
        if ($uid === (int)$_SESSION['user_id']) {
            $error = 'Нельзя удалить собственный аккаунт.';
        } else {
            $db->prepare('DELETE FROM users WHERE id = ?')->execute([$uid]);
            $success = 'Пользователь удалён.';
        }
    }

    if ($_POST['action'] === 'change_role' && !empty($_POST['user_id']) && !empty($_POST['new_role'])) {
        $uid      = (int)$_POST['user_id'];
        $new_role = $_POST['new_role'];
        $validRoles = ['lab_assistent', 'teacher', 'admin'];
        if (in_array($new_role, $validRoles)) {
            $db->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$new_role, $uid]);
            $success = 'Роль изменена.';
        }
    }
}

// Список всех пользователей
$users = $db->query(
    'SELECT id, username, full_name, role, created_at FROM users ORDER BY created_at DESC'
)->fetchAll();

$roleNames = [
    'admin'         => 'Администратор',
    'teacher'       => 'Преподаватель',
    'lab_assistent' => 'Лаборант',
];
?>

<h2>Управление пользователями</h2>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<!-- Форма добавления нового пользователя -->
<div class="card mb-4">
    <div class="card-header"><strong>Добавить пользователя</strong></div>
    <div class="card-body">
        <form method="post" class="row g-2">
            <input type="hidden" name="action" value="create">
            <div class="col-md-3">
                <input type="text" name="username" class="form-control" placeholder="Логин *" required>
            </div>
            <div class="col-md-3">
                <input type="text" name="full_name" class="form-control" placeholder="Полное имя *" required>
            </div>
            <div class="col-md-2">
                <input type="password" name="password" class="form-control" placeholder="Пароль *" required>
            </div>
            <div class="col-md-2">
                <select name="role" class="form-select">
                    <option value="lab_assistent">Лаборант</option>
                    <option value="teacher">Преподаватель</option>
                    <option value="admin">Администратор</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-success w-100">Добавить</button>
            </div>
        </form>
    </div>
</div>

<!-- Таблица пользователей -->
<table class="table table-bordered table-hover">
    <thead>
        <tr>
            <th>Логин</th>
            <th>Полное имя</th>
            <th>Роль</th>
            <th>Дата создания</th>
            <th>Действия</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($users as $u): ?>
        <tr>
            <td><?= htmlspecialchars($u['username']) ?></td>
            <td><?= htmlspecialchars($u['full_name']) ?></td>
            <td>
                <!-- Быстрая смена роли -->
                <form method="post" class="d-inline">
                    <input type="hidden" name="action" value="change_role">
                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                    <select name="new_role" class="form-select form-select-sm d-inline w-auto"
                            onchange="this.form.submit()">
                        <?php foreach ($roleNames as $val => $label): ?>
                            <option value="<?= $val ?>" <?= $u['role'] === $val ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            </td>
            <td><?= date('d.m.Y', strtotime($u['created_at'])) ?></td>
            <td>
                <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
                    <form method="post" class="d-inline"
                          onsubmit="return confirm('Удалить пользователя <?= htmlspecialchars($u['username']) ?>?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Удалить</button>
                    </form>
                <?php else: ?>
                    <span class="text-muted">— вы —</span>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<a href="/admin/index.php" class="btn btn-outline-secondary mt-2">← В админку</a>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>