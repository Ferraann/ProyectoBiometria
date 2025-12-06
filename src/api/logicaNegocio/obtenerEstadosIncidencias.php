<?php
// ------------------------------------------------------------------
// Fichero: obtenerEstadosIncidencias.php
// Autor: Manuel
// Fecha: 30/10/2025
// ------------------------------------------------------------------
// Descripción:
//  Devuelve todos los estados de incidencia en formato JSON.
//  Usa MySQLi y NO imprime errores como texto.
// ------------------------------------------------------------------

function obtenerEstadosIncidencia($conn) {
    $res = $conn->query("SELECT id, nombre FROM estado_incidencia ORDER BY id");
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}
?>