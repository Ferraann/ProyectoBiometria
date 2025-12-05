<?php
// ------------------------------------------------------------------
// Fichero: mailer.php
// Autor: Manuel
// Fecha: 5/12/2025
// ------------------------------------------------------------------
// Descripción:
//  Se importan las librerías PHPMailer utilizadas por las funciones
//  que envían correos (ej. registro/activación de usuario).
// ------------------------------------------------------------------

require __DIR__ . '/../libs/PHPMailer-7.0.0/src/Exception.php';
require __DIR__ . '/../libs/PHPMailer-7.0.0/src/PHPMailer.php';
require __DIR__ . '/../libs/PHPMailer-7.0.0/src/SMTP.php';

//copia esto en la parte superior del archivo
// require_once __DIR__ . '/mailer.php';
// use PHPMailer\PHPMailer\PHPMailer;
// use PHPMailer\PHPMailer\Exception;
?>