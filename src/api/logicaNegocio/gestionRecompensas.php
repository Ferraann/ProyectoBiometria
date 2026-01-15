<?php
/**
 * @file gestionRecompensas.php
 * @brief Módulo para la gestión de catálogo de recompensas y canjes de usuarios.
 * @details Este archivo permite a los usuarios adquirir recompensas mediante puntos,
 * genera tokens únicos de validación y permite a los administradores marcar
 * las recompensas como consumidas físicamente.
 * @author Manuel
 * @date 11/01/2026
 */

require_once __DIR__ . '/mailer.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * @brief Gestiona el proceso completo de adquisición de una recompensa.
 * @details Realiza tres pasos críticos en una transacción atómica:
 * 1. Comprueba si el usuario tiene puntos suficientes (Saldo >= Coste).
 * 2. Deduce los puntos del saldo del usuario.
 * 3. Crea la relación en la tabla 'registro_recompensas' generando un token único.
 * @param mysqli $conn Instancia de conexión activa a la base de datos.
 * @param array $data {
 * @var int $id_user Identificador del usuario.
 * @var int $id_recompensa Identificador de la recompensa deseada.
 * }
 * @return array Resultado de la operación con estado y mensaje descriptivo.
 */
function canjearRecompensa($conn, $data) {
    // ----------------------------------------------------------------------------------------
    // 1. VALIDACIÓN DE ENTRADA
    // ----------------------------------------------------------------------------------------
    if (!isset($data['id_user'], $data['id_recompensa'])) {
        return ["status" => "error", "mensaje" => "Parámetros insuficientes: se requiere id_user e id_recompensa."];
    }

    $id_user = intval($data['id_user']);
    $id_recompensa = intval($data['id_recompensa']);

    // ----------------------------------------------------------------------------------------
    // 2. COMPROBACIÓN DE REQUISITOS (PUNTOS SUFICIENTES)
    // ----------------------------------------------------------------------------------------
    /** @section VerificacionSaldo Obtención de coste y saldo actual. */
    $sqlInfo = "SELECT r.puntos as coste, r.nombre as promo_nombre, u.puntos as saldo_actual, u.gmail, u.nombre as user_nombre 
                FROM recompensas r, usuario u 
                WHERE r.id = ? AND u.id = ?";
    
    $stmtInfo = $conn->prepare($sqlInfo);
    $stmtInfo->bind_param("ii", $id_recompensa, $id_user);
    $stmtInfo->execute();
    $resInfo = $stmtInfo->get_result();

    if ($resInfo->num_rows === 0) {
        return ["status" => "error", "mensaje" => "Error: El usuario o la recompensa especificados no existen."];
    }

    $info = $resInfo->fetch_assoc();

    // Punto A: Comprobar si tiene puntos suficientes
    if ($info['saldo_actual'] < $info['coste']) {
        return [
            "status" => "error", 
            "mensaje" => "Saldo insuficiente. Requieres {$info['coste']} puntos y tienes {$info['saldo_actual']}."
        ];
    }

    // ----------------------------------------------------------------------------------------
    // 3. PROCESO DE CANJE (TRANSACCIÓN ATÓMICA)
    // ----------------------------------------------------------------------------------------
    /** @section TransaccionCanje Inicio de bloque transaccional para asegurar integridad. */
    $conn->begin_transaction();

    try {
        // Punto C: Restar los puntos correspondientes al usuario
        $nuevoSaldo = $info['saldo_actual'] - $info['coste'];
        $updUser = $conn->prepare("UPDATE usuario SET puntos = ? WHERE id = ?");
        $updUser->bind_param("ii", $nuevoSaldo, $id_user);
        
        if (!$updUser->execute()) {
            throw new Exception("No se pudo actualizar el saldo del usuario.");
        }

        // Generación de token único para el canje físico
        $token = "AITH-" . strtoupper(bin2hex(random_bytes(4)));

        // Punto B: Crear la relación (Registro de la compra)
        $insReg = $conn->prepare("INSERT INTO registro_recompensas (id_recompensa, id_user, token) VALUES (?, ?, ?)");
        $insReg->bind_param("iis", $id_recompensa, $id_user, $token);
        
        if (!$insReg->execute()) {
            throw new Exception("Error al crear el registro de la recompensa.");
        }

        // Si todo ha ido bien, confirmamos los cambios en la DB
        $conn->commit();

        // ----------------------------------------------------------------------------------------
        // 4. NOTIFICACIÓN AL USUARIO
        // ----------------------------------------------------------------------------------------
        /** @note El envío de correo ocurre tras el commit para evitar enviar códigos si la DB falla. */
        enviarCorreoRecompensa($info['gmail'], $info['user_nombre'], $info['promo_nombre'], $token);

        return [
            "status" => "ok", 
            "mensaje" => "Canje realizado con éxito. Se han deducido {$info['coste']} puntos.",
            "token" => $token
        ];

    } catch (Exception $e) {
        // En caso de cualquier error, se revierten todos los cambios (ni se restan puntos ni se crea registro)
        $conn->rollback();
        return ["status" => "error", "mensaje" => "Fallo en el proceso de canje: " . $e->getMessage()];
    }
}

