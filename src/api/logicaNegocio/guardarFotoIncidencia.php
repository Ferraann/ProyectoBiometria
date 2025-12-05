<?php
// ------------------------------------------------------------------
// Fichero: guardarFotoIncidencia.php
// Autor: Manuel
// Fecha: 5/12/2025
// ------------------------------------------------------------------
// Descripción:
//  Función para guardar fotos de incidencias en la base de datos.
//  Procesa imágenes en formato base64 y las almacena como BLOB.
// ------------------------------------------------------------------

function guardarFotoIncidencia($conn, $data)
{
    if (empty($data['incidencia_id']) || empty($data['fotos']) || !is_array($data['fotos'])) {
        return ["status" => "error", "mensaje" => "Faltan parámetros: incidencia_id o fotos."];
    }

    $incidencia_id = (int)$data['incidencia_id'];
    $fotos = $data['fotos'];

    $stmt = $conn->prepare("INSERT INTO fotos_incidencia (incidencia_id, foto) VALUES (?, ?)");
    
    if (!$stmt) {
        return ["status" => "error", "mensaje" => "Error en la preparación de la consulta."];
    }

    foreach ($fotos as $base64) {
        $base64 = preg_replace('/^data:image\/\w+;base64,/', '', $base64);
        $blob = base64_decode($base64, true);
        
        if ($blob === false) {
            $stmt->close();
            return ["status" => "error", "mensaje" => "Una de las imágenes no es válida."];
        }

        $stmt->bind_param("ib", $incidencia_id, $blob);
        $stmt->send_long_data(1, $blob);
        
        if (!$stmt->execute()) {
            $stmt->close();
            return ["status" => "error", "mensaje" => "Error al guardar la foto."];
        }
    }

    $stmt->close();
    return ["status" => "ok", "mensaje" => "Fotos guardadas correctamente."];
}