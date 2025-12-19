<?php
/**
 * @file cerrarIncidencia.php
 * @brief Lógica de negocio para la finalización y cierre de incidencias.
 * @details Actualiza el estado de una incidencia específica a 'Cerrada' y registra 
 * automáticamente la marca de tiempo de finalización.
 * @author Manuel
 * @date 05/12/2025
 */

/**
 * @brief Cambia el estado de una incidencia a 'Cerrada'.
 * @param mysqli $conn Instancia de conexión a la base de datos.
 * @param array $data Debe contener la clave 'incidencia_id'.
 * @return array {
 * @var string status 'ok' o 'error'.
 * @var string mensaje Descripción del resultado.
 * }
 */
function cerrarIncidencia($conn, $data)
{
    // ----------------------------------------------------------------------------------------
    // 1. VALIDACIÓN DE ENTRADA
    // ----------------------------------------------------------------------------------------

    /** @section ValidacionParametros Verificación de la presencia del ID de incidencia. */
    if (!isset($data['incidencia_id'])) {
        return ["status" => "error", "mensaje" => "Falta el parámetro incidencia_id."];
    }

    /** @var int $incidenciaId Identificador de la incidencia a cerrar. */
    $incidenciaId = (int)$data['incidencia_id'];

    // ----------------------------------------------------------------------------------------
    // 2. OBTENCIÓN DEL ESTADO OBJETIVO
    // ----------------------------------------------------------------------------------------

    /** * @section BusquedaEstado 
     * Recupera el ID dinámico para el estado 'Cerrada' desde la tabla maestra.
     * @note Se establece el valor '4' como fallback predeterminado si no se encuentra en la DB.
     */
    $sqlEstado = "SELECT id FROM estado_incidencia WHERE nombre = 'Cerrada' LIMIT 1";
    $resEstado = $conn->query($sqlEstado);
    $estadoRow = $resEstado->fetch_assoc();
    
    /** @var int $estadoId ID correspondiente al estado de cierre. */
    $estadoId = $estadoRow ? $estadoRow['id'] : 4;

    // ----------------------------------------------------------------------------------------
    // 3. ACTUALIZACIÓN DE REGISTRO
    // ----------------------------------------------------------------------------------------

    /** * @section UpdateIncidencia 
     * Ejecuta la actualización del estado y asigna la fecha actual mediante NOW().
     */
    $sql = "UPDATE incidencias SET estado_id = ?, fecha_cierre = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $estadoId, $incidenciaId);

    if ($stmt->execute()) {
        return ["status" => "ok", "mensaje" => "Incidencia cerrada correctamente."];
    } else {
        return ["status" => "error", "mensaje" => "Error al cerrar incidencia: " . $conn->error];
    }
}
?>