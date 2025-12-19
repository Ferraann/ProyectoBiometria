<?php
/**
 * @file distancias.php
 * @brief Gestión de métricas de actividad física (distancias recorridas).
 * @details Proporciona funciones para el registro diario acumulativo de distancias y la 
 * recuperación de datos históricos por usuario.
 * @author Manuel
 * @date 05/12/2025
 */

/**
 * @brief Registra o actualiza la distancia recorrida por un usuario en el día actual.
 * @details Si ya existe un registro para la fecha de hoy, suma la nueva distancia a la existente.
 * @param mysqli $conn Instancia de conexión a la base de datos.
 * @param array $data {
 * @var int $usuario_id ID del usuario.
 * @var float $distancia Valor de la distancia a añadir.
 * }
 * @return array Estado de la operación y distancia total acumulada hoy.
 */
function guardarDistanciaHoy($conn, $data)
{
    // ----------------------------------------------------------------------------------------
    // 1. VALIDACIÓN DE ENTRADA
    // ----------------------------------------------------------------------------------------
    if (empty($data['usuario_id']) || !isset($data['distancia'])) {
        return ["status" => "error", "mensaje" => "Faltan parámetros: usuario_id o distancia."];
    }

    /** @var int $usuario_id */
    $usuario_id = (int)$data['usuario_id'];
    /** @var float $distancia */
    $distancia = (float)$data['distancia'];
    /** @var string $hoy Fecha actual en formato ISO. */
    $hoy = date("Y-m-d");

    // ----------------------------------------------------------------------------------------
    // 2. VERIFICACIÓN DE EXISTENCIA
    // ----------------------------------------------------------------------------------------
    
    /** @section BusquedaDiaria Consulta para verificar si el usuario ya tiene actividad hoy. */
    /* SQL:
     * Seleccionamos distancia para comprobar si debemos hacer INSERT o UPDATE.
     * El filtro combina usuario y fecha (clave candidata lógica).
     */
    $stmt = $conn->prepare("SELECT id, distancia FROM distancias_diarias 
                            WHERE usuario_id = ? AND fecha = ?");
    $stmt->bind_param("is", $usuario_id, $hoy);
    $stmt->execute();
    $result = $stmt->get_result();

    // ----------------------------------------------------------------------------------------
    // 3. ACTUALIZACIÓN O CREACIÓN
    // ----------------------------------------------------------------------------------------

    if ($row = $result->fetch_assoc()) {
        /** @section UpdateDistancia Acumulación de distancia. */
        $nuevaDistancia = $row['distancia'] + $distancia;

        /* SQL:
         * Actualiza el registro existente sumando la nueva medición.
         * Se identifica por 'id' (Primary Key) para máxima eficiencia.
         */
        $stmt2 = $conn->prepare("UPDATE distancias_diarias 
                                 SET distancia = ? 
                                 WHERE id = ?");
        $stmt2->bind_param("di", $nuevaDistancia, $row['id']);
        $stmt2->execute();
        $stmt2->close();

        return [
            "status" => "ok",
            "mensaje" => "Distancia actualizada.",
            "distancia_total" => $nuevaDistancia
        ];
    }

    /** @section InsertDistancia Creación del primer registro del día. */
    /* SQL:
     * Inserta una nueva fila vinculando al usuario con la fecha actual del sistema.
     */
    $stmt2 = $conn->prepare("INSERT INTO distancias_diarias (usuario_id, fecha, distancia) 
                             VALUES (?, ?, ?)");
    $stmt2->bind_param("isd", $usuario_id, $hoy, $distancia);
    $stmt2->execute();
    $stmt2->close();

    return [
        "status" => "ok",
        "mensaje" => "Distancia guardada correctamente.",
        "distancia_total" => $distancia
    ];
}

/**
 * @brief Recupera todos los registros de distancia de un usuario.
 * @param mysqli $conn Conexión a DB.
 * @param array $data Contiene 'usuario_id'.
 * @return array Historial ordenado cronológicamente de forma descendente.
 */
function getHistorialDistancias($conn, $data)
{
    if (empty($data['usuario_id'])) {
        return ["status" => "error", "mensaje" => "Falta parámetro: usuario_id."];
    }

    $usuario_id = (int)$data['usuario_id'];

    /** @section ConsultaHistorial */
    /* SQL:
     * Selecciona fecha y distancia filtrando por usuario.
     * 'ORDER BY fecha DESC' garantiza que los datos más recientes aparezcan primero.
     */
    $stmt = $conn->prepare("SELECT fecha, distancia 
                            FROM distancias_diarias 
                            WHERE usuario_id = ?
                            ORDER BY fecha DESC");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $historial = [];

    while ($row = $result->fetch_assoc()) {
        $historial[] = [
            "fecha" => $row['fecha'],
            "distancia" => (float)$row['distancia']
        ];
    }

    return [
        "status" => "ok",
        "historial" => $historial
    ];
}

/**
 * @brief Obtiene la distancia registrada en un día específico.
 * @param mysqli $conn Conexión a DB.
 * @param array $data {
 * @var int $usuario_id
 * @var string $fecha Formato 'YYYY-MM-DD'
 * }
 * @return array Resultado con el valor de distancia o 0 si no hay registro.
 */
function getDistanciaFecha($conn, $data)
{
    if (empty($data['usuario_id']) || empty($data['fecha'])) {
        return ["status" => "error", "mensaje" => "Faltan parámetros: usuario_id o fecha."];
    }

    $usuario_id = (int)$data['usuario_id'];
    $fecha = $data['fecha'];

    /** @section ConsultaPuntual */
    /* SQL:
     * Busca la coincidencia exacta de fecha para un usuario determinado.
     */
    $stmt = $conn->prepare("SELECT distancia FROM distancias_diarias 
                            WHERE usuario_id = ? AND fecha = ?");
    $stmt->bind_param("is", $usuario_id, $fecha);
    $stmt->execute();

    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return ["status" => "ok", "distancia" => (float)$row['distancia']];
    }

    /** @note Si no se encuentra el registro, se asume actividad nula (0.0). */
    return ["status" => "ok", "distancia" => 0];
}
?>