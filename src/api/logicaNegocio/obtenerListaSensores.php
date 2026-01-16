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
 * @brief Obtiene el listado de sensores vinculados actualmente a un usuario.
 */
function obtenerListaSensores($conn, $usuario_id){
    try {
        if (empty($usuario_id)) {
            return ["status" => "error", "mensaje" => "Falta usuario_id obligatorio."];
        }

        $sql = "SELECT s.id, s.mac 
                FROM sensor s 
                INNER JOIN usuario_sensor us ON s.id = us.sensor_id
                WHERE us.usuario_id = ? AND us.actual = 1";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error en prepare: " . $conn->error);
        }

        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $sensores = [];
        while ($row = $result->fetch_assoc()) {
            $sensores[] = ["id" => (int)$row["id"], "mac" => $row["mac"]];
        }
        $stmt->close();

        return ["status" => "ok", "listaSensores" => $sensores];

    } catch (Exception $e) {
        error_log("CRÍTICO (obtenerListaSensores): " . $e->getMessage());
        return ["status" => "error", "mensaje" => "Error interno en el servidor."];
    }
}

/**
 * @brief Obtiene el inventario global de sensores para el Panel Técnico.
 */
function getTodosLosSensoresDetallados($conn) {
    try {
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

        // 1. DEPURE: Si la consulta falla, registramos el error exacto de SQL
        if (!$result) {
            error_log("SQL Error en getTodosLosSensoresDetallados: " . $conn->error);
            return ["status" => "error", "mensaje" => "Error en la base de datos."];
        }

        $sensores = [];
        while ($row = $result->fetch_assoc()) {
            $sensores[] = [
                "sensor_id"      => (int)$row["sensor_id"],
                "mac"            => $row["mac"],
                "nombre_sensor"  => $row["nombre_sensor"],
                "modelo"         => $row["modelo"],
                "estado"         => (int)$row["estado"],
                "nombre_usuario" => $row["nombre_usuario"]
            ];
        }

        // 2. DEPURE: Registrar cuántos sensores se han encontrado
        error_log("getTodosLosSensoresDetallados: Cargados " . count($sensores) . " sensores.");

        return ["status" => "ok", "listaSensores" => $sensores];

    } catch (Exception $e) {
        error_log("CRÍTICO (getTodosLosSensoresDetallados): " . $e->getMessage());
        return ["status" => "error", "mensaje" => "Excepción en el servidor."];
    }
}
?>