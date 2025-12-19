<?php
/**
 * @file MockDatabase.php
 * @brief Infraestructura de simulación para pruebas unitarias sin base de datos real.
 * @details Define clases que imitan el comportamiento de mysqli, mysqli_stmt y mysqli_result.
 * Permite ejecutar la lógica de negocio en un entorno controlado y seguro.
 * @author Manuel
 * @date 19/12/2025
 */

/**
 * @class MockStmt
 * @brief Simula una sentencia preparada (Statement).
 */
class MockStmt {
    /** @var int $affected_rows Simula el número de filas afectadas por una operación DML. */
    public $affected_rows = 1;
    /** @var string $error Simula un mensaje de error de SQL. */
    public $error = "";
    
    public function bind_param(...$args) { return true; }
    public function execute() { return true; }
    public function close() { return true; }
    /** @return MockResult Devuelve un objeto de resultados simulado. */
    public function get_result() { return new MockResult(); }
}

/**
 * @class MockResult
 * @brief Simula el conjunto de resultados (Result Set) de una consulta.
 */
class MockResult {
    /** @var int $num_rows Cantidad de filas devueltas. */
    public $num_rows = 1;
    private $iteration = 0;
    
    /** @var array $mockData Estructura de datos genérica para alimentar los tests. */
    private $mockData = [
        "id" => 1, "usuario_id" => 1, "id_user" => 1, "sensor_id" => 1, "estado_id" => 1,
        "nombre" => "Elemento Test", "gmail" => "test@aither.com", "activo" => 1,
        "mac" => "AA:BB:CC:DD:EE:FF", "modelo" => "Model-X", "puntos" => 500,
        "valor" => 25.5, "unidad" => "ºC", "medida" => "Temperatura",
        "titulo" => "Incidencia Test", "foto" => "base64_fake_data"
    ];

    /** @return array|null Simula la extracción de una fila asociativa. */
    public function fetch_assoc() {
        if ($this->iteration < 1) { 
            $this->iteration++;
            return $this->mockData;
        }
        return null;
    }
    public function fetch_column() { return 1; }
}

/**
 * @class MockConn
 * @brief Simula la conexión principal a la base de datos.
 */
class MockConn {
    public $error = "";
    public function prepare($sql) { return new MockStmt(); }
    public function query($sql) { return new MockResult(); }
    public function close() { return true; }
}

/**
 * @brief Ejecutor de pruebas con reporte visual.
 * @param string $nombre Nombre descriptivo del test.
 * @param callable $funcion Función anónima que contiene la lógica del test.
 */
function runTest($nombre, $funcion) {
    try {
        $funcion();
        echo "\033[32m[PASS]\033[0m $nombre\n";
    } catch (Exception $e) {
        echo "\033[31m[FAIL]\033[0m $nombre: " . $e->getMessage() . "\n";
    }
}