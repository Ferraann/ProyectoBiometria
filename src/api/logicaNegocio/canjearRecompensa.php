<?php
// ------------------------------------------------------------------
// Fichero: canjearRecompensa.php
// Autor: Manuel
// Fecha: 11/12/2025
// ------------------------------------------------------------------
// Descripción:
//  Función para canjear una recompensa. Resta puntos y envía un correo 
//  de confirmación con el código de canje.
// ------------------------------------------------------------------

// Asegúrate de incluir los archivos de PHPMailer
require_once __DIR__ . '/mailer.php'; 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Función para canjear una recompensa
 * @param mysqli $conn Conexión a la base de datos.
 * @param array $data Debe contener 'id_usuario', 'costo_puntos', 'nombre_recompensa', y opcionalmente 'codigo_canje'.
 * @return array Resultado de la operación.
 */
function canjearRecompensa($conn, $data)
{
    /* ---------- 1. Validaciones y saneado ---------- */
    if (!isset($data['id_usuario'], $data['costo_puntos'], $data['nombre_recompensa'])) {
        return ["status" => "error", "mensaje" => "Faltan datos obligatorios para el canje."];
    }

    $idUsuario = (int)$data['id_usuario'];
    $costoPuntos = (int)$data['costo_puntos'];
    $nombreRecompensa = trim($data['nombre_recompensa']);
    // Asumimos que la recompensa ya tiene un código, si no, generarlo aquí.
    $codigoCanje = $data['codigo_canje'] ?? 'CANJE-' . strtoupper(bin2hex(random_bytes(4)));

    if ($idUsuario <= 0 || $costoPuntos <= 0) {
        return ["status" => "error", "mensaje" => "ID de usuario o costo de puntos no válidos."];
    }

    /* ---------- 2. Verificar usuario y puntos ---------- */
    // Obtenemos los datos actuales y puntos del usuario
    $stmt = $conn->prepare("SELECT nombre, puntos, gmail FROM usuario WHERE id = ?");
    $stmt->bind_param("i", $idUsuario);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        return ["status" => "error", "mensaje" => "El usuario no existe."];
    }

    $usuario = $res->fetch_assoc();
    $stmt->close();

    if ($usuario['puntos'] < $costoPuntos) {
        return ["status" => "error", "mensaje" => "Puntos insuficientes. Puntos actuales: {$usuario['puntos']}."];
    }

    /* ---------- 3. Restar puntos (Actualización de la BD) ---------- */
    $sqlRestar = "UPDATE usuario SET puntos = puntos - ? WHERE id = ?";
    $stmtRestar = $conn->prepare($sqlRestar);

    if (!$stmtRestar) {
        return ["status" => "error", "mensaje" => "Error al preparar la resta de puntos: " . $conn->error];
    }

    $stmtRestar->bind_param("ii", $costoPuntos, $idUsuario);

    if (!$stmtRestar->execute()) {
        $error = $stmtRestar->error;
        $stmtRestar->close();
        return ["status" => "error", "mensaje" => "Error al restar puntos: " . $error];
    }
    $stmtRestar->close();
    
    // Puntos restados con éxito. Procedemos al email.

    /* ---------- 4. Enviar correo de confirmación de canje ---------- */
    $nombre = $usuario['nombre'];
    $gmail = $usuario['gmail'];
    $puntosRestantes = $usuario['puntos'] - $costoPuntos;

    $asunto  = "Confirmación de Canje: {$nombreRecompensa}";
    $cuerpo  = "<h2>¡Felicidades, $nombre!</h2>
        <p>Has canjeado exitosamente la recompensa **{$nombreRecompensa}**.</p>
        <p>Te hemos descontado **{$costoPuntos} puntos**.</p>
        
        <h3>Detalles de la Recompensa:</h3>
        <p><strong>Código de Canje:</strong> <span style='font-size: 1.2em; color: #152D9A; font-weight: bold;'>$codigoCanje</span></p>
        <p>Muestra este código para reclamar tu premio.</p>
        
        <p>Tus puntos restantes son: **{$puntosRestantes}**.</p>
        <p>¡Gracias por participar!</p>";

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'no.reply.aither@gmail.com';
        $mail->Password   = 'esdf lkoc qprz rkum';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('noreply@aither.com', 'AITHER Recompensas');
        $mail->addAddress($gmail);
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $cuerpo;

        $mail->send();
        
    } catch (Exception $e) {
        error_log('PHPMailer error al enviar canje: ' . $mail->ErrorInfo);
        return [
            "status" => "ok", 
            "mensaje" => "Canje exitoso, pero no se pudo enviar el correo de confirmación (puntos restados). Contacta con soporte."
        ];
    }

    return [
        "status" => "ok", 
        "mensaje" => "¡Canje exitoso! Se restaron {$costoPuntos} puntos. Revisa tu correo ({$gmail}) para los detalles de la recompensa."
    ];
}
?>