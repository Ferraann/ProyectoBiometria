<?php
/**
 * @file sumarPuntosXid.php
 * @brief Gestión del sistema de gamificación y recompensas.
 * @details Proporciona la funcionalidad para incrementar el saldo de puntos de un usuario. 
 * Se utiliza principalmente en módulos de incentivos por reciclaje, participación o 
 * resolución de incidencias.
 * @author Manuel
 * @date 11/12/2025
 */

/**
 * @brief Incrementa los puntos acumulados de un usuario específico.
 * @details Realiza una actualización aritmética directamente en el motor SQL para 
 * garantizar la consistencia de los datos (evitando condiciones de carrera).
 * @param mysqli $conn Instancia de conexión activa a la base de datos.
 * @param array $data {
 * @var int $id_usuario Identificador único del usuario beneficiario.
 * @var int $puntos_a_sumar Cantidad positiva de puntos a añadir.
 * }
 * @return array {
 * @var string status 'ok' o 'error'.
 * @var string mensaje Descripción detallada del resultado de la operación.
 * }
 */
function sumarPuntosUsuario($conn, $data)
{
    // ----------------------------------------------------------------------------------------
    // 1. VALIDACIÓN DE PARÁMETROS Y TIPADO
    // ----------------------------------------------------------------------------------------

    /** @section ValidacionPuntos Comprobación de integridad de entrada. */
    if (!isset($data['id_usuario'], $data['puntos_a_sumar'])) {
        return ["status" => "error", "mensaje" => "Faltan parámetros obligatorios: id_usuario o puntos_a_sumar."];
    }

    $idUsuario = (int)$data['id_usuario'];
    $puntosSumar = (int)$data['puntos_a_sumar'];

    if ($idUsuario <= 0 || $puntosSumar <= 0) {
        return ["status" => "error", "mensaje" => "El ID de usuario y los puntos deben ser valores positivos."];
    }

    // ----------------------------------------------------------------------------------------
    // 2. OPERACIÓN ATÓMICA DE ACTUALIZACIÓN
    // ----------------------------------------------------------------------------------------

    /** @section IncrementoSQL Actualización de la columna 'puntos'. */
    /* SQL:
     * Se utiliza 'puntos = puntos + ?' para que el incremento sea gestionado 
     * internamente por la base de datos, asegurando la precisión incluso con múltiples peticiones simultáneas.
     */
    $sql = "UPDATE usuario SET puntos = puntos + ? WHERE id = ?";
    
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        return ["status" => "error", "mensaje" => "Fallo en la preparación de la consulta: " . $conn->error];
    }
    
    $stmt->bind_param("ii", $puntosSumar, $idUsuario);

    // ----------------------------------------------------------------------------------------
    // 3. CONTROL DE EJECUCIÓN Y AFECTACIÓN
    // ----------------------------------------------------------------------------------------

    if ($stmt->execute()) {
        /** @note Se verifica affected_rows para confirmar que el ID existía realmente. */
        if ($stmt->affected_rows === 0) {
             $stmt->close();
             return ["status" => "error", "mensaje" => "No se encontró ningún usuario activo con el ID: {$idUsuario}."];
        }
        $stmt->close();
        return ["status" => "ok", "mensaje" => "Éxito: Se han añadido {$puntosSumar} puntos al usuario {$idUsuario}."];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ["status" => "error", "mensaje" => "Error interno al procesar el incremento: " . $error];
    }
}
?>