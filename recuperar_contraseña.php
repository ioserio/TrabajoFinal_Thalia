<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="login-container">
        <a href="login.html" title="Volver al login" aria-label="Volver al login" style="position:absolute; top:12px; right:12px; color:var(--rose-2); text-decoration:underline; font-weight:700; font-size:.95rem;">Volver al login</a>
        <h2>Recuperar Contraseña</h2>
        <form action="recuperar_contraseña.php" method="POST">
            <label for="correo">Ingresa tu correo electrónico:</label>
            <input type="email" id="correo" name="correo" required autocomplete="email">
            <button type="submit">Enviar instrucciones</button>
        </form>
        <div id="mensaje-recuperacion" style="display:none;"></div>
    </div>
</body>
</html>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include 'conexion.php';
    include 'correo_funciones.php';
    $correo = $_POST['correo'];
    $sql = "SELECT * FROM usuarios WHERE correo = '$correo'";
    $resultado = mysqli_query($conn, $sql);
    if (mysqli_num_rows($resultado) > 0) {
        // Generar token único
        $token = bin2hex(random_bytes(32));
        // Guardar token en la base de datos (puedes crear una tabla 'recuperaciones' o agregar campo 'token_recuperacion' en usuarios)
        $sqlToken = "UPDATE usuarios SET token_recuperacion = '$token', token_expira = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE correo = '$correo'";
        mysqli_query($conn, $sqlToken);
        // Enviar correo
        if (enviarCorreoRecuperacion($correo, $token)) {
            echo "<script>document.getElementById('mensaje-recuperacion').style.display='block';document.getElementById('mensaje-recuperacion').innerText='Se han enviado instrucciones a tu correo.';</script>";
        } else {
            echo "<script>document.getElementById('mensaje-recuperacion').style.display='block';document.getElementById('mensaje-recuperacion').innerText='Error al enviar el correo.';</script>";
        }
    } else {
        echo "<script>document.getElementById('mensaje-recuperacion').style.display='block';document.getElementById('mensaje-recuperacion').innerText='El correo no está registrado.';</script>";
    }
    mysqli_close($conn);
}
?>
