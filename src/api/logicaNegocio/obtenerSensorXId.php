<?php
/**
 * @file obtenerSensorXId.php
 * @brief Recuperación de metadatos y estado de diagnóstico de un sensor específico.
 * @details Permite consultar la información técnica de un dispositivo de hardware, 
 * incluyendo su identificador físico (MAC) y cualquier problema reportado.
 * @author Manuel
 * @date 11/12/2025
 */

/**
 * @brief Obtiene el registro detallado de un sensor mediante su identificador único.
 * @param mysqli $conn Instancia de conexión activa a la base de datos.
 * @param int $id Identificador único del sensor (Primary Key).
 * @return array|null Devuelve un array asociativo con los datos del sensor o null si no se encuentra o hay error.
 */
function obtenerSensorXId($conn, $id)
{
    // ----------------------------------------------------------------------------------------
    // 1. PREPARACIÓN DE LA CONSULTA
    // ----------------------------------------------------------------------------------------

    /** @section ConsultaSensor 
     * Selección de campos técnicos y de estado del dispositivo.
     */
    /* SQL:
     * - id, mac: Identificadores lógicos y físicos.
     * - modelo, nombre: Información descriptiva para la interfaz.
     * - problema: Campo de diagnóstico que indica fallos conocidos en el sensor.
     */
    $sql = "SELECT id, mac, modelo, nombre, problema FROM sensor WHERE id = ?";

    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        /** @note Se registra el fallo en el log del servidor para depuración interna. */
        error_log("Error al preparar la consulta: " . $conn->error);
        return null;
    }
    
    // ----------------------------------------------------------------------------------------
    // 2. EJECUCIÓN Y GESTIÓN DE RESULTADOS
    // ----------------------------------------------------------------------------------------

    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    /** @var mysqli_result $result Resultado de la ejecución en la base de datos. */
    $result = $stmt->get_result();
    
    if (!$result) {
        error_log("Error al ejecutar la consulta: " . $stmt->error);
        return null;
    }
    
    /** @var array|null $sensor Almacena el registro asociativo del sensor. */
    $sensor = $result->fetch_assoc();
    
    /** @section CierreRecursos 
     * Finalización del statement para liberar memoria en el servidor.
     */
    $stmt->close();
    
    return $sensor;
}
?>