<?php
// ------------------------------------------------------------------
// Fichero: obtenerUsuarioXId.php
// Autor: Manuel
// Fecha: 5/12/2025
// ------------------------------------------------------------------
// Descripción:
//  Función para obtener usuario a partir de su id
// ------------------------------------------------------------------

function obtenerUsuarioXId($conn, $id)
{
    $sql = "SELECT * FROM usuario WHERE id = ?";

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
    
    $usuario = $result->fetch_assoc();
    $stmt->close();
    
    return $usuario;
}
?>