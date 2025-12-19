<?php
/**
 * @file obtenerSensoresXIdUsuario.php
 * @brief Funciones de gestión de activos y vinculación de hardware.
 * @details Contiene la lógica necesaria para consultar la disponibilidad y propiedad 
 * de los dispositivos electrónicos asignados a los usuarios del sistema.
 * @author Manuel
 * @date 11/12/2025
 */

/**
 * @brief Recupera los sensores vinculados activamente a un usuario.
 * @details Realiza una intersección entre el catálogo de sensores y la tabla de asignaciones
 * para identificar aquellos dispositivos donde el usuario mantiene el control vigente.
 * @param mysqli $conn Instancia de conexión activa a la base de datos.
 * @param int $usuarioId Identificador único del usuario propietario.
 * @return array Colección de sensores (id, mac, nombre, modelo). Devuelve array vacío en caso de error o sin resultados.
 */
function obtenerSensoresDeUsuario($conn, int $usuarioId): array
{
    // ----------------------------------------------------------------------------------------
    // 1. CONSULTA DE PROPIEDAD DE HARDWARE
    // ----------------------------------------------------------------------------------------

    /** @section ConsultaRelacional 
     * Unión de la tabla maestra 'sensor' con la tabla de relación 'usuario_sensor'.
     */
    /* SQL:
     * - s.mac, s.nombre, s.modelo: Recupera la identidad y especificaciones del dispositivo.
     * - JOIN usuario_sensor us: Cruza con la tabla de asignación.
     * - us.actual = 1: Filtro crítico que garantiza que el sensor pertenece al usuario actualmente.
     * - ORDER BY s.nombre: Facilita la visualización alfabética en el front-end.
     */
    $sql = "SELECT s.id,
                   s.mac,
                   s.nombre,
                   s.modelo
            FROM sensor s
            JOIN usuario_sensor us ON us.sensor_id = s.id
            WHERE us.usuario_id = ?
              AND us.actual = 1
            ORDER BY s.nombre, s.mac";

    $stmt = $conn->prepare($sql);
    
    /** @note Si la preparación falla (ej. tabla inexistente), se retorna un contenedor vacío para evitar errores de ejecución. */
    if (!$stmt) {
        return [];
    }

    // ----------------------------------------------------------------------------------------
    // 2. EJECUCIÓN Y PROCESAMIENTO
    // ----------------------------------------------------------------------------------------

    $stmt->bind_param('i', $usuarioId);
    $stmt->execute();
    
    /** @var mysqli_result $res Puntero al conjunto de resultados. */
    $res = $stmt->get_result();

    $sensores = [];
    
    /** @section MapeoAsociativo Extracción de filas del buffer de la base de datos. */
    while ($fila = $res->fetch_assoc()) {
        $sensores[] = $fila;
    }

    $stmt->close();
    return $sensores;
}