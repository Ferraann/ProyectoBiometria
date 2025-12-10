<?php
// ------------------------------------------------------------------
// Fichero: crearIncidencia.php
// Autor: Manuel
// Fecha: 5/12/2025
// ------------------------------------------------------------------
// Descripción:
//  Función para crear una nueva incidencia en el sistema.
//  Valida los parámetros, obtiene el estado inicial "Abierta" 
//  de forma dinámica e inserta el registro en la base de datos,
//  incluyendo opcionalmente el sensor al que pertenece.
// ------------------------------------------------------------------

function crearIncidencia($conn, $data)
{
    // Validar parámetros obligatorios
    if (!isset($data['id_user'], $data['titulo'], $data['descripcion'])) {
        return ["status" => "error", "mensaje" => "Faltan parámetros."];
    }

    // Validar/normalizar sensor_id (si no se envía, quedará en NULL)
    $sensor_id = isset($data['sensor_id']) && $data['sensor_id'] !== "" 
                 ? $data['sensor_id'] 
                 : null;

    // Obtener dinámicamente el estado "Abierta"
    $sqlEstado = "SELECT id FROM estado_incidencia WHERE nombre = 'Abierta' LIMIT 1";
    $resEstado = $conn->query($sqlEstado);
    $estadoRow = $resEstado ? $resEstado->fetch_assoc() : null;
    $estadoInicial = $estadoRow ? $estadoRow['id'] : 1;

    // Insertar la incidencia (con o sin sensor_id)
    $sql = "INSERT INTO incidencias (id_user, titulo, descripcion, estado_id, sensor_id) 
            VALUES (?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    // Tipos: i = int, s = string, i = int (NULL se admite como NULL)
    $stmt->bind_param("issii", 
        $data['id_user'], 
        $data['titulo'], 
        $data['descripcion'], 
        $estadoInicial, 
        $sensor_id
    );

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