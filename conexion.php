<?php
// Archivo de conexión a la base de datos boutique
$host = 'localhost';
$user = 'root';
$password = '';
$db = 'boutique';

$conn = new mysqli($host, $user, $password, $db);

if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}
// Puedes usar $conn para tus consultas
?>