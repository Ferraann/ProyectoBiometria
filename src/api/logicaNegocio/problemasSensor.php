<?php
// ------------------------------------------------------------------
// Fichero: marcarSensorConProblema.php
// Autor: Manuel
// Fecha: 30/10/2025
// ------------------------------------------------------------------
// Descripción:
//  Función para terminar la relación de un sensor y marcarlo con problema.
//  Finaliza todas las relaciones activas y actualiza el estado del sensor.
// ------------------------------------------------------------------

function marcarSensorConProblemas($conn, $data)
{
    if (!isset($data['sensor_id'])) {
        return ["status" => "error", "mensaje" => "Falta el parámetro sensor_id."];
    }

    $sensor_id = $data['sensor_id'];

    // 1. Finalizar relaciones activas del sensor
    $sqlFinalizar = "UPDATE usuario_sensor 
                     SET actual = 0, fin_relacion = NOW() 
                     WHERE sensor_id = ? AND actual = 1";
    $stmtFin = $conn->prepare($sqlFinalizar);
    $stmtFin->bind_param("i", $sensor_id);
    $stmtFin->execute();

    // 2. Marcar el sensor como con problema
    $sqlProblema = "UPDATE sensor SET problema = 1 WHERE id = ?";
    $stmtProb = $conn->prepare($sqlProblema);
    $stmtProb->bind_param("i", $sensor_id);

    if ($stmtProb->execute()) {
        return [
            "status" => "ok",
            "mensaje" => "Sensor marcado con problema y relación finalizada.",
            "filas_relaciones_finalizadas" => $stmtFin->affected_rows
        ];
    } else {
        return ["status" => "error", "mensaje" => "Error al actualizar sensor: " . $conn->error];
    }
}