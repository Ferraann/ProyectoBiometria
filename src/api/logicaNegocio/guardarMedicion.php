<?php
/**
 * @file guardarMedicion.php
 * @brief Lógica de negocio para la ingesta de datos provenientes de sensores.
 * @details Procesa y almacena lecturas telemétricas, garantizando que el sensor emisor 
 * esté correctamente vinculado a un usuario activo antes de persistir los datos.
 * @author Manuel
 * @date 05/12/2025
 */

/**
 * @brief Registra una nueva lectura de sensor en la base de datos.
 * @param mysqli $conn Instancia de conexión activa a la base de datos.
 * @param array $data {
 * @var int $tipo_medicion_id ID de la magnitud (Temperatura, Humedad, etc.).
 * @var float $valor Valor numérico de la lectura.
 * @var int $sensor_id Identificador del hardware emisor.
 * @var string $localizacion Coordenadas o etiqueta de ubicación.
 * }
 * @return array {
 * @var string status 'ok' o 'error'.
 * @var string mensaje Descripción del resultado o causa del fallo.
 * }
 */
function guardarMedicion($conn, $data)
{
    // ----------------------------------------------------------------------------------------
    // 1. VALIDACIÓN DE PARÁMETROS
    // ----------------------------------------------------------------------------------------

    /** @section ValidacionEntrada Comprobación de campos obligatorios. */
    if (!isset($data['tipo_medicion_id'], $data['valor'], $data['sensor_id'], $data['localizacion'])) {
        return ["status" => "error", "mensaje" => "Faltan parámetros obligatorios."];
    }

    /** @var int $sensor_id */
    $sensor_id = (int)$data['sensor_id'];

    // ----------------------------------------------------------------------------------------
    // 2. VERIFICACIÓN DE INTEGRIDAD DE RELACIÓN
    // ----------------------------------------------------------------------------------------

    /** * @section ValidacionRelacion 
     * Comprueba si el sensor tiene una vinculación vigente con algún usuario.
     * @note Este paso evita que sensores "huérfanos" o desvinculados inserten datos basura.
     */
    /* SQL:
     * Cuenta registros en la tabla de asignación donde el sensor coincida
     * y el flag 'actual' sea 1 (relación vigente).
     */
    $sqlCheck = "SELECT COUNT(*) as count FROM usuario_sensor 
                 WHERE sensor_id = ? AND actual = 1";
    
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->bind_param("i", $sensor_id);
    $stmtCheck->execute();
    $result = $stmtCheck->get_result();
    $row = $result->fetch_assoc();
    
    /** @var int $existeRelacion Cantidad de vínculos activos encontrados. */
    $existeRelacion = $row['count'];
    $stmtCheck->close();

    if ($existeRelacion == 0) {
        return [
            "status" => "error",
            "mensaje" => "No existe una relación activa entre el sensor y un usuario."
        ];
    }

    // ----------------------------------------------------------------------------------------
    // 3. PERSISTENCIA DE LA MEDICIÓN
    // ----------------------------------------------------------------------------------------

    /** @section InsercionDatos Registro de la magnitud física. */
    /* SQL:
     * Almacena el valor junto a sus metadatos (tipo, sensor de origen y ubicación).
     * La marca de tiempo (timestamp) suele ser automática por defecto en la DB.
     */
    $sql = "INSERT INTO medicion (tipo_medicion_id, valor, sensor_id, localizacion)
            VALUES (?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    
    /** @note Tipos de bind: i (int), d (double/float), i (int), s (string). */
    $stmt->bind_param(
        "idis",
        $data['tipo_medicion_id'],
        $data['valor'],
        $data['sensor_id'],
        $data['localizacion']
    );

    if ($stmt->execute()) {
        $stmt->close();
        return ["status" => "ok", "mensaje" => "Medición guardada correctamente."];
    } else {
        $errorMsg = $conn->error;
        $stmt->close();
        return ["status" => "error", "mensaje" => "Error al guardar medición: " . $errorMsg];
    }
}
?>