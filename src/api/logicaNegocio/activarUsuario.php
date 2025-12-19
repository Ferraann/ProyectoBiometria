<?php
/**
 * @file activarUsuario.php
 * @brief Lógica de negocio para la activación de cuentas de usuario.
 * @details Define los procesos de validación de tokens de seguridad y el cambio de estado de cuentas.
 * Implementa comprobaciones de integridad de token y control de caducidad temporal.
 * @author Manuel
 * @date 05/12/2025
 */

/**
 * @brief Activa un usuario en el sistema mediante un token de verificación.
 * * @param mysqli $conn Instancia de conexión a la base de datos.
 * @param string $token Cadena alfanumérica única enviada al correo del usuario.
 * * @return array {
 * @var string status 'ok' o 'error'.
 * @var string mensaje Descripción del resultado de la operación.
 * }
 */
function activarUsuario($conn, $token)
{
    // ----------------------------------------------------------------------------------------
    // 1. BUSCAR USUARIO POR TOKEN
    // ----------------------------------------------------------------------------------------
    
    /**
     * @section ValidacionToken
     * Comprueba si el token existe en la base de datos y recupera su fecha de expiración.
     */
    $sql = "SELECT id, token_expira FROM usuario WHERE token = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    
    /** @var mysqli_result $res Resultado de la consulta de búsqueda. */
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        return ["status" => "error", "mensaje" => "Token inválido o ya usado."];
    }

    /** @var array $usr Datos del usuario asociado al token encontrado. */
    $usr = $res->fetch_assoc();

    // ----------------------------------------------------------------------------------------
    // 2. COMPROBAR CADUCIDAD
    // ----------------------------------------------------------------------------------------

    /**
     * @section ControlExpiracion
     * @note Se compara el timestamp de 'token_expira' con el tiempo actual del servidor.
     */
    if (strtotime($usr['token_expira']) < time()) {
        return ["status" => "error", "mensaje" => "El enlace ha expirado. Solicita un nuevo correo de activación."];
    }

    // ----------------------------------------------------------------------------------------
    // 3. ACTIVACIÓN DE CUENTA
    // ----------------------------------------------------------------------------------------

    /**
     * @section ActualizacionEstado
     * Activa la cuenta y anula las credenciales temporales (token) para evitar su reutilización.
     */
    $sql2 = "UPDATE usuario SET activo = 1, token = NULL, token_expira = NULL WHERE id = ?";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param("i", $usr['id']);
    $stmt2->execute();

    if ($stmt2->affected_rows > 0) {
        return ["status" => "ok", "mensaje" => "Cuenta activada correctamente."];
    } else {
        return ["status" => "error", "mensaje" => "No se pudo activar la cuenta."];
    }
}
?>