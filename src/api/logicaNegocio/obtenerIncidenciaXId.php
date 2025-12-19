<?php
/**
 * @file obtenerIncidenciaXId.php
 * @brief Recuperación de datos detallados de una incidencia específica.
 * @details Permite consultar toda la información técnica y administrativa de una única 
 * incidencia utilizando su identificador único (Primary Key).
 * @author Manuel
 * @date 5/12/2025
 */

/**
 * @brief Obtiene el registro completo de una incidencia por su ID.
 * @param mysqli $conn Instancia de conexión activa a la base de datos.
 * @param int $id Identificador único de la incidencia en la tabla 'incidencias'.
 * @return array|null Devuelve un array asociativo con los datos del registro o null si no existe o hay error.
 */
function obtenerIncidenciaXId($conn, $id)
{
    // ----------------------------------------------------------------------------------------
    // 1. PREPARACIÓN Y EJECUCIÓN
    // ----------------------------------------------------------------------------------------

    /** @section ConsultaUnitaria 
     * Ejecuta una selección filtrada por la clave primaria para garantizar un único resultado.
     */
    /* SQL:
     * Selecciona todos los campos (*) del registro que coincida exactamente con el ID.
     * Al ser un filtro por clave primaria, la búsqueda es de alta eficiencia (O(1)).
     */
    $sql = "SELECT * FROM incidencias WHERE id = ?";

    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        return null;
    }
    
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    /** @var mysqli_result $result Recurso de resultado de la base de datos. */
    $result = $stmt->get_result();
    
    if (!$result) {
        return null;
    }
    
    // ----------------------------------------------------------------------------------------
    // 2. EXTRACCIÓN Y CIERRE
    // ----------------------------------------------------------------------------------------

    /** @var array|null $incidencia Almacena la fila resultante o null si el cursor está vacío. */
    $incidencia = $result->fetch_assoc();
    
    /** @note El cierre del statement libera los recursos del servidor de DB tras la lectura. */
    $stmt->close();
    
    return $incidencia;
}
?>