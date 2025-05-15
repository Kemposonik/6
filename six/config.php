<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$db_server = "localhost";
$db_name = 'u68996';
$db_user = 'u68996';
$db_pass = '2920883';

// Подключение к БД
$conn = new mysqli($db_server, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Не удалось установить соединение:" . $conn->connect_error);
}
$conn->set_charset("utf8");


?>