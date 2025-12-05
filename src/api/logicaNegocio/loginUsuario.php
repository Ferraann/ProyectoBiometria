<?php
// ------------------------------------------------------------------
// Fichero: loginUsuario.php
// Autor: Manuel
// Fecha: 5/12/2024
// Descripción: 
// Autentica un usuario verificando sus credenciales
// (correo y contraseña) contra la base de datos,
// valida que la cuenta esté activa y establece la
// sesión si el login es exitoso.
// ------------------------------------------------------------------

function loginUsuario($conn, $gmail, $password)
{
    // Inicia la sesión solo si no está ya iniciada
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    /* 1. Obtener datos del usuario */
    $stmt = $conn->prepare(
        "SELECT id, nombre, apellidos, gmail, password, activo
         FROM usuario
         WHERE gmail = ?"
    );
    
    if (!$stmt) {
        return ["status" => "error", "mensaje" => "Error en la base de datos"];
    }
    
    $stmt->bind_param("s", $gmail);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        return ["status" => "error", "mensaje" => "Usuario no encontrado"];
    }
    
    $user = $res->fetch_assoc();
    $stmt->close();

    /* 2. Verificar contraseña */
    if (!password_verify($password, $user['password'])) {
        return ["status" => "error", "mensaje" => "Contraseña incorrecta"];
    }

    /* 3. Comprobar si la cuenta está activa */
    if (!$user['activo']) {
        return ["status" => "error", "mensaje" => "Cuenta no activada"];
    }

    /* 4. Establecer datos de sesión (no guardar contraseña en texto plano) */
    $_SESSION['usuario_id'] = $user['id'];
    $_SESSION['usuario_nombre'] = $user['nombre'];
    $_SESSION['usuario_apellidos'] = $user['apellidos'];
    $_SESSION['usuario_correo'] = $user['gmail'];

    unset($user['password']);
    return ["status" => "ok", "usuario" => $user];
}