<?php
// ------------------------------------------------------------------
// Fichero: roles.php
// Autor: Manuel
// Fecha: 11/12/2025
// ------------------------------------------------------------------
// Descripción:
//  Funciones para VALIDAR y GESTIONAR roles de usuario
// ------------------------------------------------------------------

/* VALIDAR ROLES */
function esTecnico($conn, int $usuarioId): bool
{
    $stmt = $conn->prepare("SELECT 1 FROM tecnicos WHERE usuario_id = ? LIMIT 1");
    $stmt->bind_param("i", $usuarioId);
    $stmt->execute();
    return (bool) $stmt->get_result()->fetch_column();
}

function esAdministrador($conn, int $usuarioId): bool
{
    $stmt = $conn->prepare("SELECT 1 FROM administradores WHERE usuario_id = ? LIMIT 1");
    $stmt->bind_param("i", $usuarioId);
    $stmt->execute();
    return (bool) $stmt->get_result()->fetch_column();
}

/* ASIGNAR / QUITAR TECNICO */
function asignarTecnico($conn, int $usuarioId): array
{
    if (esTecnico($conn, $usuarioId)) {
        return ["status" => "ok", "mensaje" => "Ya era técnico"];
    }
    $stmt = $conn->prepare("INSERT INTO tecnicos (usuario_id) VALUES (?)");
    $stmt->bind_param("i", $usuarioId);
    return $stmt->execute()
        ? ["status" => "ok", "mensaje" => "Técnico asignado"]
        : ["status" => "error", "mensaje" => $conn->error];
}

function quitarTecnico($conn, int $usuarioId): array
{
    $stmt = $conn->prepare("DELETE FROM tecnicos WHERE usuario_id = ?");
    $stmt->bind_param("i", $usuarioId);
    return $stmt->execute()
        ? ["status" => "ok", "mensaje" => "Técnico eliminado"]
        : ["status" => "error", "mensaje" => $conn->error];
}

/* ASIGNAR / QUITAR ADMINISTRADOR */
function asignarAdministrador($conn, int $usuarioId): array
{
    if (esAdministrador($conn, $usuarioId)) {
        return ["status" => "ok", "mensaje" => "Ya era administrador"];
    }
    $stmt = $conn->prepare("INSERT INTO administradores (usuario_id) VALUES (?)");
    $stmt->bind_param("i", $usuarioId);
    return $stmt->execute()
        ? ["status" => "ok", "mensaje" => "Administrador asignado"]
        : ["status" => "error", "mensaje" => $conn->error];
}

function quitarAdministrador($conn, int $usuarioId): array
{
    $stmt = $conn->prepare("DELETE FROM administradores WHERE usuario_id = ?");
    $stmt->bind_param("i", $usuarioId);
    return $stmt->execute()
        ? ["status" => "ok", "mensaje" => "Administrador eliminado"]
        : ["status" => "error", "mensaje" => $conn->error];
}

?>