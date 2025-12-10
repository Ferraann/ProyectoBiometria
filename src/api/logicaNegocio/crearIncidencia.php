<?php
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
        $sql = "INSERT INTO incidencias (id_user, titulo, descripcion, estado_id, sensor_id) 
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
        $sql = "INSERT INTO incidencias (id_user, titulo, descripcion, estado_id, sensor_id) 
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
