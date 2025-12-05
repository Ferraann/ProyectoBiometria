<?php
// ------------------------------------------------------------------
// Fichero: guardarMedicion.php
// Autor: Manuel
// Fecha: 5/12/2025
// ------------------------------------------------------------------
// Descripción:
//  Este archivo contiene la función para guardar una nueva medición
//  en la base de datos, verificando relaciones y parámetros necesarios.
// ------------------------------------------------------------------

function guardarMedicion($conn, $data)
{
    // 1 Verificar parámetros obligatorios
    if (!isset($data['tipo_medicion_id'], $data['valor'], $data['sensor_id'], $data['localizacion'])) {
        return ["status" => "error", "mensaje" => "Faltan parámetros obligatorios."];
    }

    $sensor_id = $data['sensor_id'];

    // 2 Comprobar si existe una relación activa usuario-sensor con ese sensor
    $sqlCheck = "SELECT COUNT(*) as count FROM usuario_sensor WHERE sensor_id = ? AND actual = 1";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->bind_param("i", $sensor_id);
    $stmtCheck->execute();
    $result = $stmtCheck->get_result();
    $row = $result->fetch_assoc();
    $existeRelacion = $row['count'];
    $stmtCheck->close();

    // 3 Si no hay relación activa, no permitir guardar la medición
    if ($existeRelacion == 0) {
        return [
            "status" => "error",
            "mensaje" => "No existe una relación activa entre el sensor y un usuario."
        ];
    }

    // 4 Insertar la medición si la relación es válida
    $sql = "INSERT INTO medicion (tipo_medicion_id, valor, sensor_id, localizacion)
            VALUES (?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "idis",
        $data['tipo_medicion_id'],
        $data['valor'],
        $data['sensor_id'],
        $data['localizacion']
    );

    if ($stmt->execute()) {
        return ["status" => "ok", "mensaje" => "Medición guardada correctamente."];
    } else {
        return ["status" => "error", "mensaje" => "Error al guardar medición: " . $conn->error];
    }
}