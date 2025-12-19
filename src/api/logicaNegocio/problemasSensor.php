<?php
/**
 * @file problemas_sensor.php
 * @brief Gestión del estado operativo de los dispositivos (sensores).
 * @details Proporciona las herramientas necesarias para marcar sensores con fallos técnicos
 * o rehabilitarlos tras su reparación. Estas operaciones afectan únicamente al estado de 
 * diagnóstico en la tabla 'sensor', manteniendo intactas las relaciones de propiedad con los usuarios.
 * @author Manuel
 * @date 11/12/2025
 */

/**
 * @brief Marca un sensor como defectuoso o con problemas técnicos.
 * @param mysqli $conn Instancia de conexión activa a la base de datos.
 * @param array $data {
 * @var int $sensor_id Identificador del dispositivo a marcar.
 * }
 * @return array {
 * @var string status 'ok' o 'error'.
 * @var string mensaje Confirmación de la acción o descripción del fallo.
 * }
 */
function sensorConProblemas($conn, $data)
{
    // ----------------------------------------------------------------------------------------
    // 1. VALIDACIÓN DE IDENTIFICADOR
    // ----------------------------------------------------------------------------------------
    if (!isset($data['sensor_id'])) {
        return ["status" => "error", "mensaje" => "Falta el parámetro obligatorio: sensor_id."];
    }

    /** @var int $sensor_id */
    $sensor_id = (int)$data['sensor_id'];

    // ----------------------------------------------------------------------------------------
    // 2. ACTUALIZACIÓN DE ESTADO DE DIAGNÓSTICO
    // ----------------------------------------------------------------------------------------

    /** @section RegistroFallo 
     * Establece el flag de problema a 1 (Verdadero).
     */
    /* SQL:
     * Actualiza el campo 'problema' para indicar que el hardware requiere revisión.
     * Esta marca suele ser utilizada por el front-end para mostrar alertas visuales.
     */
    $sqlProblema = "UPDATE sensor SET problema = 1 WHERE id = ?";
    $stmtProb = $conn->prepare($sqlProblema);
    
    if (!$stmtProb) {
        return ["status" => "error", "mensaje" => "Error al preparar la consulta: " . $conn->error];
    }
    
    $stmtProb->bind_param("i", $sensor_id);

    if ($stmtProb->execute()) {
        $stmtProb->close();
        return [
            "status" => "ok",
            "mensaje" => "Sensor marcado con problema correctamente."
        ];
    } else {
        $error = $stmtProb->error;
        $stmtProb->close();
        return ["status" => "error", "mensaje" => "Error al actualizar el estado del sensor: " . $error];
    }
}

/**
 * @brief Rehabilita un sensor marcándolo como operativo (sin problemas).
 * @param mysqli $conn Instancia de conexión activa a la base de datos.
 * @param array $data {
 * @var int $sensor_id Identificador del dispositivo reparado.
 * }
 * @return array Resultado de la operación de reactivación.
 */
function sensorSinProblemas($conn, $data)
{
    // ----------------------------------------------------------------------------------------
    // 1. VALIDACIÓN DE IDENTIFICADOR
    // ----------------------------------------------------------------------------------------
    if (!isset($data['sensor_id'])) {
        return ["status" => "error", "mensaje" => "Falta el parámetro obligatorio: sensor_id."];
    }

    /** @var int $sensor_id */
    $sensor_id = (int)$data['sensor_id'];

    // ----------------------------------------------------------------------------------------
    // 2. REACTIVACIÓN DE HARDWARE
    // ----------------------------------------------------------------------------------------

    /** @section ResolucionFallo 
     * Restablece el flag de problema a 0 (Falso).
     */
    /* SQL:
     * El sensor vuelve a marcarse como funcional. Las mediciones enviadas por este
     * dispositivo volverán a considerarse fiables en el sistema de monitorización.
     */
    $sql = "UPDATE sensor SET problema = 0 WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return ["status" => "error", "mensaje" => "Error en la preparación de la consulta: " . $conn->error];
    }

    $stmt->bind_param("i", $sensor_id);

    if ($stmt->execute()) {
        $stmt->close(); 
        return ["status" => "ok", "mensaje" => "Sensor reactivado y marcado como operativo."];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ["status" => "error", "mensaje" => "Error al reactivar el sensor en la base de datos: " . $error];
    }
}
?>