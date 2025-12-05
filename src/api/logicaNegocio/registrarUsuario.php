<?php
// ------------------------------------------------------------------
// Fichero: registrarUsuario.php
// Autor: Manuel
// Fecha: 5/12/2025
// ------------------------------------------------------------------
// Descripción:
//  Función responsable del registro de usuarios. Valida datos,
//  verifica el correo, genera token y envía email de activación.
// ------------------------------------------------------------------

require_once __DIR__ . '/mailer.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function registrarUsuario($conn, $data)
{
    /* ---------- 1. Validaciones y saneado ---------- */
    if (!isset($data['nombre'], $data['apellidos'], $data['gmail'], $data['password'])) {
        return ["status" => "error", "mensaje" => "Faltan datos obligatorios para el registro."];
    }

    $nombre    = trim($data['nombre']);
    $apellidos = trim($data['apellidos']);
    $gmail     = trim($data['gmail']);
    $password  = trim($data['password']);
    
    // Validar formato de correo
    if (!filter_var($gmail, FILTER_VALIDATE_EMAIL)) {
        return ["status" => "error", "mensaje" => "El formato del correo electrónico no es válido."];
    }
    
    $hash      = password_hash($password, PASSWORD_DEFAULT);

    /* ---------- 2. ¿Existe el correo? ---------- */
    $stmt = $conn->prepare("SELECT id, activo FROM usuario WHERE gmail = ?");
    $stmt->bind_param("s", $gmail);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows) {
        $usr = $res->fetch_assoc();

        if ($usr['activo'] == 1) {
            return ["status" => "error", "mensaje" => "El correo ya está registrado y activado."];
        }

        /* usuario inactivo → lo borramos */
        $del = $conn->prepare("DELETE FROM usuario WHERE id = ?");
        $del->bind_param("i", $usr['id']);
        $del->execute();
    }

    /* ---------- 3. Crear token seguro ---------- */
    $token = bin2hex(random_bytes(32)); // 64 caracteres
    $token_expira = date("Y-m-d H:i:s", time() + 900); // 15 min de validez

    /* ---------- 4. Insertar nuevo usuario (inactivo + token + expiración) ---------- */
    $ins = $conn->prepare("INSERT INTO usuario 
        (nombre, apellidos, gmail, password, activo, token, token_expira)
        VALUES (?, ?, ?, ?, 0, ?, ?)");
    $ins->bind_param("ssssss", $nombre, $apellidos, $gmail, $hash, $token, $token_expira);

    if (!$ins->execute()) {
        return ["status" => "error", "mensaje" => "Error al registrar el usuario: " . $conn->error];
    }

    /* ---------- 5. Enviar correo de activación ---------- */
    $enlace = "https://fsanpra.upv.edu.es/src/html/activacion.html?token=" . urlencode($token);

    $asunto  = "Activa tu cuenta en AITHER";
    $cuerpo  = "<h2>¡Hola $nombre!</h2>
        <p>Gracias por registrarte. Pulsa el botón para activar tu cuenta:</p>
        <p><a href='$enlace' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:4px;'>Activar cuenta</a></p>
        <p>Si el botón no funciona, copia y pega esta dirección:<br>$enlace</p>";

    $enviado = false;

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'no.reply.aither@gmail.com';
        $mail->Password   = 'esdf lkoc qprz rkum';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('noreply@aither.com', 'AITHER');
        $mail->addAddress($gmail);
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $cuerpo;

        $mail->send();
        $enviado = true;

    } catch (Exception $e) {
        error_log('PHPMailer error: ' . $mail->ErrorInfo);
        return ["status" => "ok", "mensaje" => "Usuario registrado correctamente, pero no se pudo enviar el correo de activación. Contacta con soporte."];
    }

    return ["status" => "ok", "mensaje" => "Usuario registrado correctamente. Revisa tu correo para activarlo."];
}
?>