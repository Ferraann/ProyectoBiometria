<?php
// ------------------------------------------------------------------
// Fichero: (dentro de logicaNegocio.php)
// Autor: Manuel
// Fecha: 11/12/2025
// ------------------------------------------------------------------
// Descripción:
//  Función que devuelve todos los sensores actualmente asignados
//  a un usuario (usuario_sensor.actual = 1) en formato array JSON.
// ------------------------------------------------------------------
function obtenerSensoresDeUsuario($conn, int $usuarioId): array
{
    $sql = "SELECT s.id,
                   s.mac,
                   s.nombre,
                   s.modelo
            FROM sensor s
            JOIN usuario_sensor us ON us.sensor_id = s.id
            WHERE us.usuario_id = ?
              AND us.actual = 1
            ORDER BY s.nombre, s.mac";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }
    $stmt->bind_param('i', $usuarioId);
    $stmt->execute();
    $res = $stmt->get_result();

    $sensores = [];
    while ($fila = $res->fetch_assoc()) {
        $sensores[] = $fila;
    }
    $stmt->close();
    return $sensores;
}