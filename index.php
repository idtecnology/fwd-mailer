<?php
require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$dsn = "mysql:host={$_ENV['BD_HOST']};dbname={$_ENV['BD_NAME']}";
$usuario = $_ENV['BD_USERNAME'];
$contraseña = $_ENV['DB_PASSWORD'];

try {
    $conexion = new PDO($dsn, $usuario, $contraseña);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'Error de conexión: ' . $e->getMessage();
}

$stmt = $conexion->prepare('SELECT * FROM ejecutivos_email');
$stmt->execute();
$rst = $stmt->fetchAll();



$forwardTo = [];
foreach ($rst as $key => $datos) {
    if ($datos['active'] == 1) {
        $forwardTo[$key] = $datos['email'];
    }
}

$date = date('Y-m-d');

if (!is_dir('./logs/' . $date)) {
    mkdir('./logs/' . $date, 0755, true);
}

$logFile = __DIR__ . '/logs/' . $date . '/correos_' . $date . '.log';
$logger = new Logger('fwd-mailer');
$logger->pushHandler(new StreamHandler($logFile, Logger::INFO));

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
            $logger->info('Enviado a: ' . $forwardTo[$r]);
            $mail->send();
        } catch (Exception $e) {
            $logger->info('Error enviado a: ' . $forwardTo[$r]);
            $logger->info('Error message: ' . $mail->ErrorInfo);
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

            $logger->info('Guardado: ' . $forwardTo[$r]);
        } else {
            $logger->info("Error inserting new record");
        }
    }
} else {
    $logger->info("No hay correos no leídos.\n");
}

imap_close($inbox);
