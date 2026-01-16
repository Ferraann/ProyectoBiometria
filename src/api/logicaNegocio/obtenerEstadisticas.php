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
    // AÑADIDO: "AND valor > 0" para evitar que el mínimo sea siempre 0 si hay algún fallo.
    // Si realmente quieres incluir el 0, quita esa parte, pero la barra no se verá.
    $sql = "SELECT MIN(valor) as minimo, MAX(valor) as maximo, AVG(valor) as media 
            FROM medicion 
            WHERE tipo_medicion_id = ? 
            AND DATE(hora) = ?
            AND valor > 0";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("is", $tipoId, $fecha);
    $stmt->execute();
    $result = $stmt->get_result();

    // Si no hay datos, devolvemos 0 para evitar errores
    $fila = $result->fetch_assoc();
    if (!$fila || $fila['minimo'] === null) {
        return ['minimo' => 0, 'maximo' => 0, 'media' => 0];
    }
    return $fila;
}

// 3. TOP 5 SENSORES MÁS CONTAMINANTES (Gráfica de Barras Horizontal)
// Devuelve los 5 sensores con mayor promedio de contaminación ese día.
function getTopSensores($conn, $tipoId, $fecha) {
    // 1. y 2. Preparamos el filtro de FECHA y GAS
    // Usamos rangos para que sea rapidísimo
    $fechaInicio = $fecha . " 00:00:00";
    $fechaFin    = $fecha . " 23:59:59";

    // 3. LA CONSULTA MÁGICA
    // - MAX(m.valor): Encuentra el pico más alto (ej: 500), ignora los ceros.
    // - GROUP BY s.id: OBLIGA a que sean centrales diferentes. Nunca saldrá la misma dos veces.
    $sql = "SELECT s.ubicacion_nombre, s.mac, MAX(m.valor) as pico_maximo
            FROM medicion m
            INNER JOIN sensor s ON m.sensor_id = s.id
            WHERE m.tipo_medicion_id = ? 
            AND m.hora >= ? AND m.hora <= ?
            GROUP BY s.id 
            ORDER BY pico_maximo DESC 
            LIMIT 5";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $tipoId, $fechaInicio, $fechaFin);
    $stmt->execute();
    $result = $stmt->get_result();

    $datos = [];
    while ($row = $result->fetch_assoc()) {
        // Si la central no tiene nombre, usamos su MAC para que no salga vacío
        $nombre = !empty($row['ubicacion_nombre']) ? $row['ubicacion_nombre'] : $row['mac'];

        // 4. Preparamos los datos para la gráfica
        $datos[] = [
            'nombre' => $nombre,
            'valor'  => (float)$row['pico_maximo'] // Convertimos a número decimal
        ];
    }
    return $datos;
}
?>