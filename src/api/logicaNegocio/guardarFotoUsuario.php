<?php
/**
 * @file guardarFotoPerfil.php
 * @brief Lógica de negocio para la gestión de avatares de usuario.
 * @details Procesa la recepción de imágenes en Base64, realiza la conversión a binario 
 * y gestiona de forma inteligente la creación o sustitución del recurso (Lógica UPSERT).
 * @author Manuel
 * @date 11/12/2025
 */

/**
 * @brief Almacena o actualiza la fotografía de perfil de un usuario.
 * @param mysqli $conn Instancia de conexión a la base de datos.
 * @param array $data {
 * @var int $usuario_id ID del propietario de la cuenta.
 * @var string $foto Cadena en formato Base64 con los datos de la imagen.
 * }
 * @return array {
 * @var string status 'ok' o 'error'.
 * @var string mensaje Descripción detallada del resultado.
 * }
 */
function guardarFotoPerfil($conn, $data)
{
    // ----------------------------------------------------------------------------------------
    // 1. VALIDACIÓN Y PREPROCESAMIENTO
    // ----------------------------------------------------------------------------------------

    /** @section ValidacionEntrada Comprobación de integridad de parámetros obligatorios. */
    if (empty($data['usuario_id']) || empty($data['foto'])) {
        return ["status" => "error", "mensaje" => "Faltan parámetros: usuario_id o foto."];
    }

    /** @var int $usuario_id */
    $usuario_id = (int)$data['usuario_id'];
    /** @var string $base64 */
    $base64 = $data['foto'];

    /** * @section ConversionBinaria
     * @note Se elimina el encabezado MIME (ej: data:image/jpeg;base64,) para extraer el binario puro.
     */
    $base64 = preg_replace('/^data:image\/\w+;base64,/', '', $base64);
    /** @var string|bool $blob Contenido binario decodificado. */
    $blob = base64_decode($base64, true);

    if ($blob === false) {
        return ["status" => "error", "mensaje" => "La cadena Base64 no es válida."];
    }

    // ----------------------------------------------------------------------------------------
    // 2. COMPROBACIÓN DE ESTADO (UPSERT)
    // ----------------------------------------------------------------------------------------

    /** * @section VerificacionPrevia
     * Determina si el usuario ya posee un registro de imagen para decidir entre UPDATE o INSERT.
     */
    /* SQL:
     * Consulta simple de existencia para minimizar la transferencia de datos.
     */
    $check = $conn->prepare("SELECT id FROM fotos_perfil_usuario WHERE usuario_id = ?");
    $check->bind_param("i", $usuario_id);
    $check->execute();
    $check->store_result(); 
    
    /** @var bool $existe Verdadero si el usuario ya tiene una foto en la tabla. */
    $existe = $check->num_rows > 0;
    $check->close();

    // ----------------------------------------------------------------------------------------
    // 3. PERSISTENCIA BINARIA (LONG DATA)
    // ----------------------------------------------------------------------------------------

    if ($existe) {
        /** @section UpdatePerfil Sustitución de imagen existente. */
        /* SQL:
         * Reemplaza el contenido BLOB del usuario específico.
         */
        $stmt = $conn->prepare("UPDATE fotos_perfil_usuario SET foto = ? WHERE usuario_id = ?");
        if (!$stmt) return ["status" => "error", "mensaje" => "Error en la preparación del UPDATE."];

        /** @note Uso de send_long_data para el parámetro 0 (foto). */
        $stmt->bind_param("bi", $null, $usuario_id);
        $stmt->send_long_data(0, $blob);

        if (!$stmt->execute()) {
            $stmt->close();
            return ["status" => "error", "mensaje" => "Error al actualizar la foto: " . $conn->error];
        }

        $stmt->close();
        return ["status" => "ok", "mensaje" => "Foto actualizada correctamente."];
    } 
    else {
        /** @section InsertPerfil Registro inicial de imagen. */
        /* SQL:
         * Crea la primera vinculación entre el usuario y su avatar binario.
         */
        $stmt = $conn->prepare("INSERT INTO fotos_perfil_usuario (usuario_id, foto) VALUES (?, ?)");
        if (!$stmt) return ["status" => "error", "mensaje" => "Error en la preparación del INSERT."];

        /** @note Uso de send_long_data para el parámetro 1 (foto). */
        $stmt->bind_param("ib", $usuario_id, $null);
        $stmt->send_long_data(1, $blob);

        if (!$stmt->execute()) {
            $stmt->close();
            return ["status" => "error", "mensaje" => "Error al guardar la foto: " . $conn->error];
        }

        $stmt->close();
        return ["status" => "ok", "mensaje" => "Foto guardada correctamente."];
    }
}
?>