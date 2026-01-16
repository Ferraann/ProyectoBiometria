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
        // Hemos simplificado los JOINs para que, si falla la relación con el usuario, 
        // el sensor aparezca de todos modos (gracias al LEFT JOIN).
        $sql = "SELECT 
                    s.id AS sensor_id, 
                    s.mac, 
                    s.nombre AS nombre_sensor, 
                    s.modelo, 
                    s.estado, 
                    (SELECT u.nombre 
                     FROM usuario u 
                     JOIN usuario_sensor us ON u.id = us.usuario_id 
                     WHERE us.sensor_id = s.id AND us.actual = 1 
                     LIMIT 1) AS nombre_usuario
                FROM sensor s
                ORDER BY s.id DESC";

        $result = $conn->query($sql);

        if (!$result) {
            error_log("Error SQL Directo: " . $conn->error);
            return ["status" => "error", "mensaje" => $conn->error];
        }

        $sensores = [];
        while ($row = $result->fetch_assoc()) {
            $sensores[] = [
                "sensor_id"      => (int)$row["sensor_id"],
                "mac"            => $row["mac"] ?? '00:00:00:00',
                "nombre_sensor"  => $row["nombre_sensor"] ?? 'Sin nombre',
                "modelo"         => $row["modelo"] ?? 'N/A',
                "estado"         => (int)$row["estado"],
                "nombre_usuario" => $row["nombre_usuario"] // Será NULL si no tiene dueño
            ];
        }

        return ["status" => "ok", "listaSensores" => $sensores];

    } catch (Exception $e) {
        error_log("Excepción en sensores: " . $e->getMessage());
        return ["status" => "error", "mensaje" => $e->getMessage()];
    }
}
?>