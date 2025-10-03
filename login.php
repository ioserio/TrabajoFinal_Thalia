<?php
session_start();
require_once __DIR__ . '/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.html');
    exit;
}

$correo = isset($_POST['correo']) ? trim($_POST['correo']) : '';
$password = $_POST['password'] ?? '';

if ($correo === '' || $password === '') {
    header('Location: login.html?error=1');
    exit;
}

$stmt = $conn->prepare('SELECT id, nombres, password FROM usuarios WHERE correo = ? LIMIT 1');
if (!$stmt) {
    header('Location: login.html?error=1');
    exit;
}
$stmt->bind_param('s', $correo);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 1) {
    $stmt->bind_result($id, $nombres, $hash);
    $stmt->fetch();
    if (password_verify($password, $hash)) {
        $_SESSION['user_id'] = $id;
        $_SESSION['nombres'] = $nombres;
    header('Location: index.php');
        exit;
    }
}

header('Location: login.html?error=1');
exit;
