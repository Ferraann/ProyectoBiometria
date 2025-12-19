<?php
/**
 * @file actualizarUsuario.php
 * @brief Lógica de negocio para la actualización dinámica de registros de usuario.
 * @details Procesa actualizaciones selectivas permitiendo modificar solo los campos enviados en la petición.
 * Implementa construcción dinámica de SQL y vinculación segura de parámetros (Prepared Statements).
 * @author Manuel
 * @date 05/12/2025
 */

/**
 * @brief Actualiza selectivamente los campos de un usuario en la base de datos.
 * * @param mysqli $conn Instancia de conexión activa a la base de datos.
 * @param array $data Diccionario con los datos a actualizar (debe incluir obligatoriamente 'id').
 * * @return array {
 * @var string status Estado de la operación ('ok' o 'error').
 * @var string message Mensaje descriptivo del resultado.
 * }
 * * @note La función filtra automáticamente los campos no permitidos para garantizar la integridad de la DB.
 */
function actualizarUsuario($conn, $data)
{
    // ----------------------------------------------------------------------------------------
    // 1. VALIDACIÓN DE IDENTIFICADOR
    // ----------------------------------------------------------------------------------------

    /** @section ValidacionID Comprobación de existencia del ID de usuario. */
    if (empty($data['id'])) {
        return ["status" => "error", "message" => "Falta el id del usuario."];
    }
    
    /** @var int $id Identificador del usuario convertido a entero por seguridad. */
    $id = (int)$data['id'];

    // ----------------------------------------------------------------------------------------
    // 2. CONSTRUCCIÓN DINÁMICA DE LA CONSULTA
    // ----------------------------------------------------------------------------------------

    /**
     * @section ConstruccionSQL
     * Genera la cláusula SET basándose exclusivamente en los campos permitidos y presentes en $data.
     */
    $allowed = ['nombre', 'apellidos', 'gmail', 'password', 'activo'];
    $setParts = [];
    $types    = '';
    $values   = [];

    foreach ($allowed as $field) {
        if (!isset($data[$field])) {
            continue;
        }

        /** @note El hashing de contraseña se asume realizado previamente en el controlador de nivel superior. */
        $setParts[] = "$field = ?";
        
        // Asignación de tipos para bind_param: 'i' para activo (int), 's' para el resto (string)
        $types      .= in_array($field, ['activo'], true) ? 'i' : 's';
        $values[]    = $data[$field];
    }

    if (!$setParts) {
        return ["status" => "error", "message" => "No hay nada que actualizar."];
    }

    // ----------------------------------------------------------------------------------------
    // 3. PREPARACIÓN Y EJECUCIÓN
    // ----------------------------------------------------------------------------------------

    /**
     * @section EjecucionSQL
     * Implementa el cierre de la consulta con la cláusula WHERE y ejecuta el statement.
     */
    $types .= 'i';
    $values[] = $id;

    /** @var string $sql Sentencia SQL construida dinámicamente. */
    $sql = "UPDATE usuario SET " . implode(', ', $setParts) . " WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        return ["status" => "error", "message" => "Error preparando consulta: " . $conn->error];
    }

    /** @note Uso de splat operator (...) para pasar el array de valores dinámicos a bind_param. */
    $stmt->bind_param($types, ...$values);

    if ($stmt->execute()) {
        return ["status" => "ok", "message" => "Usuario actualizado correctamente."];
    } else {
        return ["status" => "error", "message" => "Error al actualizar usuario: " . $stmt->error];
    }
}
?>