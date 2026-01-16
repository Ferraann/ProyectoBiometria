<?php
/**
 * @file gestionSensores.php
 * @brief Módulo de gestión y consulta de inventario de sensores.
 * @details Contiene las funciones necesarias para recuperar sensores por usuario 
 * o el inventario completo para el panel de administración técnica.
 * @author Manuel
 * @date 16/01/2026
 */

/**
 * @brief Obtiene el listado de sensores vinculados actualmente a un usuario específico.
 * @param mysqli $conn Instancia de conexión activa a la base de datos.
 * @param int $usuario_id Identificador único del usuario.
 * @return array {
 * @var string status 'ok' o 'error'.
 * @var array listaSensores Colección de objetos con 'id' y 'mac' del sensor.
 * }
 */
function obtenerListaSensores($conn, $usuario_id){
    // 1. VALIDACIÓN
    if (empty($usuario_id)) {
        return ["status" => "error", "mensaje" => "Falta usuario_id obligatorio."];
    }

    // 2. CONSULTA (INNER JOIN para sensores de un usuario)
    $sql = "SELECT s.id, s.mac 
            FROM sensor s 
            INNER JOIN usuario_sensor us ON s.id = us.sensor_id
            WHERE us.usuario_id = ? AND us.actual = 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // 3. ESTRUCTURACIÓN
    $sensores = [];
    while ($row = $result->fetch_assoc()) {
        $sensores[] = ["id" => (int)$row["id"], "mac" => $row["mac"]];
    }
    $stmt->close();

    return ["status" => "ok", "listaSensores" => $sensores];
}

/**
 * @brief Obtiene el inventario global de sensores para el Panel Técnico.
 * @details Recupera todos los sensores del sistema, su estado de hardware y 
 * la información de asignación actual (dueño) mediante LEFT JOIN.
 * @param mysqli $conn Instancia de conexión activa a la base de datos.
 * @return array {
 * @var string status 'ok' o 'error'.
 * @var array listaSensores Detalle completo: id, mac, nombre, modelo, estado y usuario.
 * }
 */
function getTodosLosSensoresDetallados($conn) {
    // 1. CONSULTA (LEFT JOIN para inventario global)
    /** @section ConsultaInventarioMaestro
     * Se obtienen todos los dispositivos, estén o no asignados a un usuario.
     */
    $sql = "SELECT 
                s.id AS sensor_id, 
                s.mac, 
                COALESCE(s.nombre, 'Sin nombre') AS nombre_sensor, 
                COALESCE(s.modelo, 'N/A') AS modelo, 
                s.estado, 
                u.nombre AS nombre_usuario 
            FROM sensor s
            LEFT JOIN usuario_sensor us ON s.id = us.sensor_id AND us.actual = 1
            LEFT JOIN usuario u ON us.usuario_id = u.id
            ORDER BY s.id DESC";

    $result = $conn->query($sql);

    // 2. VALIDACIÓN DE RESULTADO
    if (!$result) {
        return ["status" => "error", "mensaje" => "Error al acceder al inventario."];
    }

    // 3. ESTRUCTURACIÓN
    $sensores = [];
    while ($row = $result->fetch_assoc()) {
        $sensores[] = [
            "sensor_id"     => (int)$row["sensor_id"],
            "mac"           => $row["mac"],
            "nombre_sensor" => $row["nombre_sensor"],
            "modelo"        => $row["modelo"],
            "estado"        => (int)$row["estado"], // 1: Activo, 0: Inactivo
            "nombre_usuario" => $row["nombre_usuario"] // NULL si está disponible
        ];
    }

    return ["status" => "ok", "listaSensores" => $sensores];
}
?>