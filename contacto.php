<?php
require_once __DIR__ . '/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$nombre = trim($_POST['nombre'] ?? '');
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$asunto = trim($_POST['asunto'] ?? '');
$mensaje = trim($_POST['mensaje'] ?? '');

if ($nombre === '' || !$email || $mensaje === '') {
    header('Location: index.php?contact=0');
    exit;
}

// Tabla sugerida:
// CREATE TABLE IF NOT EXISTS contactos (
//   id INT AUTO_INCREMENT PRIMARY KEY,
//   nombre VARCHAR(120) NOT NULL,
//   email VARCHAR(150) NOT NULL,
//   asunto VARCHAR(180) NULL,
//   mensaje TEXT NOT NULL,
//   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

$stmt = $conn->prepare('INSERT INTO contactos (nombre, email, asunto, mensaje) VALUES (?, ?, ?, ?)');
if (!$stmt) {
    header('Location: index.php?contact=0');
    exit;
}
$stmt->bind_param('ssss', $nombre, $email, $asunto, $mensaje);
if ($stmt->execute()) {
    header('Location: index.php?contact=1');
} else {
    header('Location: index.php?contact=0');
}
$stmt->close();
$conn->close();
exit;
