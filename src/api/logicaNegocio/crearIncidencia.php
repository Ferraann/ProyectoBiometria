<?php
// ------------------------------------------------------------------
// Fichero: crearIncidencia.php
// Autor: Manuel
// Fecha: 11/12/2025
// ------------------------------------------------------------------
// Descripción:
//  Función de API (Lógica de Negocio) para registrar una nueva incidencia
//  generada por un usuario en el sistema.
//  
// Funcionalidad:
//  - Función 'crearIncidencia' que valida la presencia de campos obligatorios (id_user, titulo, descripcion).
//  - Normaliza el parámetro `sensor_id`, permitiendo que sea NULL si no se proporciona.
//  - Consulta la base de datos para obtener el ID del estado predefinido 'Abierta' (o utiliza un fallback).
//  - Construye dinámicamente la consulta INSERT utilizando consultas preparadas, adaptándose para manejar la diferencia entre asignar un valor entero (`?`) o `NULL` al campo `id_sensor`.
//  - Tras la ejecución exitosa, devuelve el ID de la nueva incidencia creada.
// ------------------------------------------------------------------

function crearIncidencia($conn, $data)
{
    // Validar parámetros obligatorios
    if (!isset($data['id_user'], $data['titulo'], $data['descripcion'])) {
        return ["status" => "error", "mensaje" => "Faltan parámetros."];
    }

    // Normalizar sensor_id: si no se envía, será NULL
    $sensor_id = isset($data['sensor_id']) && $data['sensor_id'] !== "" 
                 ? (int)$data['sensor_id'] 
                 : null;

    // Obtener dinámicamente el estado "Abierta"
    $sqlEstado = "SELECT id FROM estado_incidencia WHERE nombre = 'Abierta' LIMIT 1";
    $resEstado = $conn->query($sqlEstado);
    $estadoRow = $resEstado ? $resEstado->fetch_assoc() : null;
    $estadoInicial = $estadoRow ? $estadoRow['id'] : 1;

    // Insertar la incidencia
    if ($sensor_id !== null) {
        // Si hay sensor, usamos bind_param normal
        $sql = "INSERT INTO incidencias (id_user, titulo, descripcion, estado_id, id_sensor) 
        VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "issii",
            $data['id_user'],
            $data['titulo'],
            $data['descripcion'],
            $estadoInicial,
            $sensor_id
        );
    } else {
        // Si no hay sensor, ponemos NULL directamente en SQL
        $sql = "INSERT INTO incidencias (id_user, titulo, descripcion, estado_id, id_sensor) 
        VALUES (?, ?, ?, ?, NULL)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "issi",
            $data['id_user'],
            $data['titulo'],
            $data['descripcion'],
            $estadoInicial
        );
    }

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
