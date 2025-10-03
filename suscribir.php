<?php
require_once __DIR__ . '/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
if (!$email) {
    header('Location: index.php?sub=0'); // inválido
    exit;
}

// Insertar o marcar duplicado sin error (requiere índice UNIQUE en email)
// SQL recomendado para la tabla (ejecutar una vez en tu BD):
// CREATE TABLE IF NOT EXISTS suscriptores (
//   id INT AUTO_INCREMENT PRIMARY KEY,
//   email VARCHAR(150) NOT NULL UNIQUE,
//   created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
// ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

$sql = "INSERT INTO suscriptores (email) VALUES (?) ON DUPLICATE KEY UPDATE email = email";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    header('Location: index.php?sub=0');
    exit;
}
$stmt->bind_param('s', $email);
$stmt->execute();

// affected_rows: 1 = insertado, 0 = duplicado (porque email=email no cambia)
if ($stmt->affected_rows === 1) {
    header('Location: index.php?sub=1'); // éxito
} else {
    header('Location: index.php?sub=dup'); // ya estaba suscrito
}
$stmt->close();
$conn->close();
exit;
