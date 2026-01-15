<?php
/**
 * @file obtenerEstadisticas.php
 * @brief Funciones para calcular métricas y estadísticas de calidad del aire.
 */

// 1. EVOLUCIÓN MEDIA DIARIA (Gráfica de Línea)
// Devuelve el valor promedio de un gas por cada hora del día seleccionado.
function getEvolucionDiaria($conn, $tipoId, $fecha) {
    // Agrupamos por hora (0-23) y sacamos la media del valor
    $sql = "SELECT HOUR(hora) as hora, AVG(valor) as media 
            FROM medicion 
            WHERE tipo_medicion_id = ? AND DATE(hora) = ? 
            GROUP BY HOUR(hora) 
            ORDER BY HOUR(hora) ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $tipoId, $fecha);
    $stmt->execute();
    $result = $stmt->get_result();

    $datos = [];
    while ($row = $result->fetch_assoc()) {
        $datos[] = $row;
    }
    return $datos;
}

// 2. RESUMEN MÁXIMOS/MÍNIMOS (Gráfica de Barras o Números)
// Devuelve el valor mínimo, máximo y promedio global del día.
function getMinMaxGlobal($conn, $tipoId, $fecha) {
    $sql = "SELECT MIN(valor) as minimo, MAX(valor) as maximo, AVG(valor) as media 
            FROM medicion 
            WHERE tipo_medicion_id = ? AND DATE(hora) = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $tipoId, $fecha);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_assoc();
}

// 3. TOP 5 SENSORES MÁS CONTAMINANTES (Gráfica de Barras Horizontal)
// Devuelve los 5 sensores con mayor promedio de contaminación ese día.
function getTopSensores($conn, $tipoId, $fecha) {
    $sql = "SELECT s.ubicacion_nombre, s.mac, AVG(m.valor) as promedio 
            FROM medicion m
            INNER JOIN sensor s ON m.sensor_id = s.id
            WHERE m.tipo_medicion_id = ? AND DATE(m.hora) = ?
            GROUP BY m.sensor_id 
            ORDER BY promedio DESC 
            LIMIT 5";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $tipoId, $fecha);
    $stmt->execute();
    $result = $stmt->get_result();

    $datos = [];
    while ($row = $result->fetch_assoc()) {
        // Si no tiene nombre, usamos la MAC o 'Sensor X'
        $nombre = $row['ubicacion_nombre'] ?? $row['mac'] ?? "Sensor Desconocido";
        $datos[] = [
            'nombre' => $nombre,
            'valor' => round($row['promedio'], 2)
        ];
    }
    return $datos;
}
?>