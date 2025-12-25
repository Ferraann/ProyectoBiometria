<?php
/**
 * @file loginUsuario.php
 * @brief Sistema de autenticación y control de acceso.
 * @details Valida las credenciales del usuario, comprueba el estado de activación de la cuenta
 * y gestiona la persistencia de la identidad mediante variables de sesión PHP.
 * @author Manuel
 * @date 05/12/2025
 */

/**
 * @brief Autentica a un usuario y establece su sesión.
 * @param mysqli $conn Instancia de conexión activa a la base de datos.
 * @param string $gmail Correo electrónico del usuario (identificador).
 * @param string $password Contraseña en texto plano proporcionada en el formulario.
 * @return array {
 * @var string status 'ok' o 'error'.
 * @var array|null usuario Datos del usuario autenticado (solo en éxito).
 * @var string|null mensaje Descripción del error (solo en fallo).
 * }
 */
function loginUsuario($conn, $gmail, $password)
{
    // ----------------------------------------------------------------------------------------
    // 1. RECUPERACIÓN DE CREDENCIALES
    // ----------------------------------------------------------------------------------------

    /** @section BusquedaUsuario
     * Recupera el hash de la contraseña y el estado del usuario mediante el correo.
     */
    /* SQL:
     * Busca los campos necesarios para la sesión y validación de seguridad.
     * Se usa un filtro por 'gmail', que debe ser único en la tabla 'usuario'.
     */
    $stmt = $conn->prepare(
        "SELECT id, nombre, apellidos, gmail, password, activo
         FROM usuario
         WHERE gmail = ?"
    );
    
    if (!$stmt) {
        return ["status" => "error", "mensaje" => "Error interno al preparar la consulta de seguridad."];
    }
    
    $stmt->bind_param("s", $gmail);
    $stmt->execute();
    /** @var mysqli_result $res Resultado de la búsqueda por correo. */
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        return ["status" => "error", "mensaje" => "Las credenciales no coinciden con nuestros registros."];
    }
    
    /** @var array $user Datos del registro recuperado. */
    $user = $res->fetch_assoc();
    $stmt->close();

    // ----------------------------------------------------------------------------------------
    // 2. VALIDACIÓN DE SEGURIDAD Y ESTADO
    // ----------------------------------------------------------------------------------------

    /** @section VerificacionHash
     * Compara la contraseña ingresada contra el hash almacenado mediante BCrypt.
     */
    if (!password_verify($password, $user['password'])) {
        return ["status" => "error", "mensaje" => "Contraseña incorrecta."];
    }

    /** @section VerificacionEstado
     * Impide el acceso si el usuario no ha completado el proceso de activación por correo.
     */
    if (!$user['activo']) {
        return ["status" => "error", "mensaje" => "La cuenta está pendiente de activación."];
    }

    // ----------------------------------------------------------------------------------------
    // 3. ESTABLECIMIENTO DE SESIÓN
    // ----------------------------------------------------------------------------------------

    /** @section PersistenciaSesion
     * Almacena información no sensible en el servidor para identificar al usuario en futuras peticiones.
     * @note Por seguridad, la contraseña (incluso el hash) se elimina del array de retorno.
     */
    $_SESSION['usuario_id'] = $user['id'];
    $_SESSION['usuario_nombre'] = $user['nombre'];
    $_SESSION['usuario_apellidos'] = $user['apellidos'];
    $_SESSION['usuario_correo'] = $user['gmail'];

    // Limpieza de datos sensibles antes de retornar al controlador
    unset($user['password']);
    
    return ["status" => "ok", "usuario" => $user];
}
?>