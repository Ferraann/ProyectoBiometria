<?php
/**
 * @file obtenerUsuarioXId.php
 * @brief Recuperación de perfiles de usuario mediante identificador único.
 * @details Proporciona acceso a la información completa de un usuario registrado en el sistema. 
 * Esta función es esencial para cargar perfiles, verificar permisos y gestionar la administración de cuentas.
 * @author Manuel
 * @date 5/12/2025
 */

/**
 * @brief Obtiene el registro íntegro de un usuario por su clave primaria.
 * @param mysqli $conn Instancia de conexión activa a la base de datos.
 * @param int $id Identificador numérico único del usuario.
 * @return array|null Devuelve un array asociativo con los campos del usuario o null si no se encuentra el registro o existe un error de red.
 */
function obtenerUsuarioXId($conn, $id)
{
    // ----------------------------------------------------------------------------------------
    // 1. PREPARACIÓN DE LA SENTENCIA SEGURA
    // ----------------------------------------------------------------------------------------

    /** @section ConsultaUsuario 
     * Ejecuta una selección sobre la tabla 'usuario' filtrada por ID.
     */
    /* SQL:
     * Selecciona todas las columnas (*) del usuario solicitado.
     * Al usar consultas preparadas, se previene cualquier intento de inyección SQL.
     */
    $sql = "SELECT * FROM usuario WHERE id = ?";

    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        /** @note En caso de fallo en la preparación (ej. tabla bloqueada o inexistente), retorna null. */
        return null;
    }
    
    // ----------------------------------------------------------------------------------------
    // 2. VINCULACIÓN Y EXTRACCIÓN
    // ----------------------------------------------------------------------------------------

    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    /** @var mysqli_result $result Recurso que contiene el set de resultados de la DB. */
    $result = $stmt->get_result();
    
    if (!$result) {
        return null;
    }
    
    /** @var array|null $usuario Fila del usuario mapeada como array asociativo. */
    $usuario = $result->fetch_assoc();

    /** @section CierreConexion 
     * Liberación del recurso statement para optimizar la memoria del servidor.
     */
    $stmt->close();
    
    return $usuario;
}
?>