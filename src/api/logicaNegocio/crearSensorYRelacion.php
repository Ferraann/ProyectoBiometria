<?php
/**
 * @file crearSensorYRelacion.php
 * @brief Lógica de negocio para el registro de hardware y vinculación con usuarios.
 * @details Gestiona el aprovisionamiento de dispositivos en el sistema. Si el sensor no existe, 
 * lo crea; si ya existe, garantiza que la nueva vinculación sea la única activa ('actual').
 * @author Manuel
 * @date 05/12/2025
 */

/**
 * @brief Asigna un sensor a un usuario y gestiona la integridad del inventario.
 * @param mysqli $conn Instancia de conexión activa a la base de datos.
 * @param array $data {
 * @var string $mac Dirección física del sensor.
 * @var int $usuario_id Identificador del usuario que reclama el sensor.
 * }
 * @return array {
 * @var string status 'ok' o 'error'.
 * @var string mensaje Descripción del proceso.
 * @var int sensor_id ID del sensor procesado.
 * @var int id_relacion ID de la nueva entrada en usuario_sensor.
 * }
 */
function crearSensorYRelacion($conn, $data)
{
    // ----------------------------------------------------------------------------------------
    // 1. VALIDACIÓN DE PARÁMETROS
    // ----------------------------------------------------------------------------------------

    /** @section ValidacionEntrada Comprobación de parámetros obligatorios. */
    if (!isset($data['mac']) || !isset($data['usuario_id'])) {
        return ["status" => "error", "mensaje" => "Faltan parámetros: mac y usuario_id son obligatorios."];
    }

    /** @var string $mac */
    $mac = $data['mac'];
    /** @var int $usuario_id */
    $usuario_id = (int)$data['usuario_id'];

    // ----------------------------------------------------------------------------------------
    // 2. GESTIÓN DE LA ENTIDAD SENSOR
    // ----------------------------------------------------------------------------------------

    /** @section GestionSensor 
     * Busca la existencia del dispositivo por MAC para evitar duplicados en la tabla maestra.
     */
    $sqlBuscarSensor = "SELECT id FROM sensor WHERE mac = ?";
    $stmtBuscar = $conn->prepare($sqlBuscarSensor);
    $stmtBuscar->bind_param("s", $mac);
    $stmtBuscar->execute();
    $result = $stmtBuscar->get_result();

    if ($row = $result->fetch_assoc()) {
        /** @var int $sensor_id ID del sensor existente. */
        $sensor_id = $row['id'];
    } else {
        /** @note Si el sensor es nuevo, se inicializa con el campo 'problema' en 0 (sin fallos). */
        $sqlInsertarSensor = "INSERT INTO sensor (mac, problema) VALUES (?, 0)";
        $stmtInsertar = $conn->prepare($sqlInsertarSensor);
        $stmtInsertar->bind_param("s", $mac);

        if ($stmtInsertar->execute()) {
            $sensor_id = $conn->insert_id;
        } else {
            return ["status" => "error", "mensaje" => "Error al crear el sensor: " . $conn->error];
        }
    }

    // ----------------------------------------------------------------------------------------
    // 3. CONTROL DE HISTORIAL Y EXCLUSIVIDAD
    // ----------------------------------------------------------------------------------------

    /** @section RotacionRelaciones 
     * Inactiva cualquier relación previa del sensor para asegurar que solo haya un dueño 'actual'.
     * @note Registra la fecha de fin de relación de forma automática.
     */
    $sqlCerrarRelaciones = "UPDATE usuario_sensor 
                            SET actual = 0, fin_relacion = NOW() 
                            WHERE sensor_id = ? AND actual = 1";
    $stmtCerrar = $conn->prepare($sqlCerrarRelaciones);
    $stmtCerrar->bind_param("i", $sensor_id);
    $stmtCerrar->execute();

    // ----------------------------------------------------------------------------------------
    // 4. CREACIÓN DE VÍNCULO ACTIVO
    // ----------------------------------------------------------------------------------------

    /** @section NuevaRelacion 
     * Inserta el nuevo registro de vinculación usuario-sensor como relación vigente.
     */
    $sqlNuevaRelacion = "INSERT INTO usuario_sensor (usuario_id, sensor_id, actual, inicio_relacion)
                         VALUES (?, ?, 1, NOW())";
    $stmtRelacion = $conn->prepare($sqlNuevaRelacion);
    $stmtRelacion->bind_param("ii", $usuario_id, $sensor_id);

    if ($stmtRelacion->execute()) {
        return [
            "status" => "ok",
            "mensaje" => "Sensor asignado correctamente.",
            "sensor_id" => $sensor_id,
            "id_relacion" => $conn->insert_id
        ];
    } else {
        return ["status" => "error", "mensaje" => "Error al crear la relación usuario-sensor: " . $conn->error];
    }
}
?>