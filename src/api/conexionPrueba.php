<?php
/**
 * @file conexion.php
 * @brief Gestión de conexión a la base de datos con soporte para multi-entorno.
 * @author Manuel
 * @date 11/12/2025
 */

/**
 * @brief Establece una conexión con el servidor MySQL probando múltiples credenciales.
 * @details Esta función implementa una lógica de conmutación por error (Failover). 
 * Intenta conectar primero con la configuración local y, si falla, salta a la de producción.
 * * @return mysqli Objeto de conexión MySQLi activo.
 * @throws mysqli_sql_exception Si todas las configuraciones fallan.
 */
function abrirServidorpruebas()
{
    /** @var string $servername Nombre del host del servidor. */
    $servername = "localhost";
    /** @var string $dbname Nombre de la base de datos. */
    $dbname = "pruebasmapa";

    /** * @brief Lista de intentos de configuración.
     * Permite que desarrolladores en local y el entorno de Plesk coexistan.
     */
    $configuraciones = [
        [
            'username' => 'root',
            'password' => '',
            'nombre'   => 'Entorno Local (Root)'
        ],
        [
            'username' => 'fsanpra_grupo_11',
            'password' => 'Loc@1234SALSA',
            'nombre'   => 'Entorno Producción (Plesk)'
        ]
    ];

    // Guardar configuración actual de reporte de errores del driver
    $driver = new mysqli_driver();
    $modoErrorPrevio = $driver->report_mode;
    
    // Desactivar temporalmente el lanzamiento de excepciones para pruebas silenciosas
    mysqli_report(MYSQLI_REPORT_OFF);

    foreach ($configuraciones as $config) {
        try {
            // Intento de instanciación
            $conn = @new mysqli($servername, $config['username'], $config['password'], $dbname);

            // Verificar si hubo error de conexión
            if ($conn->connect_error) {
                // Registrar el fallo en el log para diagnóstico, pero continuar el bucle
                error_log("Diagnóstico: El intento {$config['nombre']} no conectó.");
                continue; 
            }

            // --- Conexión Exitosa ---
            
            // Restaurar el modo de reporte original del driver
            mysqli_report($modoErrorPrevio);
            
            // Configurar charset
            $conn->set_charset("utf8mb4");
            
            error_log("Conexión establecida con éxito: {$config['nombre']}");
            return $conn;

        } catch (Exception $e) {
            error_log("Excepción capturada en {$config['nombre']}: " . $e->getMessage());
            continue;
        }
    }

    // --- Si llega aquí, todos los intentos fallaron ---
    
    mysqli_report($modoErrorPrevio); // Restaurar antes de morir
    
    header('Content-Type: application/json');
    http_response_code(500);
    die(json_encode([
        "status" => "error",
        "message" => "Error crítico: No se pudo conectar a la base de datos local ni de producción."
    ]));
}