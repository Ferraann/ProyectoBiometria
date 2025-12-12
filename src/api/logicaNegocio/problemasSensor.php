<?php
// ------------------------------------------------------------------
// Fichero: problemas_sensor.php
// Autor: Manuel
// Fecha: 11/12/2025
// ------------------------------------------------------------------
// Descripción:
//  Conjunto de funciones para gestionar el estado de problema (problema=0/1) del sensor
//  SIN afectar las relaciones activas con usuarios.
// ------------------------------------------------------------------

function sensorConProblemas($conn, $data)
{
    if (!isset($data['sensor_id'])) {
        return ["status" => "error", "mensaje" => "Falta el parámetro sensor_id."];
    }

    $sensor_id = $data['sensor_id'];

    // Se marca el sensor como con problema (problema = 1)
    $sqlProblema = "UPDATE sensor SET problema = 1 WHERE id = ?";
    $stmtProb = $conn->prepare($sqlProblema);
    
    if (!$stmtProb) {
        return ["status" => "error", "mensaje" => "Error al preparar la consulta: " . $conn->error];
    }
    
    $stmtProb->bind_param("i", $sensor_id);

    if ($stmtProb->execute()) {
        $stmtProb->close();
        return [
            "status" => "ok",
            "mensaje" => "Sensor marcado con problema."
        ];
    } else {
        $error = $stmtProb->error;
        $stmtProb->close();
        return ["status" => "error", "mensaje" => "Error al actualizar sensor: " . $error];
    }
}

function sensorSinProblemas($conn, $data)
{
    if (!isset($data['sensor_id'])) {
        return ["status" => "error", "mensaje" => "Falta el parámetro sensor_id."];
    }

    $sensor_id = $data['sensor_id'];

    // Se marca el sensor como reactivado (problema = 0)
    $sql = "UPDATE sensor SET problema = 0 WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return ["status" => "error", "mensaje" => "Error en la preparación de la consulta: " . $conn->error];
    }

    $stmt->bind_param("i", $sensor_id);

    if ($stmt->execute()) {
        $stmt->close(); 
        return ["status" => "ok", "mensaje" => "Sensor reactivado correctamente."];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ["status" => "error", "mensaje" => "Error al reactivar sensor: " . $error];
    }
}
?>