<?php
// Configuración para PHPMailer con Gmail
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

function enviarCorreoRecuperacion($correo, $token) {
    $mail = new PHPMailer(true);
    try {
        // Configuración del servidor SMTP de Gmail
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'rene.merino.ci@iestpvillamaria.edu.pe'; // Cambia por tu correo
        $mail->Password = 'tkch tfza bhqq fhpz'; // Cambia por tu contraseña de aplicación
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('rene.merino.ci@iestpvillamaria.edu.pe', 'Boutique');
        $mail->addAddress($correo);
        $mail->isHTML(true);
        $mail->Subject = 'Recuperación de contraseña';
        $mail->Body = 'Haz clic en el siguiente enlace para recuperar tu contraseña:<br>' .
            '<a href="http://localhost/TrabajoFinal_Thalia/restablecer_contraseña.php?token=' . $token . '">Recuperar contraseña</a>';
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>