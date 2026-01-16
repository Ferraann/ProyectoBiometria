<?php
/**
 * @file obtenerMedicion.php
 * @brief Recuperación global de datos telemétricos procesados.
 * @details Extrae el historial completo de lecturas de sensores, integrando descripciones 
 * de magnitudes físicas y metadatos de hardware mediante consultas relacionales.
 * @author Manuel
 * @date 05/12/2025
 */

/**
 * @brief Obtiene todas las mediciones registradas con sus detalles asociados.
 * @details Realiza un triple JOIN para unificar el valor de la medición con su unidad 
 * de medida (desde tipo_medicion) y la identificación del hardware (desde sensor).
 * @param mysqli $conn Instancia de conexión activa a la base de datos.
 * @return array Colección de registros asociativos. Devuelve un array vacío si no hay datos.
 */
function obtenerMediciones($conn)
{
    // ----------------------------------------------------------------------------------------
    // 1. CONSULTA RELACIONAL COMPUESTA
    // ----------------------------------------------------------------------------------------

    /** @section ConsultaTelemétrica 
     * Unión de tablas para transformar IDs numéricos en información legible.
     */
    /* SQL:
     * - m.id, m.valor, m.hora: Datos crudos de la lectura.
     * - tm.medida, tm.unidad: Contexto de la magnitud (ej. 'Temperatura', '°C').
     * - s.mac: Identificador físico del sensor que originó el dato.
     * - INNER JOIN: Asegura que solo se devuelvan mediciones con tipos y sensores válidos.
     * - ORDER BY m.hora DESC: Prioriza las lecturas más recientes (Tiempo Real).
     */
    $sql = "SELECT m.id, tm.medida, tm.unidad, m.valor, m.hora, m.localizacion, s.mac
            FROM medicion m
            INNER JOIN tipo_medicion tm ON m.tipo_medicion_id = tm.id
            INNER JOIN sensor s ON m.sensor_id = s.id
            ORDER BY m.hora DESC";

    /** @var mysqli_result|bool $result Conjunto de resultados de la ejecución. */
    $result = $conn->query($sql);
    $datos = [];

    // ----------------------------------------------------------------------------------------
    // 2. PROCESAMIENTO DE RESULTADOS
    // ----------------------------------------------------------------------------------------

    if ($result && $result->num_rows > 0) {
        /** @note Se itera el cursor para construir la lista de objetos JSON. */
        while ($row = $result->fetch_assoc()) {
            $datos[] = $row;
        }
    }

    return $datos;
}

/**
 * @brief Recupera la última medición de cada sensor para un tipo de gas específico.
 * @details Esta función optimiza la carga del mapa al filtrar por la magnitud física 
 * deseada (ej. 'CO2', 'Temperatura') y obtener solo el registro más reciente de cada 
 * dispositivo físico, evitando la superposición de datos históricos.
 * * @param mysqli $conn Instancia de conexión activa a la base de datos.
 * @param string $tipoId Id de la magnitud a filtrar (coincidente con tm.medida).
 * * @return array Colección de puntos con latitud, longitud y valor.
 * * @note La localización se asume almacenada en formato "lat,long" en la columna m.localizacion.
 */
function getMedicionesXTipo($conn, $tipoId, $fecha = null)
{
    // Si no llega fecha, usamos la de hoy por defecto
    $fechaFiltro = $fecha ?? date('Y-m-d');

    // Modificamos la consulta para filtrar por el día específico usando DATE()
    // Nota: DATE(m.hora) extrae solo la parte Y-m-d de la columna datetime
    $sql = "SELECT m.valor, m.localizacion, m.hora, tm.unidad, tm.medida, s.mac
            FROM medicion m
            INNER JOIN tipo_medicion tm ON m.tipo_medicion_id = tm.id
            INNER JOIN sensor s ON m.sensor_id = s.id
            WHERE m.tipo_medicion_id = ? 
            AND DATE(m.hora) = ? 
            ORDER BY m.hora ASC";

    $stmt = $conn->prepare($sql);

    // Bind de parámetros: "is" -> (integer, string)
    $stmt->bind_param("is", $tipoId, $fechaFiltro);

    $stmt->execute();
    $result = $stmt->get_result();

    $puntos = [];

    while ($row = $result->fetch_assoc()) {
        if (!empty($row['localizacion'])) {
            $coords = explode(',', $row['localizacion']);
            if (count($coords) === 2) {
                $puntos[] = [
                    "lat"    => (float)trim($coords[0]),
                    "lon"    => (float)trim($coords[1]),
                    "value"  => (float)$row['valor'],
                    "unit"   => $row['unidad'],
                    "label"  => $row['medida'],
                    "sensor" => $row['mac'],
                    "fecha"  => $row['hora']
                ];
            }
        }
    }

    return $puntos;
}

// Función para obtener SOLO los sensores del usuario logueado
function getMedicionesDeUsuario($conn, $usuarioId, $tipoId, $fecha) {

    // 1. SELECT: Sacamos la MAC (Imprescindible), Coordenadas y el Valor Máximo
    // 2. JOIN usuario_sensor: Aquí está la magia. Solo cogemos sensores vinculados a TU id.

    $sql = "SELECT 
                s.mac, 
                s.latitud as lat, 
                s.longitud as lon, 
                MAX(m.valor) as max_valor,
                tm.unidad,
                tm.medida
            FROM sensor s
            INNER JOIN usuario_sensor us ON s.id = us.sensor_id
            INNER JOIN medicion m ON s.id = m.sensor_id
            INNER JOIN tipo_medicion tm ON m.tipo_medicion_id = tm.id
            WHERE us.usuario_id = ? 
              AND m.tipo_medicion_id = ? 
              AND DATE(m.hora) = ?
            GROUP BY s.id"; // Agrupamos por sensor para no repetir puntos

    $stmt = $conn->prepare($sql);
    // "iis" -> integer (userId), integer (gasId), string (fecha)
    $stmt->bind_param("iis", $usuarioId, $tipoId, $fecha);

    $stmt->execute();
    $result = $stmt->get_result();

    $puntos = [];
    while ($row = $result->fetch_assoc()) {
        // Validación de coordenadas para que no falle el mapa
        if ($row['lat'] != 0 && $row['lon'] != 0) {
            $puntos[] = [
                "lat"    => (float)$row['lat'],
                "lon"    => (float)$row['lon'],
                "value"  => (float)$row['max_valor'],
                "sensor" => $row['mac'], // AQUÍ VA LA MAC
                "unit"   => $row['unidad'],
                "label"  => $row['medida']
            ];
        }
    }
    return $puntos;
}
?>