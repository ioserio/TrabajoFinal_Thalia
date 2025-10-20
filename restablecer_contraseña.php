<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="login-container">
        <a href="login.html" class="home-link" title="Volver al login" aria-label="Volver al login">&larr; Volver al login</a>
        <h2>Restablecer Contraseña</h2>
        <?php
        include 'conexion.php';
        $token = isset($_GET['token']) ? $_GET['token'] : '';
        $error = '';
        $exito = '';
        if ($token) {
            $sql = "SELECT * FROM usuarios WHERE token_recuperacion = '$token' AND token_expira > NOW()";
            $resultado = mysqli_query($conn, $sql);
            if (mysqli_num_rows($resultado) > 0) {
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $nueva = $_POST['nueva_contraseña'];
                    $hash = password_hash($nueva, PASSWORD_DEFAULT);
                    $sqlUpdate = "UPDATE usuarios SET password = '$hash', token_recuperacion = NULL, token_expira = NULL WHERE token_recuperacion = '$token'";
                    if (mysqli_query($conn, $sqlUpdate)) {
                        $exito = '¡Contraseña restablecida correctamente!';
                    } else {
                        $error = 'Error al actualizar la contraseña.';
                    }
                }
                if ($exito) {
                    echo '<div style="color:green;text-align:center;">'.$exito.'</div>';
                } else {
        ?>
        <form action="restablecer_contraseña.php?token=<?php echo $token; ?>" method="POST">
            <label for="nueva_contraseña">Nueva contraseña:</label>
            <input type="password" id="nueva_contraseña" name="nueva_contraseña" required>
            <button type="submit">Restablecer</button>
        </form>
        <?php
                }
                if ($error) {
                    echo '<div style="color:red;text-align:center;">'.$error.'</div>';
                }
            } else {
                echo '<div style="color:red;text-align:center;">Token inválido o expirado.</div>';
            }
        } else {
            echo '<div style="color:red;text-align:center;">Token no proporcionado.</div>';
        }
        mysqli_close($conn);
        ?>
    </div>
</body>
</html>
