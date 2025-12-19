<?php
/**
 * @file crearIncidencia.php
 * @brief Lógica de negocio para el registro de nuevas incidencias en el sistema.
 * @details Procesa la creación de reportes técnicos vinculados a usuarios y, opcionalmente, a sensores.
 * Gestiona la asignación automática del estado inicial y la integridad de los datos de entrada.
 * @author Manuel
 * @date 11/12/2025
 */

/**
 * @brief Registra una nueva incidencia en la base de datos.
 * @param mysqli $conn Instancia de conexión activa a la base de datos.
 * @param array $data {
 * @var int $id_user ID del usuario que reporta.
 * @var string $titulo Resumen breve del problema.
 * @var string $descripcion Detalle extenso de la incidencia.
 * @var int|null $sensor_id (Opcional) Identificador del sensor relacionado.
 * }
 * @return array {
 * @var string status 'ok' o 'error'.
 * @var int|null id_incidencia ID generado tras la inserción (solo en éxito).
 * @var string mensaje Descripción del resultado de la operación.
 * }
 */
function crearIncidencia($conn, $data)
{
    // ----------------------------------------------------------------------------------------
    // 1. VALIDACIÓN Y NORMALIZACIÓN DE DATOS
    // ----------------------------------------------------------------------------------------

    /** @section ValidacionEntrada Comprobación de campos obligatorios y saneado de tipos. */
    if (!isset($data['id_user'], $data['titulo'], $data['descripcion'])) {
        return ["status" => "error", "mensaje" => "Faltan parámetros obligatorios (id_user, titulo o descripcion)."];
    }

    /** * @var int|null $sensor_id 
     * @note Se normaliza a null si el campo llega vacío para cumplir con la integridad referencial de la DB.
     */
    $sensor_id = isset($data['sensor_id']) && $data['sensor_id'] !== "" 
                 ? (int)$data['sensor_id'] 
                 : null;

    // ----------------------------------------------------------------------------------------
    // 2. GESTIÓN DEL ESTADO INICIAL
    // ----------------------------------------------------------------------------------------

    /** * @section ObtencionEstado
     * Recupera dinámicamente el ID para el estado 'Abierta'. 
     * @note Se establece el ID '1' como fallback de seguridad si la consulta falla.
     */
    $sqlEstado = "SELECT id FROM estado_incidencia WHERE nombre = 'Abierta' LIMIT 1";
    $resEstado = $conn->query($sqlEstado);
    $estadoRow = $resEstado ? $resEstado->fetch_assoc() : null;
    $estadoInicial = $estadoRow ? $estadoRow['id'] : 1;

    // ----------------------------------------------------------------------------------------
    // 3. PERSISTENCIA EN BASE DE DATOS
    // ----------------------------------------------------------------------------------------

    /** * @section RegistroIncidencia
     * Construcción dinámica del Statement para manejar de forma segura el valor NULL en id_sensor.
     */
    if ($sensor_id !== null) {
        // Caso A: Incidencia vinculada a un sensor
        $sql = "INSERT INTO incidencias (id_user, titulo, descripcion, estado_id, id_sensor) 
                VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "issii",
            $data['id_user'],
            $data['titulo'],
            $data['descripcion'],
            $estadoInicial,
            $sensor_id
        );
    } else {
        // Caso B: Incidencia genérica (sin sensor)
        $sql = "INSERT INTO incidencias (id_user, titulo, descripcion, estado_id, id_sensor) 
                VALUES (?, ?, ?, ?, NULL)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "issi",
            $data['id_user'],
            $data['titulo'],
            $data['descripcion'],
            $estadoInicial
        );
    }

    if ($stmt->execute()) {
        /** @var int $newId Captura del ID autoincremental generado. */
        $newId = $conn->insert_id;
        $stmt->close();
        
        return [
            "status" => "ok",
            "id_incidencia" => $newId,
            "mensaje" => "Incidencia creada correctamente con estado inicial 'Abierta'."
        ];
    } else {
        $errorMsg = $conn->error;
        $stmt->close();
        return [
            "status" => "error",
            "mensaje" => "Error al registrar incidencia: " . $errorMsg
        ];
    }
}
?>