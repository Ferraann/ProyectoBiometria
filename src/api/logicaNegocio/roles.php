<?php
// ------------------------------------------------------------------
// Fichero: roles.php
// Autor: Manuel
// Fecha: 5/12/2025
// ------------------------------------------------------------------
// Descripción:
//  Funciones para validar roles de usuario (técnico, administrador)
// ------------------------------------------------------------------

/* Devuelve true si el usuario está en la tabla técnicos */
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
?>