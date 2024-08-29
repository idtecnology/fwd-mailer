<?php
require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;






// $dsn = "mysql:host={$_ENV['BD_HOST']};dbname={$_ENV['BD_NAME']}";
// $usuario = $_ENV['BD_USERNAME'];
// $contraseña = $_ENV['DB_PASSWORD'];

// try {
//     $conexion = new PDO($dsn, $usuario, $contraseña);
//     $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
// } catch (PDOException $e) {
//     echo 'Error de conexión: ' . $e->getMessage();
// }

// $stmt = $conexion->prepare('SELECT * FROM ejecutivos_email');
// $stmt->execute();
// $rst = $stmt->fetchAll();




$forwardTo = [];
// foreach ($rst as $key => $datos) {
//     if ($datos['active'] == 1) {
//         $forwardTo[$key] = $datos['email'];
//     }
// }

$date = date('Y-m-d');

if (!is_dir('./logs/' . $date)) {
    mkdir('./logs/' . $date, 0755, true);
}

$logFile = __DIR__ . '/logs/' . $date . '/correos_' . $date . '.log';
$handle = fopen($logFile, 'a'); // Abre en modo append


$hostname = $_ENV['EMAIL_HOST'];
$username = $_ENV['EMAIL_USERNAME'];
$password = $_ENV['EMAIL_PASSWORD'];

$counter = count($forwardTo);
$counter = $counter - 1;

$inbox = imap_open($hostname, $username, $password) or die('No se puede conectar al correo: ' . imap_last_error());
$emails = imap_search($inbox, 'UNSEEN');

if ($emails) {
    rsort($emails);

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = $username;
    $mail->Password = $password;
    $mail->SMTPSecure = 'ssl';
    $mail->Port = 465;

    foreach ($emails as $email_number) {
        $r = rand(0, $counter);

        $overview = imap_fetch_overview($inbox, $email_number, 0);
        $messagePlain = imap_fetchbody($inbox, $email_number, 1);
        preg_match('/<([^>]+)>/', $overview[0]->from, $matches);
        $parts = explode('<', $overview[0]->from);
        try {
            $mail->clearAddresses();
            $mail->setFrom($matches[1], 'Fwd FROM: ' . $matches[1]);
            $mail->addAddress($forwardTo[$r]);
            $mail->addReplyTo($matches[1], trim($parts[0]));
            $mail->Subject = 'Fwd: ' . $overview[0]->subject;
            $mail->Body = quoted_printable_decode($messagePlain);
            fwrite($logFile, "[" . date('Y-m-d H:i:s') . "] - Enviado:  $forwardTo[$r] \n");
            fclose($handle);
            $mail->send();
        } catch (Exception $e) {
            fwrite($logFile, "[" . date('Y-m-d H:i:s') . "] - Error message:  $mail->ErrorInfo \n");
            fclose($handle);

            fwrite($logFile, "[" . date('Y-m-d H:i:s') . "] - Error enviado a:  $forwardTo[$r] \n");
            fclose($handle);
        }

        $stmt = $conexion->prepare("INSERT INTO derivacion_email (email_from, email_to, fecha) VALUES (?, ?, ?)");

        $stmt->bindParam(1, $matches[1]);
        $stmt->bindParam(2, $forwardTo[$r]);
        $currentDate = date('Y-m-d H:i:s');
        $stmt->bindParam(3, $currentDate);

        $stmt->execute();

        if (
            $stmt->rowCount() > 0
        ) {

            fwrite($logFile, "[" . date('Y-m-d H:i:s') . "] - Guardado:  $forwardTo[$r] \n");
            fclose($handle);
        } else {
            fwrite($logFile, "[" . date('Y-m-d H:i:s') . "] - Error inserting new record \n");
            fclose($handle);
        }
    }
} else {
    fwrite($handle, "[" . date('Y-m-d H:i:s') . "] - No hay correos \n");
    fclose($handle);
}

imap_close($inbox);
