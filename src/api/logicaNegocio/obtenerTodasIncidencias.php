<?php
/**
 * @file obtenerTodasIncidencias.php
 * @brief Generador de informes detallados y listados globales de incidencias.
 * @details Realiza una consulta compleja multitabla para consolidar toda la información 
 * relativa a los tickets de soporte, incluyendo datos del emisor, técnico responsable, 
 * estado actual y detalles del hardware involucrado.
 * @author Manuel
 * @date 5/12/2025
 */

/**
 * @brief Recupera el listado completo de incidencias con sus relaciones resueltas.
 * @details Utiliza múltiples uniones externas (LEFT JOIN) para asegurar que se recuperen 
 * incluso aquellas incidencias que no tienen un técnico o sensor asignado todavía.
 * @param mysqli $conn Instancia de conexión activa a la base de datos.
 * @return array Colección de incidencias con nombres legibles en lugar de IDs numéricos.
 */
function obtenerTodasIncidencias($conn)
{
    // ----------------------------------------------------------------------------------------
    // 1. CONSULTA DE CONSOLIDACIÓN (MULTI-JOIN)
    // ----------------------------------------------------------------------------------------

    /** @section ConsultaMaestraIncidencias 
     * Unión de 5 tablas para transformar claves foráneas en información descriptiva.
     */
    /* SQL:
     * - i.*: Datos base de la incidencia (título, descripción, fechas).
     * - u.nombre: Resuelve el ID del usuario que reporta la incidencia.
     * - COALESCE(tu.nombre, 'Sin asignar'): Si id_tecnico es NULL, devuelve el texto 'Sin asignar'.
     * - e.nombre: Resuelve el estado (ej. 'Abierta', 'Cerrada').
     * - COALESCE(s.nombre, ...): Si el sensor no tiene nombre, genera un alias dinámico usando su ID.
     * - LEFT JOIN: Crucial para no perder registros si falta alguna relación opcional (como el técnico).
     * - ORDER BY fecha_creacion DESC: Muestra primero los problemas más recientes.
     */
    $sql = "
        SELECT 
            i.id,
            i.titulo,
            i.descripcion,
            i.fecha_creacion,
            i.fecha_finalizacion,
            i.id_user,
            u.nombre AS usuario,
            i.id_tecnico,
            COALESCE(tu.nombre, 'Sin asignar') AS tecnico,
            i.estado_id,
            e.nombre AS estado,
            i.id_sensor,
            COALESCE(s.nombre, CONCAT('Sensor #', i.id_sensor)) AS nombre_sensor,
            s.mac AS mac_sensor,
            s.problema AS sensor_con_problema

        FROM incidencias i
        LEFT JOIN usuario u           ON i.id_user = u.id
        LEFT JOIN tecnicos t          ON i.id_tecnico = t.usuario_id
        LEFT JOIN usuario tu          ON t.usuario_id = tu.id
        LEFT JOIN estado_incidencia e ON i.estado_id = e.id
        LEFT JOIN sensor s            ON i.id_sensor = s.id
        ORDER BY i.fecha_creacion DESC
    ";

    /** @var mysqli_result|bool $result */
    $result = $conn->query($sql);
    
    if (!$result) {
        /** @note Si la consulta falla (ej. error de sintaxis o tabla inexistente), retorna un array vacío. */
        return [];
    }
    
    // ----------------------------------------------------------------------------------------
    // 2. PROCESAMIENTO DE FILAS
    // ----------------------------------------------------------------------------------------

    $incidencias = [];
    
    /** @section MapeoAsociativo Conversión de registros a estructura JSON-ready. */
    while ($row = $result->fetch_assoc()) {
        $incidencias[] = $row;
    }
    
    return $incidencias;
}
?>