<?php
// ------------------------------------------------------------------
// Fichero: distancias.php
// Autor: Manuel
// Fecha: 5/12/2025
// ------------------------------------------------------------------
// Descripción:
//  Aquí se definen las funciones para manejar las distancias de los usuarios.
// ------------------------------------------------------------------


// Guardar distancia caminada hoy para un usuario
function guardarDistanciaHoy($conn, $data)
{
    if (empty($data['usuario_id']) || !isset($data['distancia'])) {
        return ["status" => "error", "mensaje" => "Faltan parámetros: usuario_id o distancia."];
    }

    $usuario_id = (int)$data['usuario_id'];
    $distancia = (float)$data['distancia'];
    $hoy = date("Y-m-d");

    // ¿Ya hay registro hoy?
    $stmt = $conn->prepare("SELECT id, distancia FROM distancias_diarias 
                            WHERE usuario_id = ? AND fecha = ?");
    $stmt->bind_param("is", $usuario_id, $hoy);
    $stmt->execute();
    $result = $stmt->get_result();

    // Si existe → sumar distancia
    if ($row = $result->fetch_assoc()) {
        $nuevaDistancia = $row['distancia'] + $distancia;

        $stmt2 = $conn->prepare("UPDATE distancias_diarias 
                                SET distancia = ? 
                                WHERE id = ?");
        $stmt2->bind_param("di", $nuevaDistancia, $row['id']);
        $stmt2->execute();
        $stmt2->close();

        return [
            "status" => "ok",
            "mensaje" => "Distancia actualizada.",
            "distancia_total" => $nuevaDistancia
        ];
    }

    // Si NO existía → crear registro
    $stmt2 = $conn->prepare("INSERT INTO distancias_diarias (usuario_id, fecha, distancia) 
                             VALUES (?, ?, ?)");
    $stmt2->bind_param("isd", $usuario_id, $hoy, $distancia);
    $stmt2->execute();
    $stmt2->close();

    return [
        "status" => "ok",
        "mensaje" => "Distancia guardada correctamente.",
        "distancia_total" => $distancia
    ];
}

// Obtener historial de distancias para un usuario
function getHistorialDistancias($conn, $data)
{
    if (empty($data['usuario_id'])) {
        return ["status" => "error", "mensaje" => "Falta parámetro: usuario_id."];
    }

    $usuario_id = (int)$data['usuario_id'];

    $stmt = $conn->prepare("SELECT fecha, distancia 
                            FROM distancias_diarias 
                            WHERE usuario_id = ?
                            ORDER BY fecha DESC");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $historial = [];

    while ($row = $result->fetch_assoc()) {
        $historial[] = [
            "fecha" => $row['fecha'],
            "distancia" => (float)$row['distancia']
        ];
    }

    return [
        "status" => "ok",
        "historial" => $historial
    ];
}

// Obtener distancia caminada en una fecha específica para un usuario
function getDistanciaFecha($conn, $data)
{
    if (empty($data['usuario_id']) || empty($data['fecha'])) {
        return ["status" => "error", "mensaje" => "Faltan parámetros: usuario_id o fecha."];
    }

    $usuario_id = (int)$data['usuario_id'];
    $fecha = $data['fecha']; // formato: YYYY-MM-DD

    $stmt = $conn->prepare("SELECT distancia FROM distancias_diarias 
                            WHERE usuario_id = ? AND fecha = ?");
    $stmt->bind_param("is", $usuario_id, $fecha);
    $stmt->execute();

    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return ["status" => "ok", "distancia" => (float)$row['distancia']];
    }

    return ["status" => "ok", "distancia" => 0]; // Ese día no caminó nada
}
?>