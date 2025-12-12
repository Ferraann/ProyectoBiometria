<?php
// ------------------------------------------------------------------
// Fichero: activarUsuario.php
// Autor: Manuel
// Fecha: 5/12/2025
// ------------------------------------------------------------------
// Descripción:
//  Define la lógica de negocio para la activación de cuentas de usuario
//  mediante un token de seguridad enviado por correo electrónico.
//  
// Funcionalidad:
//  - Función 'activarUsuario' que recibe la conexión a DB y un token único.
//  - Realiza la validación del token buscando su existencia en la tabla 'usuario'.
//  - Comprueba la caducidad del token ('token_expira') contra la hora actual del sistema.
//  - Si es válido y no ha expirado, actualiza el campo 'activo' a 1.
//  - Limpia los campos de token de seguridad y de expiración tras la activación exitosa.
// ------------------------------------------------------------------

function activarUsuario($conn, $token)
{
    // 1. Buscar el usuario por token
    $sql = "SELECT id, token_expira FROM usuario WHERE token = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        return ["status" => "error", "mensaje" => "Token inválido o ya usado."];
    }

    $usr = $res->fetch_assoc();

    // 2. Comprobar si el token ha expirado
    if (strtotime($usr['token_expira']) < time()) {
        return ["status" => "error", "mensaje" => "El enlace ha expirado. Solicita un nuevo correo de activación."];
    }

    // 3. Activar usuario
    $sql2 = "UPDATE usuario SET activo = 1, token = NULL, token_expira = NULL WHERE id = ?";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param("i", $usr['id']);
    $stmt2->execute();

    if ($stmt2->affected_rows > 0) {
        return ["status" => "ok", "mensaje" => "Cuenta activada correctamente."];
    } else {
        return ["status" => "error", "mensaje" => "No se pudo activar la cuenta."];
    }
}
?>