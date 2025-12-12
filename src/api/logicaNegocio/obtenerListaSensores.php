<?php
/**
 * FUNCIÓN 17: Obtener una lista de sensores asociados al usuario
 */
function obtenerListaSensores($conn, $usuario_id){

    if (empty($usuario_id)) {
        return ["status" => "error", "mensaje" => "Falta usuario_id."];
    }

    $sql = "SELECT s.id, s.mac 
            FROM sensor s 
            INNER JOIN usuario_sensor us 
                ON s.id = us.sensor_id
            WHERE us.usuario_id = ? 
              AND us.actual = 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $sensores = [];

    while ($row = $result->fetch_assoc()) {
        $sensores[] = [
            "id"  => $row["id"],
            "mac" => $row["mac"]
        ];
    }

    if (empty($sensores)) {
        return ["status" => "ok", "listaSensores" => [], "mensaje" => "El usuario no tiene sensores asociados."];
    }

    return ["status" => "ok", "listaSensores" => $sensores];
}
?>