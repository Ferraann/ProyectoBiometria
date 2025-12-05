<?php
// ------------------------------------------------------------------
// Fichero: obtenerIncidenciaXId.php
// Autor: Manuel
// Fecha: 5/12/2025
// ------------------------------------------------------------------
// Descripción:
//  Función para obtener la incidencia a partir de su id
// ------------------------------------------------------------------

function obtenerIncidenciaXId($conn, $id)
{
    $sql = "SELECT * FROM incidencias WHERE id = ?";

    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        return null;
    }
    
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result) {
        return null;
    }
    
    $incidencia = $result->fetch_assoc();
    $stmt->close();
    
    return $incidencia;
}
?>
