<?php
/**
 * @file obtenerEstadosIncidencias.php
 * @brief Cargador de catálogo para los estados de gestión de incidencias.
 * @details Recupera la lista completa de estados definidos en el sistema (ej. Abierta, En Proceso, 
 * Cerrada) para alimentar selectores y filtros en la interfaz de usuario.
 * @author Manuel
 * @date 30/10/2025
 */

/**
 * @brief Recupera todos los estados de incidencia disponibles.
 * @param mysqli $conn Instancia de conexión activa a la base de datos.
 * @return array Lista asociativa de estados. Si falla o no hay datos, devuelve un array vacío.
 */
function obtenerEstadosIncidencia($conn) {
    // ----------------------------------------------------------------------------------------
    // 1. EJECUCIÓN DE CONSULTA MAESTRA
    // ----------------------------------------------------------------------------------------

    /** @section LecturaEstados 
     * Consulta simple a la tabla de referencia para obtener el listado oficial de fases.
     */
    /* SQL:
     * Selecciona el identificador y el nombre legible del estado.
     * 'ORDER BY id' asegura que se presenten en el orden lógico de creación.
     */
    $res = $conn->query("SELECT id, nombre FROM estado_incidencia ORDER BY id");

    // ----------------------------------------------------------------------------------------
    // 2. RETORNO DE RESULTADOS
    // ----------------------------------------------------------------------------------------

    /** @note Se utiliza MYSQLI_ASSOC para que las claves del array coincidan con los nombres de las columnas. */
    return $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}
?>