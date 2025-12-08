<?php
// ------------------------------------------------------------------
// Fichero: crearTipoMedicion.php
// Autor: Manuel
// Fecha: 5/12/2025
// ------------------------------------------------------------------
// Descripción:
//  Este archivo maneja la creación de un nuevo tipo de medición
//  a través de la API. Recibe datos en formato JSON y llama a la
//  función crearTipoMedicion para interactuar con la base de datos.
// ------------------------------------------------------------------

function crearTipoMedicion($conn, $data)
{
    if (!isset($data['medida']) || !isset($data['unidad'])) {
        return ["status" => "error", "mensaje" => "Faltan parámetros: medida y unidad son obligatorios."];
    }

    // Validación adicional de datos
    if (empty($data['medida']) || empty($data['unidad'])) {
        return ["status" => "error", "mensaje" => "Los parámetros medida y unidad no pueden estar vacíos."];
    }

    $sql = "INSERT INTO tipo_medicion (medida, unidad, txt) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $data['medida'], $data['unidad'], $data['txt']);

    if ($stmt->execute()) {
        $stmt->close(); // Cerrar la declaración
        return ["status" => "ok", "mensaje" => "Tipo de medición creado correctamente.", "id" => $conn->insert_id];
    } else {
        return ["status" => "error", "mensaje" => "Error al crear tipo de medición: " . $conn->error];
    }
}
?>