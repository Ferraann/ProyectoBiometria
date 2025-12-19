<?php
/**
 * @file canjearRecompensa.php
 * @brief Lógica de negocio para el canje de puntos por recompensas.
 * @details Gestiona el flujo completo de canje: validación de saldo, deducción de puntos en la base de datos
 * y notificación automática al usuario mediante PHPMailer.
 * @author Manuel
 * @date 11/12/2025
 */

require_once __DIR__ . '/mailer.php'; 
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * @brief Procesa el canje de una recompensa para un usuario específico.
 * @param mysqli $conn Conexión activa a la base de datos.
 * @param array $data {
 * @var int $id_usuario ID del cliente.
 * @var int $costo_puntos Puntos a deducir.
 * @var string $nombre_recompensa Nombre descriptivo del premio.
 * @var string|null $codigo_canje (Opcional) Código predefinido.
 * }
 * @return array Resultado de la transacción con estado y mensaje.
 */
function canjearRecompensa($conn, $data)
{
    // ----------------------------------------------------------------------------------------
    // 1. VALIDACIÓN Y SANEADO
    // ----------------------------------------------------------------------------------------

    /** @section ValidacionEntrada Comprobación de integridad de los datos recibidos. */
    if (!isset($data['id_usuario'], $data['costo_puntos'], $data['nombre_recompensa'])) {
        return ["status" => "error", "mensaje" => "Faltan datos obligatorios para el canje."];
    }

    /** @var int $idUsuario */
    $idUsuario = (int)$data['id_usuario'];
    /** @var int $costoPuntos */
    $costoPuntos = (int)$data['costo_puntos'];
    /** @var string $nombreRecompensa */
    $nombreRecompensa = trim($data['nombre_recompensa']);
    
    /** * @var string $codigoCanje Código único de identificación del canje.
     * @note Si no se provee, se genera un hash aleatorio criptográficamente seguro.
     */
    $codigoCanje = $data['codigo_canje'] ?? 'CANJE-' . strtoupper(bin2hex(random_bytes(4)));

    if ($idUsuario <= 0 || $costoPuntos <= 0) {
        return ["status" => "error", "mensaje" => "ID de usuario o costo de puntos no válidos."];
    }

    // ----------------------------------------------------------------------------------------
    // 2. VERIFICACIÓN DE SALDO
    // ----------------------------------------------------------------------------------------

    /** @section VerificacionSaldo Consulta de puntos actuales y datos de contacto. */
    $stmt = $conn->prepare("SELECT nombre, puntos, gmail FROM usuario WHERE id = ?");
    $stmt->bind_param("i", $idUsuario);
    $stmt->execute();
    
    /** @var mysqli_result $res */
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        return ["status" => "error", "mensaje" => "El usuario no existe."];
    }

    /** @var array $usuario Datos del usuario recuperados para el email y validación. */
    $usuario = $res->fetch_assoc();
    $stmt->close();

    if ($usuario['puntos'] < $costoPuntos) {
        return ["status" => "error", "mensaje" => "Puntos insuficientes. Puntos actuales: {$usuario['puntos']}."];
    }

    // ----------------------------------------------------------------------------------------
    // 3. ACTUALIZACIÓN DE PUNTOS (TRANSACCIÓN)
    // ----------------------------------------------------------------------------------------

    /** @section DeduccionPuntos Resta de puntos en el perfil del usuario. */
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
    
    // ----------------------------------------------------------------------------------------
    // 4. NOTIFICACIÓN VÍA EMAIL
    // ----------------------------------------------------------------------------------------

    /** * @section NotificacionEmail Envío de comprobante mediante PHPMailer.
     * @note Si el envío falla, se notifica éxito en el canje pero error en la comunicación.
     */
    $nombre = $usuario['nombre'];
    $gmail = $usuario['gmail'];
    $puntosRestantes = $usuario['puntos'] - $costoPuntos;

    $asunto  = "Confirmación de Canje: {$nombreRecompensa}";
    $cuerpo  = "<h2>¡Felicidades, $nombre!</h2>
                <p>Has canjeado exitosamente la recompensa <strong>{$nombreRecompensa}</strong>.</p>
                <p>Te hemos descontado <strong>{$costoPuntos} puntos</strong>.</p>
                <h3>Detalles de la Recompensa:</h3>
                <p><strong>Código de Canje:</strong> <span style='font-size: 1.2em; color: #152D9A; font-weight: bold;'>$codigoCanje</span></p>
                <p>Muestra este código para reclamar tu premio.</p>
                <p>Tus puntos restantes son: <strong>{$puntosRestantes}</strong>.</p>
                <p>¡Gracias por participar!</p>";

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'no.reply.aither@gmail.com'; // Credenciales de sistema
        $mail->Password   = 'esdf lkoc qprz rkum';       // Contraseña de aplicación
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