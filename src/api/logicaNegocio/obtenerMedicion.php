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
?>