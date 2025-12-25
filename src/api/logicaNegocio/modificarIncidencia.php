<?php
/**
 * @file modificarIncidencia.php
 * @brief Lógica de negocio para la gestión y seguimiento de incidencias.
 * @details Proporciona funcionalidades para el ciclo de vida de una incidencia, permitiendo 
 * la transición entre estados y la asignación de personal técnico para su resolución.
 * @author Manuel
 * @date 30/10/2025
 */

/**
 * @brief Actualiza el flujo de estado de una incidencia específica.
 * @param mysqli $conn Instancia de conexión activa a la base de datos.
 * @param array $datos {
 * @var int $incidencia_id ID de la incidencia a modificar.
 * @var int $estado_id ID del nuevo estado (ej. En Proceso, Resuelta).
 * }
 * @return array {
 * @var string status 'ok' o 'error'.
 * @var string|null mensaje Descripción del fallo en caso de error.
 * }
 */
function actualizarEstadoIncidencia($conn, $datos) {
    // ----------------------------------------------------------------------------------------
    // 1. SANEAMIENTO DE ENTRADA
    // ----------------------------------------------------------------------------------------

    /** @var int $incidenciaId Identificador único de la incidencia. */
    $incidenciaId = intval($datos['incidencia_id'] ?? 0);
    /** @var int $estadoId Referencia al nuevo estado en la tabla maestra. */
    $estadoId     = intval($datos['estado_id'] ?? 0);

    // ----------------------------------------------------------------------------------------
    // 2. ACTUALIZACIÓN DE ESTADO
    // ----------------------------------------------------------------------------------------

    /** @section UpdateEstado Cambio en la fase del ticket. */
    /* SQL:
     * Modifica la columna estado_id vinculada a la tabla 'estado_incidencia'.
     * El cambio afecta exclusivamente al registro identificado por su clave primaria.
     */
    $stmt = $conn->prepare("UPDATE incidencias SET estado_id = ? WHERE id = ?");
    
    /** @note Se ejecuta pasando los parámetros directamente al método execute para mayor brevedad. */
    $ok   = $stmt->execute([$estadoId, $incidenciaId]);

    if ($ok) {
        return ["status" => "ok"];
    } else {
        return [
            "status" => "error", 
            "mensaje" => "No se pudo actualizar el estado de la incidencia: " . $conn->error
        ];
    }
}

/**
 * @brief Asigna o reasigna un técnico responsable a una incidencia.
 * @param mysqli $conn Instancia de conexión activa a la base de datos.
 * @param array $datos {
 * @var int $incidencia_id ID de la incidencia.
 * @var int $tecnico_id ID del técnico asignado.
 * }
 * @return array Resultado de la operación de asignación.
 */
function asignarTecnicoIncidencia($conn, $datos) {
    // ----------------------------------------------------------------------------------------
    // 1. SANEAMIENTO DE ENTRADA
    // ----------------------------------------------------------------------------------------

    /** @var int $incidenciaId */
    $incidenciaId = intval($datos['incidencia_id'] ?? 0);
    /** @var int $tecnicoId ID del usuario con rol técnico. */
    $tecnicoId    = intval($datos['tecnico_id'] ?? 0);

    // ----------------------------------------------------------------------------------------
    // 2. ASIGNACIÓN DE TÉCNICO
    // ----------------------------------------------------------------------------------------

    /** @section UpdateTecnico Vinculación de personal a la incidencia. */
    /* SQL:
     * Establece la relación entre la incidencia y el técnico responsable.
     * Esta acción suele disparar notificaciones en sistemas de gestión de tareas.
     */
    $stmt = $conn->prepare("UPDATE incidencias SET id_tecnico = ? WHERE id = ?");
    $ok   = $stmt->execute([$tecnicoId, $incidenciaId]);

    if ($ok) {
        return ["status" => "ok"];
    } else {
        return [
            "status" => "error", 
            "mensaje" => "No se pudo asignar el técnico a la incidencia: " . $conn->error
        ];
    }
}
?>