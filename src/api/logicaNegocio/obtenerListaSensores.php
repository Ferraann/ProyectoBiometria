<?php
/**
 * @file obtenerListaSensores.php
 * @brief Recuperación de inventario de dispositivos activos por usuario.
 * @details Gestiona la consulta de sensores vinculados, filtrando únicamente aquellos que
 * mantienen una relación vigente ('actual') con el identificador de usuario proporcionado.
 * @author Manuel
 * @date 11/12/2025
 */

/**
 * @brief Obtiene el listado de sensores vinculados actualmente a un usuario.
 * @param mysqli $conn Instancia de conexión activa a la base de datos.
 * @param int $usuario_id Identificador único del usuario.
 * @return array {
 * @var string status 'ok' o 'error'.
 * @var array listaSensores Colección de objetos con 'id' y 'mac' del sensor.
 * @var string|null mensaje Información adicional en caso de ausencia de datos.
 * }
 */
function obtenerListaSensores($conn, $usuario_id){

    // ----------------------------------------------------------------------------------------
    // 1. VALIDACIÓN DE ENTRADA
    // ----------------------------------------------------------------------------------------
    if (empty($usuario_id)) {
        return ["status" => "error", "mensaje" => "Falta usuario_id obligatorio."];
    }

    // ----------------------------------------------------------------------------------------
    // 2. CONSULTA RELACIONAL (JOIN)
    // ----------------------------------------------------------------------------------------

    /** @section ConsultaSensoresActivos 
     * Extracción de datos maestros del sensor mediante su tabla intermedia de relación.
     */
    /* SQL:
     * - INNER JOIN: Cruza la tabla 'sensor' con 'usuario_sensor' para verificar propiedad.
     * - us.usuario_id = ?: Filtra por el dueño actual.
     * - us.actual = 1: Crucial para ignorar sensores que el usuario tuvo en el pasado 
     * pero que ya no gestiona (historial inactivo).
     */
    $sql = "SELECT s.id, s.mac 
            FROM sensor s 
            INNER JOIN usuario_sensor us 
                ON s.id = us.sensor_id
            WHERE us.usuario_id = ? 
              AND us.actual = 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    
    /** @var mysqli_result $result Resultado de la consulta relacional. */
    $result = $stmt->get_result();

    // ----------------------------------------------------------------------------------------
    // 3. ESTRUCTURACIÓN DE RESPUESTA
    // ----------------------------------------------------------------------------------------

    $sensores = [];

    /** @section MapeoResultados Construcción del array asociativo de salida. */
    while ($row = $result->fetch_assoc()) {
        $sensores[] = [
            "id"  => (int)$row["id"],
            "mac" => $row["mac"]
        ];
    }

    $stmt->close();

    if (empty($sensores)) {
        /** @note Se retorna un array vacío en 'listaSensores' para evitar errores de iteración en el cliente. */
        return [
            "status" => "ok", 
            "listaSensores" => [], 
            "mensaje" => "El usuario no tiene sensores asociados en este momento."
        ];
    }

    return [
        "status" => "ok", 
        "listaSensores" => $sensores
    ];
}
?>