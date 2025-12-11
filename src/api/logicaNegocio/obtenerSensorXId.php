<?php
// ------------------------------------------------------------------
// Fichero: obtenerSensorXId.php
// Autor: Manuel
// Fecha: 11/12/2025
// ------------------------------------------------------------------
// Descripción:
//  Función para obtener un sensor a partir de su id.
// ------------------------------------------------------------------

function obtenerSensorXId($conn, $id)
{
    // Selecciona todos los campos de la tabla sensor
    $sql = "SELECT id, mac, modelo, nombre, problema FROM sensor WHERE id = ?";

    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        // En caso de error en la preparación
        error_log("Error al preparar la consulta: " . $conn->error);
        return null;
    }
    
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (!$result) {
        // En caso de error en la ejecución
        error_log("Error al ejecutar la consulta: " . $stmt->error);
        return null;
    }
    
    $sensor = $result->fetch_assoc();
    $stmt->close();
    
    return $sensor;
}
?>