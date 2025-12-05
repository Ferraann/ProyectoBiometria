<?php
// ------------------------------------------------------------------
// Fichero: crearIncidencia.php
// Autor: Manuel
// Fecha: 5/12/2025
// ------------------------------------------------------------------
// Descripción:
//  Función para crear una nueva incidencia en el sistema.
//  Valida los parámetros, obtiene el estado inicial "Abierta" 
//  de forma dinámica e inserta el registro en la base de datos.
// ------------------------------------------------------------------

function crearIncidencia($conn, $data)
{
    if (!isset($data['id_user'], $data['titulo'], $data['descripcion'])) {
        return ["status" => "error", "mensaje" => "Faltan parámetros."];
    }

    // Buscar dinámicamente el estado "Abierta"
    $sqlEstado = "SELECT id FROM estado_incidencia WHERE nombre = 'Abierta' LIMIT 1";
    $resEstado = $conn->query($sqlEstado);
    $estadoRow = $resEstado ? $resEstado->fetch_assoc() : null;
    $estadoInicial = $estadoRow ? $estadoRow['id'] : 1;

    // Insertar la nueva incidencia
    $sql = "INSERT INTO incidencias (id_user, titulo, descripcion, estado_id) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issi", $data['id_user'], $data['titulo'], $data['descripcion'], $estadoInicial);

    if ($stmt->execute()) {
        return [
            "status" => "ok",
            "id_incidencia" => $conn->insert_id,
            "mensaje" => "Incidencia creada correctamente con estado inicial 'Abierta'."
        ];
    } else {
        return [
            "status" => "error",
            "mensaje" => "Error al registrar incidencia: " . $conn->error
        ];
    }
}
?>