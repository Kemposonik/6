<?php
require_once 'config.php';

if (!isset($_SESSION['logged_in']) || empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Проверка прав администратора


// Обработка удаления пользователя
if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    if ($del_id !== $_SESSION['user_id']) { // нельзя удалять себя
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $del_id);
        $stmt->execute();
        $stmt->close();
    }
    header("Location: admin_panel.php");
    exit;
}

// Получение списка пользователей
$sql = "SELECT u.id,u.name,u.email,u.phone, u.login, u.is_admin, a.id as application_id 
        FROM users u 
        LEFT JOIN application a ON u.application_id = a.id";

$result = $conn->query($sql);
$users = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ-панель</title>
    <link rel="stylesheet" href="main1.css">
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<style>
body {  
            background-image:url("five.jpg");
            background-size:cover;
     }
     td{
        background-color:white;
     }
     h1{
        color:white;
     }
        
    </style>
<body>
    <div>
    <h1>Админ-панель пользователей</h1></div>
    <div class="logout-container" align='left'>
        <span style="color:white"><?= htmlspecialchars($_SESSION['user_login'] ?? 'User') ?></span>
        <a href="logout.php" class="logout-btn" align=left>Выход</a>
    </div>
    <table>
        <tr>
            <th>ID</th>
            <th>Login</th>
            <th>Имя</th>
            <th>Phone</th>
            <th>Email</th>
            <th>is_admin</th>
            <th>App ID</th>
            <th>Действия</th>
        </tr>
        <?php foreach ($users as $user): ?>
        <tr>
            <td><?= htmlspecialchars($user['id']) ?></td>
            <td><?= htmlspecialchars($user['login']) ?></td>
            <td><?= htmlspecialchars($user['name']) ?></td>
            <td><?= htmlspecialchars($user['phone']) ?></td>
            <td><?= htmlspecialchars($user['email']) ?></td>
            <td><?= $user['is_admin'] ? 'Да' : 'Нет' ?></td>
            <td><?= htmlspecialchars($user['application_id']) ?></td>
            <td>
                <a href="edit_user.php?id=<?= $user['id'] ?>">✏️</a>
                <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                <a style="color:white" href="admin_panel.php?delete_id=<?= $user['id'] ?>" onclick="return confirm('Удалить пользователя?');">🗑️</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <a style="color:white" href="add_user.php" align='right'> ➕ Добавить нового пользователя</a>
</body>
</html>
