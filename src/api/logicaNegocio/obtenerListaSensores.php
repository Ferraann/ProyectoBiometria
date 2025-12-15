<?php
// ------------------------------------------------------------------
// Fichero: obtenerListaSensores.php
// Autor: Manuel
// Fecha: 11/12/2025
// ------------------------------------------------------------------
// Descripción:
//  Función de API (Lógica de Negocio) para recuperar la lista de sensores
//  que están actualmente asignados y activos para un usuario específico.
//  
// Funcionalidad:
//  - Función 'obtenerListaSensores' que requiere el `usuario_id` como parámetro.
//  - Ejecuta una consulta JOIN entre las tablas `sensor` (s) y la tabla intermedia 
//    `usuario_sensor` (us).
//  - Filtra la lista para incluir solo las asignaciones donde `us.usuario_id` coincide
//    con el ID proporcionado.
//  - **Punto clave:** Filtra la relación con la condición `us.actual = 1`, asegurando 
//    que solo se devuelvan los sensores que el usuario está gestionando en este momento.
//  - Devuelve un array de objetos JSON con el `id` y la `mac` de cada sensor asociado.
// ------------------------------------------------------------------

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