<?php
// ------------------------------------------------------------------
// Fichero: activarUsuario.php
// Autor: Manuel
// Fecha: 5/12/2025
// ------------------------------------------------------------------
// Descripción:
//  Aquí se definen todas las funciones lógicas que maneja la API.
//  Cada función se encarga de interactuar con la base de datos y
//  devolver los resultados al archivo index.php.
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