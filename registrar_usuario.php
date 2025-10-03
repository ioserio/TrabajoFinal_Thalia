<?php
include 'conexion.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dni = $_POST['dni'] ?? '';
    $nombres = $_POST['nombres'] ?? '';
    $correo = $_POST['correo'] ?? '';
    $telefono = $_POST['telefono'] ?? '';
    $genero = $_POST['genero'] ?? '';
    $password = $_POST['password_reg'] ?? '';
    $terminos = isset($_POST['terminos']) ? 1 : 0;

    // Encriptar la contraseña
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO usuarios (dni, nombres, correo, telefono, genero, password, terminos) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('ssssssi', $dni, $nombres, $correo, $telefono, $genero, $password_hash, $terminos);

    if ($stmt->execute()) {
        header('Location: login.html?success=1');
        exit;
    } else {
        echo "Error al registrar: " . $conn->error;
    }
    $stmt->close();
    $conn->close();
} else {
    echo "Acceso no permitido";
}
?>