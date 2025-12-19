<?php
/**
 * @file guardarFotoIncidencia.php
 * @brief Gestión de archivos multimedia vinculados a reportes de incidencias.
 * @details Procesa flujos de datos en formato Base64, realiza la decodificación a binario (BLOB)
 * y los almacena de forma masiva en la base de datos utilizando el protocolo de datos largos de MySQL.
 * @author Manuel
 * @date 05/12/2025
 */

/**
 * @brief Almacena una colección de fotografías asociadas a una incidencia.
 * @param mysqli $conn Instancia de conexión a la base de datos.
 * @param array $data {
 * @var int $incidencia_id Identificador de la incidencia padre.
 * @var array $fotos Lista de cadenas de texto en formato Base64.
 * }
 * @return array {
 * @var string status 'ok' o 'error'.
 * @var string mensaje Descripción del resultado de la transacción.
 * }
 */
function guardarFotoIncidencia($conn, $data)
{
    // ----------------------------------------------------------------------------------------
    // 1. VALIDACIÓN DE ENTRADA
    // ----------------------------------------------------------------------------------------

    /** @section ValidacionEstructura Comprobación de integridad del array de fotos. */
    if (empty($data['incidencia_id']) || empty($data['fotos']) || !is_array($data['fotos'])) {
        return ["status" => "error", "mensaje" => "Faltan parámetros: incidencia_id o fotos (debe ser un array)."];
    }

    /** @var int $incidencia_id */
    $incidencia_id = (int)$data['incidencia_id'];
    /** @var array $fotos */
    $fotos = $data['fotos'];

    // ----------------------------------------------------------------------------------------
    // 2. PREPARACIÓN DE LA SENTENCIA
    // ----------------------------------------------------------------------------------------

    /** @section PreparacionInsert 
     * Definición de la consulta de inserción para datos binarios.
     */
    /* SQL:
     * Inserta un registro vinculando el ID de la incidencia con el contenido binario.
     * El campo 'foto' debe ser de tipo LONGBLOB o MEDIUMBLOB en el esquema.
     */
    $stmt = $conn->prepare("INSERT INTO fotos_incidencia (incidencia_id, foto) VALUES (?, ?)");
    
    if (!$stmt) {
        return ["status" => "error", "mensaje" => "Error en la preparación de la consulta: " . $conn->error];
    }

    // ----------------------------------------------------------------------------------------
    // 3. PROCESAMIENTO Y ENVÍO DE DATOS BINARIOS (BLOB)
    // ----------------------------------------------------------------------------------------

    /** @section IteracionFotos
     * Limpieza de cabeceras Base64 y transferencia de bits al servidor.
     */
    foreach ($fotos as $base64) {
        // Eliminación del prefijo MIME (data:image/png;base64, etc.)
        $base64 = preg_replace('/^data:image\/\w+;base64,/', '', $base64);
        
        /** @var string|bool $blob Datos binarios puros decodificados. */
        $blob = base64_decode($base64, true);
        
        if ($blob === false) {
            $stmt->close();
            return ["status" => "error", "mensaje" => "Una de las imágenes no tiene un formato Base64 válido."];
        }

        /* * @note Uso de send_long_data:
         * El protocolo de MySQL requiere enviar los BLOBs por separado para no exceder 
         * el límite de 'max_allowed_packet' en una sola sentencia.
         */
        $stmt->bind_param("ib", $incidencia_id, $null); // El segundo parámetro se reserva con null
        $stmt->send_long_data(1, $blob);               // Se envía el blob al parámetro en el índice 1 (foto)
        
        if (!$stmt->execute()) {
            $errorMsg = $stmt->error;
            $stmt->close();
            return ["status" => "error", "mensaje" => "Error al ejecutar la inserción: " . $errorMsg];
        }
    }

    $stmt->close();
    return ["status" => "ok", "mensaje" => "Fotos guardadas correctamente."];
}
?>