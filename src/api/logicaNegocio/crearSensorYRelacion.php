<?php
// ------------------------------------------------------------------
// Fichero: crearSensorYRelacion.php
// Autor: Manuel
// Fecha: 5/12/2025
// ------------------------------------------------------------------
// Descripción:
// Función para asignar un sensor a un usuario, creando el sensor si 
// no existe o modificar la relación si ya existe
// ------------------------------------------------------------------

function crearSensorYRelacion($conn, $data)
{
    if (!isset($data['mac']) || !isset($data['usuario_id'])) {
        return ["status" => "error", "mensaje" => "Faltan parámetros: mac y usuario_id son obligatorios."];
    }

    $mac = $data['mac'];
    $usuario_id = $data['usuario_id'];

    // 1. Comprobar si el sensor ya existe
    $sqlBuscarSensor = "SELECT id FROM sensor WHERE mac = ?";
    $stmtBuscar = $conn->prepare($sqlBuscarSensor);
    $stmtBuscar->bind_param("s", $mac);
    $stmtBuscar->execute();
    $result = $stmtBuscar->get_result();

    if ($row = $result->fetch_assoc()) {
        $sensor_id = $row['id'];
    } else {
        // 2. Si no existe, lo creamos
        $sqlInsertarSensor = "INSERT INTO sensor (mac, problema) VALUES (?, 0)";
        $stmtInsertar = $conn->prepare($sqlInsertarSensor);
        $stmtInsertar->bind_param("s", $mac);

        if ($stmtInsertar->execute()) {
            $sensor_id = $conn->insert_id;
        } else {
            return ["status" => "error", "mensaje" => "Error al crear el sensor: " . $conn->error];
        }
    }

    // 3. Cerrar relaciones anteriores activas de ese sensor
    $sqlCerrarRelaciones = "UPDATE usuario_sensor 
                            SET actual = 0, fin_relacion = NOW() 
                            WHERE sensor_id = ? AND actual = 1";
    $stmtCerrar = $conn->prepare($sqlCerrarRelaciones);
    $stmtCerrar->bind_param("i", $sensor_id);
    $stmtCerrar->execute();

    // 4. Crear nueva relación usuario-sensor
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