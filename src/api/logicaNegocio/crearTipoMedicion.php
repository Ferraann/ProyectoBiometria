<?php
/**
 * @file crearTipoMedicion.php
 * @brief Lógica de negocio para la definición de nuevos tipos de magnitud física.
 * @details Permite registrar en el sistema nuevas categorías de medición (ej. Temperatura, Humedad) 
 * especificando su nombre, unidad de medida y un texto descriptivo.
 * @author Manuel
 * @date 05/12/2025
 */

/**
 * @brief Crea un nuevo registro en el catálogo de tipos de medición.
 * @param mysqli $conn Instancia de conexión activa a la base de datos.
 * @param array $data {
 * @var string $medida Nombre de la magnitud (ej. 'Temperatura').
 * @var string $unidad Símbolo de la unidad (ej. '°C').
 * @var string|null $txt Descripción o contexto adicional del tipo de medición.
 * }
 * @return array {
 * @var string status 'ok' o 'error'.
 * @var string mensaje Descripción del resultado.
 * @var int|null id Identificador autoincremental asignado (solo en éxito).
 * }
 */
function crearTipoMedicion($conn, $data)
{
    // ----------------------------------------------------------------------------------------
    // 1. VALIDACIÓN DE PARÁMETROS
    // ----------------------------------------------------------------------------------------

    /** @section ValidacionEntrada Comprobación de integridad y contenido de los datos. */
    if (!isset($data['medida']) || !isset($data['unidad'])) {
        return ["status" => "error", "mensaje" => "Faltan parámetros: medida y unidad son obligatorios."];
    }

    /** @note Se garantiza que los campos críticos no contengan cadenas vacías tras el envío. */
    if (empty($data['medida']) || empty($data['unidad'])) {
        return ["status" => "error", "mensaje" => "Los parámetros medida y unidad no pueden estar vacíos."];
    }

    // ----------------------------------------------------------------------------------------
    // 2. PERSISTENCIA EN BASE DE DATOS
    // ----------------------------------------------------------------------------------------

    /** @section RegistroMedicion
     * Inserción del nuevo tipo de magnitud en la tabla maestra 'tipo_medicion'.
     */

    /* SQL:
     * - medida: Nombre descriptivo de lo que se mide.
     * - unidad: Símbolo técnico de la unidad de medida.
     * - txt: Información contextual adicional.
     */
    $sql = "INSERT INTO tipo_medicion (medida, unidad, txt) 
            VALUES (?, ?, ?)";
    
    /** @var mysqli_stmt $stmt */
    $stmt = $conn->prepare($sql);
    
    // Vinculación de parámetros: tres strings (sss)
    $stmt->bind_param("sss", $data['medida'], $data['unidad'], $data['txt']);

    if ($stmt->execute()) {
        /** @var int $newId Captura del ID generado por la base de datos. */
        $newId = $conn->insert_id;
        $stmt->close(); 

        return [
            "status" => "ok", 
            "mensaje" => "Tipo de medición creado correctamente.", 
            "id" => $newId
        ];
    } else {
        $errorMsg = $conn->error;
        return [
            "status" => "error", 
            "mensaje" => "Error al crear tipo de medición: " . $errorMsg
        ];
    }
}
?>