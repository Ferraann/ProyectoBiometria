<?php
/**
 * @file obtenerFotoPerfil.php
 * @brief Recuperación de la imagen de perfil de un usuario.
 * @details Extrae el objeto binario (BLOB) de la tabla de fotos de perfil y lo 
 * codifica en Base64 para que pueda ser renderizado directamente por el navegador.
 * @author Manuel
 * @date 07/12/2025
 */

/**
 * @brief Obtiene la foto de perfil asociada a un usuario específico.
 * @param mysqli $conn Instancia de conexión activa a la base de datos.
 * @param int $usuario_id Identificador único del usuario.
 * @return array {
 * @var string status 'ok'.
 * @var array fotos Lista de objetos conteniendo la imagen codificada en Base64.
 * }
 */
function obtenerFotoPerfil($conn, $usuario_id) {
    // ----------------------------------------------------------------------------------------
    // 1. PREPARACIÓN DE LA CONSULTA
    // ----------------------------------------------------------------------------------------

    /** @section ConsultaFotoPerfil Recuperación del recurso binario. */
    /* SQL:
     * Selecciona la columna 'foto' (BLOB) filtrando por el ID de usuario.
     * Se espera un único registro por usuario según la lógica del sistema.
     */
    $sql = "SELECT foto FROM fotos_perfil_usuario WHERE usuario_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    
    /** @var mysqli_result $res Conjunto de resultados de la base de datos. */
    $res = $stmt->get_result();
    $fotos = [];

    // ----------------------------------------------------------------------------------------
    // 2. CODIFICACIÓN Y ESTRUCTURACIÓN
    // ----------------------------------------------------------------------------------------

    /** @section ProcesamientoBinario Conversión a formato de transporte web. */
    while ($row = $res->fetch_assoc()) {
        /** * @note La codificación Base64 permite incrustar la imagen directamente 
         * en el flujo JSON sin romper la codificación de caracteres.
         */
        $fotos[] = [
            "foto" => base64_encode($row['foto'])
        ];
    }

    $stmt->close();
    
    return [
        "status" => "ok", 
        "fotos" => $fotos
    ];
}
?>