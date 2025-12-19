<?php
/**
 * @file registrarUsuario.php
 * @brief Gestión del proceso de alta de nuevos usuarios y verificación de identidad.
 * @details Este módulo gestiona el ciclo completo de registro: validación de datos, 
 * hashing de seguridad (BCrypt), control de duplicados, generación de tokens 
 * temporales y envío de correos electrónicos de activación mediante PHPMailer.
 * @author Manuel
 * @date 05/12/2025
 */

require_once __DIR__ . '/mailer.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * @brief Registra un nuevo usuario en estado inactivo y envía un enlace de activación.
 * @param mysqli $conn Instancia de conexión activa a la base de datos.
 * @param array $data {
 * @var string $nombre Nombre de pila del usuario.
 * @var string $apellidos Apellidos del usuario.
 * @var string $gmail Correo electrónico (identificador único).
 * @var string $password Contraseña en texto plano.
 * }
 * @return array {
 * @var string status 'ok' o 'error'.
 * @var string mensaje Descripción del resultado o motivo del fallo.
 * }
 */
function registrarUsuario($conn, $data)
{
    // ----------------------------------------------------------------------------------------
    // 1. SANEADO Y VALIDACIÓN INICIAL
    // ----------------------------------------------------------------------------------------

    /** @section ValidacionEntrada Comprobación de campos obligatorios y formato. */
    if (!isset($data['nombre'], $data['apellidos'], $data['gmail'], $data['password'])) {
        return ["status" => "error", "mensaje" => "Faltan datos obligatorios para el registro."];
    }

    $nombre    = trim($data['nombre']);
    $apellidos = trim($data['apellidos']);
    $gmail     = trim($data['gmail']);
    $password  = trim($data['password']);
    
    if (!filter_var($gmail, FILTER_VALIDATE_EMAIL)) {
        return ["status" => "error", "mensaje" => "El formato del correo electrónico no es válido."];
    }
    
    /** @var string $hash Contraseña procesada mediante el algoritmo de hash por defecto de PHP (BCrypt). */
    $hash = password_hash($password, PASSWORD_DEFAULT);

    // ----------------------------------------------------------------------------------------
    // 2. CONTROL DE DUPLICADOS Y LIMPIEZA
    // ----------------------------------------------------------------------------------------

    /** @section ControlExistencia 
     * Verifica si el correo ya existe y gestiona cuentas huérfanas (inactivas).
     */
    $stmt = $conn->prepare("SELECT id, activo FROM usuario WHERE gmail = ?");
    $stmt->bind_param("s", $gmail);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows) {
        $usr = $res->fetch_assoc();

        if ($usr['activo'] == 1) {
            return ["status" => "error", "mensaje" => "El correo ya está registrado y activado."];
        }

        /* @note Si el usuario existe pero no está activo, se elimina para permitir un nuevo intento limpio. */
        $del = $conn->prepare("DELETE FROM usuario WHERE id = ?");
        $del->bind_param("i", $usr['id']);
        $del->execute();
    }

    // ----------------------------------------------------------------------------------------
    // 3. SEGURIDAD DE ACTIVACIÓN
    // ----------------------------------------------------------------------------------------

    /** @section GeneracionToken 
     * Crea un identificador criptográficamente seguro para el enlace de activación.
     */
    $token = bin2hex(random_bytes(32)); 
    /** @var string $token_expira Timestamp de caducidad fijado en 15 minutos. */
    $token_expira = date("Y-m-d H:i:s", time() + 900); 

    // ----------------------------------------------------------------------------------------
    // 4. PERSISTENCIA
    // ----------------------------------------------------------------------------------------

    /** @section InsercionUsuario Registro en la tabla 'usuario' con estado 'activo=0'. */
    $ins = $conn->prepare("INSERT INTO usuario 
        (nombre, apellidos, gmail, password, activo, token, token_expira)
        VALUES (?, ?, ?, ?, 0, ?, ?)");
    $ins->bind_param("ssssss", $nombre, $apellidos, $gmail, $hash, $token, $token_expira);

    if (!$ins->execute()) {
        return ["status" => "error", "mensaje" => "Error técnico en el registro: " . $conn->error];
    }

    // ----------------------------------------------------------------------------------------
    // 5. NOTIFICACIÓN SMTP (PHPMailer)
    // ----------------------------------------------------------------------------------------

    /** @section EnvioCorreo Integración con servidor de correo externo. */
    $enlace = "https://fsanpra.upv.edu.es/src/html/activacion.html?token=" . urlencode($token);

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'no.reply.aither@gmail.com';
        $mail->Password   = 'esdf lkoc qprz rkum'; // @warning Credencial sensible
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('noreply@aither.com', 'AITHER');
        $mail->addAddress($gmail);
        $mail->isHTML(true);
        $mail->Subject = "Activa tu cuenta en AITHER";
        $mail->Body    = "<h2>¡Hola $nombre!</h2>
                         <p>Gracias por registrarte. Pulsa el botón para activar tu cuenta:</p>
                         <p><a href='$enlace' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:4px;'>Activar cuenta</a></p>
                         <p>Si el botón no funciona, copia y pega esta dirección:<br>$enlace</p>";

        $mail->send();

    } catch (Exception $e) {
        /** @note Si el correo falla, se informa al usuario que el registro se hizo, pero requiere soporte técnico. */
        error_log('PHPMailer error: ' . $mail->ErrorInfo);
        return ["status" => "ok", "mensaje" => "Usuario registrado, pero el correo de activación falló. Contacta con soporte."];
    }

    return ["status" => "ok", "mensaje" => "Registro completado. Por favor, revisa tu correo electrónico para activar la cuenta."];
}
?>