/**
 * @brief Envía el correo electrónico con el código de canje.
 * @internal Solo para uso dentro del módulo.
 */
function enviarCorreoRecompensa($correoDestino, $nombreUser, $nombreRecompensa, $token) {
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
        $mail->addAddress($correoDestino);
        $mail->isHTML(true);
        $mail->Subject = "Tu codigo de canje: $nombreRecompensa";
        $mail->Body    = "<h2>¡Enhorabuena, $nombreUser!</h2>
                          <p>Has canjeado tus puntos por: <strong>$nombreRecompensa</strong>.</p>
                          <p>Presenta el siguiente código en el punto de canje físico:</p>
                          <h1 style='background:#f4f4f4; padding:20px; text-align:center; border:2px dashed #333;'>$token</h1>
                          <p>Ubicación de canje: <strong>UPV</strong></p>";
        $mail->send();
    } catch (Exception $e) {
        error_log("Error enviando correo de recompensa: " . $mail->ErrorInfo);
    }
}

/**
 * @brief Marca una recompensa como canjeada físicamente.
 * @details Cambia el estado 'canjeada' a 1 y registra la localización del canje.
 * @param mysqli $conn Conexión a la base de datos.
 * @param array $data {
 * @var string $token Código único de la recompensa.
 * @var string $localizacion (Opcional) Lugar del canje, por defecto 'UPV'.
 * }
 * @return array Respuesta de estado.
 */
function marcarRecompensaComoUsada($conn, $data) {
    if (!isset($data['token'])) {
        return ["status" => "error", "mensaje" => "Token no proporcionado."];
    }

    $token = $data['token'];
    $localizacion = $data['localizacion'] ?? 'UPV';

    $stmt = $conn->prepare("UPDATE registro_recompensas 
                            SET canjeada = 1, fecha_canjeado = NOW(), localizacion = ? 
                            WHERE token = ? AND canjeada = 0");
    $stmt->bind_param("ss", $localizacion, $token);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        return ["status" => "ok", "mensaje" => "Recompensa canjeada con éxito en $localizacion."];
    } else {
        return ["status" => "error", "mensaje" => "Token inválido o ya canjeado."];
    }
}

/**
 * @brief Obtiene el catálogo de recompensas que están vigentes.
 */
function obtenerRecompensasDisponibles($conn) {
    $sql = "SELECT * FROM recompensas 
            WHERE ilimitada = 1 
            OR (NOW() BETWEEN fecha_inicio AND fecha_finalizacion)";
    $res = $conn->query($sql);
    $lista = [];
    while ($row = $res->fetch_assoc()) {
        $lista[] = $row;
    }
    return $lista;
}