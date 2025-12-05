<?php
function reactivarSensor($conn, $data)
{
    if (!isset($data['sensor_id'])) {
        return ["status" => "error", "mensaje" => "Falta el parámetro sensor_id."];
    }

    $sensor_id = $data['sensor_id'];

    $sql = "UPDATE sensor SET problema = 0 WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return ["status" => "error", "mensaje" => "Error en la preparación de la consulta: " . $conn->error];
    }

    $stmt->bind_param("i", $sensor_id);

    if ($stmt->execute()) {
        $stmt->close(); // Cerrar la declaración
        return ["status" => "ok", "mensaje" => "Sensor reactivado correctamente."];
    } else {
        return ["status" => "error", "mensaje" => "Error al reactivar sensor: " . $conn->error];
    }
}
?>
