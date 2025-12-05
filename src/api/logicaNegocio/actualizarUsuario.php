<?php
// ------------------------------------------------------------------
// Fichero: actualizarUsuario.php
// Autor: Manuel
// Fecha: 5/12/2025
// ------------------------------------------------------------------
// Descripción:
//  Función para actualizar datos de un usuario en la base de datos.
//  Permite actualizar nombre, apellidos, gmail, password y estado activo.
// ------------------------------------------------------------------

function actualizarUsuario($conn, $data)
{
    /* 1. Comprobamos ID obligatorio */
    if (empty($data['id'])) {
        return ["status" => "error", "message" => "Falta el id del usuario."];
    }
    $id = (int)$data['id'];

    /* 2. Campos actualizables (solo los que llegan) */
    $allowed = ['nombre', 'apellidos', 'gmail', 'password', 'activo'];
    $setParts = [];
    $types    = '';
    $values   = [];

    foreach ($allowed as $field) {
        if (!isset($data[$field])) {
            continue;
        }
        /* hashear password si viene */
        if ($field === 'password') {
            $data[$field] = password_hash($data[$field], PASSWORD_DEFAULT);
        }
        $setParts[] = "$field = ?";
        $types      .= in_array($field, ['activo'], true) ? 'i' : 's';
        $values[]    = $data[$field];
    }

    if (!$setParts) {
        return ["status" => "error", "message" => "No hay nada que actualizar."];
    }

    /* 3. WHERE id = ? */
    $types .= 'i';
    $values[] = $id;

    $sql = "UPDATE usuario SET " . implode(', ', $setParts) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return ["status" => "error", "message" => "Error preparando consulta: " . $conn->error];
    }

    $stmt->bind_param($types, ...$values);

    // // -------------------------------
    // //   OBTENER DATOS ACTUALIZADOS
    // // -------------------------------
    // $sqlUser = "SELECT id, nombre, apellidos, gmail AS correo FROM usuario WHERE id = ?";
    // $stmtUser = $conn->prepare($sqlUser);
    // $stmtUser->bind_param("i", $id);
    // $stmtUser->execute();
    // $resUser = $stmtUser->get_result();
    // $usuarioActualizado = $resUser->fetch_assoc();

    // return [
    //     "status" => "ok",
    //     "mensaje" => "Usuario actualizado correctamente",
    //     "usuario" => $usuarioActualizado
    // ];

    if ($stmt->execute()) {
        return ["status" => "ok", "message" => "Usuario actualizado correctamente."];
        //aqui podriamos devolver los datos actualizados.
    } else {
        return ["status" => "error", "message" => "Error al actualizar usuario: " . $stmt->error];
    }
}