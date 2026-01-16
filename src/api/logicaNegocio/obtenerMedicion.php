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

    // SOLUCIÓN:
    // 1. Usamos MAX(m.valor) para coger el pico de contaminación del día.
    // 2. Usamos GROUP BY m.sensor_id para que cada sensor solo salga UNA vez en el mapa.
    // 3. Añadimos m.localizacion en el SELECT (usando ANY_VALUE o asumiendo que es constante por sensor).

    $sql = "SELECT 
                MAX(m.valor) as max_valor, 
                m.localizacion, 
                tm.unidad, 
                tm.medida, 
                s.mac
            FROM medicion m
            INNER JOIN tipo_medicion tm ON m.tipo_medicion_id = tm.id
            INNER JOIN sensor s ON m.sensor_id = s.id
            WHERE m.tipo_medicion_id = ? 
            AND DATE(m.hora) = ? 
            GROUP BY m.sensor_id";

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
                // Limpiamos espacios en blanco por si acaso "40.123, -3.123"
                $lat = (float)trim($coords[0]);
                $lon = (float)trim($coords[1]);

                // Verificación extra: Si lat/lon son 0, el punto no se pinta bien
                if ($lat != 0 && $lon != 0) {
                    $puntos[] = [
                        "lat"    => $lat,
                        "lon"    => $lon,
                        // ¡IMPORTANTE!: Usamos el alias 'max_valor'
                        "value"  => (float)$row['max_valor'],
                        "unit"   => $row['unidad'],
                        "label"  => $row['medida'],
                        "sensor" => $row['mac'],
                        // Usamos la fecha del filtro ya que agrupamos
                        "fecha"  => $fechaFiltro
                    ];
                }
            }
        }
    }

    return $puntos;
}
?>