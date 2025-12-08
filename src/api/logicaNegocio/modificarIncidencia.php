<?php
// ------------------------------------------------------------------
// Fichero: modificarIncidencia.php
// Autor: Manuel
// Fecha: 30/10/2025
// ------------------------------------------------------------------
// Descripción:
// Este archivo contiene la lógica de negocio para modificar una incidencia en el sistema de biometría.
// Permite actualizar los detalles de una incidencia existente, asegurando que los cambios se reflejen
// correctamente en la base de datos. Se espera que este script sea invocado a través de una solicitud
// HTTP, y que reciba los parámetros necesarios para realizar la modificación.
// ------------------------------------------------------------------

function actualizarEstadoIncidencia($conn, $datos) {
    $incidenciaId = intval($datos['incidencia_id'] ?? 0);
    $estadoId     = intval($datos['estado_id'] ?? 0);

    $stmt = $conn->prepare("UPDATE incidencias SET estado_id = ? WHERE id = ?");
    $ok   = $stmt->execute([$estadoId, $incidenciaId]);

    return $ok ? ["status" => "ok"] : ["status" => "error", "mensaje" => "No se pudo actualizar"];
}

function asignarTecnicoIncidencia($conn, $datos) {
    $incidenciaId = intval($datos['incidencia_id'] ?? 0);
    $tecnicoId    = intval($datos['tecnico_id'] ?? 0);

    $stmt = $conn->prepare("UPDATE incidencias SET id_tecnico = ? WHERE id = ?");
    $ok   = $stmt->execute([$tecnicoId, $incidenciaId]);

    return $ok ? ["status" => "ok"] : ["status" => "error", "mensaje" => "No se pudo asignar"];
}
?>