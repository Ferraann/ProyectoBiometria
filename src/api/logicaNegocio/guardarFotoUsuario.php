<?php
function guardarFotoPerfil($conn, $data)
{
    // 1. Validaciones mínimas
    if (empty($data['usuario_id']) || empty($data['foto'])) {
        return ["status" => "error", "mensaje" => "Faltan parámetros: usuario_id o foto."];
    }

    $usuario_id = (int)$data['usuario_id'];
    $base64 = $data['foto'];

    // 2. Convertir Base64 -> BLOB (sin cabecera)
    $base64 = preg_replace('/^data:image\/\w+;base64,/', '', $base64);
    $blob = base64_decode($base64, true);

    if ($blob === false) {
        return ["status" => "error", "mensaje" => "La imagen no es válida."];
    }

    // 3. Verificar si el usuario ya tiene foto
    $check = $conn->prepare("SELECT id FROM fotos_perfil_usuario WHERE usuario_id = ?");
    $check->bind_param("i", $usuario_id);
    $check->execute();
    $check->store_result(); // Obtiene número de filas
    $existe = $check->num_rows > 0;
    $check->close();

    // 4. Dependiendo si existe -> UPDATE o INSERT
    if ($existe) {
        // --- UPDATE ---
        $stmt = $conn->prepare("UPDATE fotos_perfil_usuario SET foto = ? WHERE usuario_id = ?");
        if (!$stmt) return ["status" => "error", "mensaje" => "Error en la preparación del UPDATE."];

        $stmt->bind_param("bi", $blob, $usuario_id);
        $stmt->send_long_data(0, $blob);

        if (!$stmt->execute()) {
            $stmt->close();
            return ["status" => "error", "mensaje" => "Error al actualizar la foto."];
        }

        $stmt->close();
        return ["status" => "ok", "mensaje" => "Foto actualizada correctamente."];
    } 
    else {
        // --- INSERT ---
        $stmt = $conn->prepare("INSERT INTO fotos_perfil_usuario (usuario_id, foto) VALUES (?, ?)");
        if (!$stmt) return ["status" => "error", "mensaje" => "Error en la preparación del INSERT."];

        $stmt->bind_param("ib", $usuario_id, $blob);
        $stmt->send_long_data(1, $blob);

        if (!$stmt->execute()) {
            $stmt->close();
            return ["status" => "error", "mensaje" => "Error al guardar la foto."];
        }

        $stmt->close();
        return ["status" => "ok", "mensaje" => "Foto guardada correctamente."];
    }
}
?>
