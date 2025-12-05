<?php
// ------------------------------------------------------------------
// Fichero: cerrarIncidencia.php
// Autor: Manuel
// Fecha: 5/12/2025
// ------------------------------------------------------------------
// Descripción:
//  Función para cerrar una incidencia en la base de datos.
//  Actualiza el estado a "Cerrada" y registra la fecha de cierre.
// ------------------------------------------------------------------

function cerrarIncidencia($conn, $data)
{
    if (!isset($data['incidencia_id'])) {
        return ["status" => "error", "mensaje" => "Falta el parámetro incidencia_id."];
    }

    // Obtenemos el ID del estado "Cerrada"
    $sqlEstado = "SELECT id FROM estado_incidencia WHERE nombre = 'Cerrada' LIMIT 1";
    $resEstado = $conn->query($sqlEstado);
    $estadoRow = $resEstado->fetch_assoc();
    $estadoId = $estadoRow ? $estadoRow['id'] : 4;

    // Actualizamos la incidencia
    $sql = "UPDATE incidencias SET estado_id = ?, fecha_cierre = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $estadoId, $data['incidencia_id']);

    if ($stmt->execute()) {
        return ["status" => "ok", "mensaje" => "Incidencia cerrada correctamente."];
    } else {
        return ["status" => "error", "mensaje" => "Error al cerrar incidencia: " . $conn->error];
    }
}
?>