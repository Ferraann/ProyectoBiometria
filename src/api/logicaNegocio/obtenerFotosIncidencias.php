<?php
// ------------------------------------------------------------------
// Fichero: obtenerFotosIncidencias.php
// Autor: Manuel
// Fecha: 5/12/2025
// ------------------------------------------------------------------
// Descripción:
//  Función para obtener las fotos asociadas a una incidencia.
//  Devuelve las fotos en formato base64 codificado.
// ------------------------------------------------------------------

function obtenerFotosIncidencia($conn, $incidencia_id) {
    $sql = "SELECT foto FROM fotos_incidencia WHERE incidencia_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $incidencia_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $fotos = [];
    while ($row = $res->fetch_assoc()) {
        $fotos[] = ["foto" => base64_encode($row['foto'])];
    }
    return ["status" => "ok", "fotos" => $fotos];
}