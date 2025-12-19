<?php
/**
 * @file obtenerFotosIncidencias.php
 * @brief Recuperación de evidencias gráficas asociadas a reportes de incidencias.
 * @details Extrae los objetos binarios (BLOB) almacenados en la base de datos y los 
 * transforma en cadenas Base64 para su correcta interpretación por interfaces web o móviles.
 * @author Manuel
 * @date 5/12/2025
 */

/**
 * @brief Obtiene la colección de fotografías de una incidencia específica.
 * @param mysqli $conn Instancia de conexión activa a la base de datos.
 * @param int $incidencia_id Identificador único de la incidencia.
 * @return array {
 * @var string status 'ok'.
 * @var array fotos Lista de objetos conteniendo las imágenes codificadas en Base64.
 * }
 */
function obtenerFotosIncidencia($conn, $incidencia_id) {
    // ----------------------------------------------------------------------------------------
    // 1. PREPARACIÓN DE LA CONSULTA
    // ----------------------------------------------------------------------------------------

    /** @section ConsultaFotos Recuperación de datos binarios. */
    /* SQL:
     * Selecciona exclusivamente la columna 'foto' (BLOB) filtrando por la clave foránea.
     * Esto permite obtener todas las capturas vinculadas a un único reporte.
     */
    $sql = "SELECT foto FROM fotos_incidencia WHERE incidencia_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $incidencia_id);
    $stmt->execute();
    
    /** @var mysqli_result $res Conjunto de resultados binarios. */
    $res = $stmt->get_result();
    $fotos = [];

    // ----------------------------------------------------------------------------------------
    // 2. CODIFICACIÓN Y ESTRUCTURACIÓN
    // ----------------------------------------------------------------------------------------

    /** @section ProcesamientoBLOB Iteración y conversión de formato. */
    while ($row = $res->fetch_assoc()) {
        /** * @note base64_encode es indispensable para encapsular el binario dentro de un JSON,
         * permitiendo su uso directo en el atributo 'src' de etiquetas <img>.
         */
        $fotos[] = [
            "foto" => base64_encode($row['foto'])
        ];
    }

    $stmt->close();
    
    return [
        "status" => "ok", 
        "fotos" => $fotos
    ];
}