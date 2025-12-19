<?php
/**
 * @file roles.php
 * @brief Sistema de control de acceso basado en roles (RBAC).
 * @details Proporciona una capa de abstracción para verificar privilegios y gestionar 
 * la promoción o degradación de usuarios en los niveles de Técnico y Administrador.
 * @author Manuel
 * @date 11/12/2025
 */

// ----------------------------------------------------------------------------------------
// SECCIÓN: VALIDACIÓN DE PRIVILEGIOS
// ----------------------------------------------------------------------------------------

/**
 * @brief Comprueba si un usuario tiene privilegios de técnico.
 * @param mysqli $conn Instancia de conexión activa a la base de datos.
 * @param int $usuarioId ID del usuario a consultar.
 * @return bool Verdadero si el usuario existe en la tabla de técnicos.
 */
function esTecnico($conn, int $usuarioId): bool
{
    /** @section ValidarTecnico Consulta de existencia en tabla técnica. */
    $stmt = $conn->prepare("SELECT 1 FROM tecnicos WHERE usuario_id = ? LIMIT 1");
    $stmt->bind_param("i", $usuarioId);
    $stmt->execute();
    /** @note Se utiliza fetch_column() para obtener directamente el valor booleano de la existencia. */
    return (bool) $stmt->get_result()->fetch_column();
}

/**
 * @brief Comprueba si un usuario tiene privilegios de administrador.
 * @param mysqli $conn Instancia de conexión activa a la base de datos.
 * @param int $usuarioId ID del usuario a consultar.
 * @return bool Verdadero si el usuario existe en la tabla de administradores.
 */
function esAdministrador($conn, int $usuarioId): bool
{
    /** @section ValidarAdmin Consulta de existencia en tabla administrativa. */
    $stmt = $conn->prepare("SELECT 1 FROM administradores WHERE usuario_id = ? LIMIT 1");
    $stmt->bind_param("i", $usuarioId);
    $stmt->execute();
    return (bool) $stmt->get_result()->fetch_column();
}

// ----------------------------------------------------------------------------------------
// SECCIÓN: GESTIÓN DEL ROL TÉCNICO
// ----------------------------------------------------------------------------------------

/**
 * @brief Promociona a un usuario al rol de Técnico.
 * @param mysqli $conn Instancia de conexión.
 * @param int $usuarioId ID del usuario.
 * @return array {
 * @var string status 'ok' o 'error'.
 * @var string mensaje Descripción del resultado.
 * }
 */
function asignarTecnico($conn, int $usuarioId): array
{
    /** @note Se realiza una comprobación previa para evitar duplicados. */
    if (esTecnico($conn, $usuarioId)) {
        return ["status" => "ok", "mensaje" => "El usuario ya posee el rol de técnico."];
    }
    
    $stmt = $conn->prepare("INSERT INTO tecnicos (usuario_id) VALUES (?)");
    $stmt->bind_param("i", $usuarioId);
    return $stmt->execute()
        ? ["status" => "ok", "mensaje" => "Rol técnico asignado correctamente."]
        : ["status" => "error", "mensaje" => "Error al asignar rol: " . $conn->error];
}

/**
 * @brief Revoca el rol de Técnico a un usuario.
 * @param mysqli $conn Instancia de conexión.
 * @param int $usuarioId ID del usuario.
 * @return array Resultado de la operación de borrado.
 */
function quitarTecnico($conn, int $usuarioId): array
{
    $stmt = $conn->prepare("DELETE FROM tecnicos WHERE usuario_id = ?");
    $stmt->bind_param("i", $usuarioId);
    return $stmt->execute()
        ? ["status" => "ok", "mensaje" => "Privilegios técnicos revocados."]
        : ["status" => "error", "mensaje" => "Error al eliminar rol: " . $conn->error];
}

// ----------------------------------------------------------------------------------------
// SECCIÓN: GESTIÓN DEL ROL ADMINISTRADOR
// ----------------------------------------------------------------------------------------

/**
 * @brief Promociona a un usuario al nivel de Administrador.
 * @param mysqli $conn Instancia de conexión.
 * @param int $usuarioId ID del usuario.
 * @return array Resultado de la inserción.
 */
function asignarAdministrador($conn, int $usuarioId): array
{
    if (esAdministrador($conn, $usuarioId)) {
        return ["status" => "ok", "mensaje" => "El usuario ya posee el rol de administrador."];
    }
    
    $stmt = $conn->prepare("INSERT INTO administradores (usuario_id) VALUES (?)");
    $stmt->bind_param("i", $usuarioId);
    return $stmt->execute()
        ? ["status" => "ok", "mensaje" => "Rol administrador asignado con éxito."]
        : ["status" => "error", "mensaje" => "Error al asignar privilegios: " . $conn->error];
}

/**
 * @brief Revoca los privilegios de Administrador a un usuario.
 * @param mysqli $conn Instancia de conexión.
 * @param int $usuarioId ID del usuario.
 * @return array Resultado de la operación.
 */
function quitarAdministrador($conn, int $usuarioId): array
{
    $stmt = $conn->prepare("DELETE FROM administradores WHERE usuario_id = ?");
    $stmt->bind_param("i", $usuarioId);
    return $stmt->execute()
        ? ["status" => "ok", "mensaje" => "Privilegios de administrador revocados."]
        : ["status" => "error", "mensaje" => "Error al eliminar privilegios: " . $conn->error];
}
?>