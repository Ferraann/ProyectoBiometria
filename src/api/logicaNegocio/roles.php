<?php
// ------------------------------------------------------------------
// Fichero: roles.php  (puedes meterlo en cualquier .php que ya uses)
// ------------------------------------------------------------------

/* Devuelve true si el usuario está en la tabla técnicos */
function esTecnico(PDO $conn, int $usuarioId): bool
{
    $stmt = $conn->prepare("SELECT 1 FROM tecnicos WHERE usuario_id = :id LIMIT 1");
    $stmt->execute([':id' => $usuarioId]);
    return (bool) $stmt->fetchColumn();
}

/* Devuelve true si el usuario está en la tabla administradores */
function esAdministrador(PDO $conn, int $usuarioId): bool
{
    $stmt = $conn->prepare("SELECT 1 FROM administradores WHERE usuario_id = :id LIMIT 1");
    $stmt->execute([':id' => $usuarioId]);
    return (bool) $stmt->fetchColumn();
}
?>