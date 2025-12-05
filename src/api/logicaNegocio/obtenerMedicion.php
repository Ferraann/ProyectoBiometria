<?php
// ------------------------------------------------------------------
// Fichero: obtenerMedicion.php
// Autor: Manuel
// Fecha: 5/12/2025
// ------------------------------------------------------------------
// Descripción:
//  Este archivo se encarga de obtener las mediciones desde la base de datos
//  y devolverlas en formato JSON para su uso en la API.
// ------------------------------------------------------------------

function obtenerMediciones($conn)
{
    $sql = "SELECT m.id, tm.medida, tm.unidad, m.valor, m.hora, m.localizacion, s.mac
            FROM medicion m
            INNER JOIN tipo_medicion tm ON m.tipo_medicion_id = tm.id
            INNER JOIN sensor s ON m.sensor_id = s.id
            ORDER BY m.hora DESC";

    $result = $conn->query($sql);
    $datos = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $datos[] = $row;
        }
    }

    return $datos;
}
?>