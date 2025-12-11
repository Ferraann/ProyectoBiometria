<?php
// ------------------------------------------------------------------
// Fichero: sumarPuntosUsuario.php
// Autor: Manuel
// Fecha: 11/12/2025
// ------------------------------------------------------------------
// Descripción:
//  Función para sumar una cantidad específica de puntos a un usuario.
// ------------------------------------------------------------------

function sumarPuntosUsuario($conn, $data)
{
    if (!isset($data['id_usuario'], $data['puntos_a_sumar'])) {
        return ["status" => "error", "mensaje" => "Faltan parámetros: id_usuario o puntos_a_sumar."];
    }

    $idUsuario = (int)$data['id_usuario'];
    $puntosSumar = (int)$data['puntos_a_sumar'];

    if ($idUsuario <= 0 || $puntosSumar <= 0) {
        return ["status" => "error", "mensaje" => "ID de usuario o cantidad de puntos no válidos."];
    }

    // Consulta SQL para sumar los puntos a la columna actual
    $sql = "UPDATE usuario SET puntos = puntos + ? WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        return ["status" => "error", "mensaje" => "Error al preparar la consulta: " . $conn->error];
    }
    
    $stmt->bind_param("ii", $puntosSumar, $idUsuario);

    if ($stmt->execute()) {
        if ($stmt->affected_rows === 0) {
             // Esto ocurre si el ID del usuario no existe
             $stmt->close();
             return ["status" => "error", "mensaje" => "No se encontró el usuario con ID: {$idUsuario}."];
        }
        $stmt->close();
        return ["status" => "ok", "mensaje" => "Se han sumado {$puntosSumar} puntos al usuario {$idUsuario}."];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ["status" => "error", "mensaje" => "Error al sumar puntos: " . $error];
    }
}
?>