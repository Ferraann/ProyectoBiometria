<?php
/**
 * @file conexion.php
 * @brief Gestión de conexión a la base de datos.
 * @author Manuel
 * @date 11/12/2025
 */

/**
 * @brief Establece una conexión con el servidor MySQL probando múltiples credenciales.
 * * Esta función implementa una lógica de conmutación por error (Failover). Intenta
 * conectar primero con una configuración local y, si falla, lo intenta con la
 * configuración de producción.
 * * @details
 * 1. Define un array de configuraciones (Local y Producción).
 * 2. Itera sobre ellas intentando crear una instancia de `mysqli`.
 * 3. Si la conexión tiene éxito, configura el charset a utf8mb4 y devuelve el objeto.
 * 4. Si todas fallan, termina la ejecución devolviendo un JSON de error.
 * * @return mysqli Objeto de conexión MySQLi activo.
 * @throws Exception Termina la ejecución con die() si falla toda conexión.
 */
function abrirServidor()
{
    /** @var string $servername Nombre del host del servidor. */
    $servername = "localhost";
    /** @var string $dbname Nombre de la base de datos. */
    $dbname = "aither";

    /** * @brief Lista de intentos de configuración.
     * Cada elemento contiene el usuario, contraseña y un nombre descriptivo.
     */
    $configuraciones = [
        // Intento 1 (Configuración de Desarrollo/Local)
        [
            'username' => 'root',
            'password' => '',
            'nombre'   => 'Local/Root'
        ],
        // Intento 2 (Configuración de Producción/Plesk)
        [
            'username' => 'aitherdb',
            'password' => 'Sansaloni330.',
            'nombre'   => 'Plesk/Producción'
        ]
    ];

    /** @var int $reportLevel Almacena el nivel de reporte de errores previo. */
    $reportLevel = error_reporting(0);

    /**
     * --- Bucle de Intento de Conexión ---
     */
    foreach ($configuraciones as $config) {

        /** @var mysqli $conn Instancia de conexión para el intento actual. */
        $conn = new mysqli($servername, $config['username'], $config['password'], $dbname);

        // Verificar conexión
        if ($conn->connect_error) {
            // Registro de error en los logs del servidor
            error_log("Fallo de conexión en el intento {$config['nombre']}: " . $conn->connect_error);

            if ($conn) {
                $conn->close();
            }
            continue; // Probar la siguiente configuración
        }

        /**
         * --- Conexión Exitosa ---
         */

        // Restaurar reporte de errores
        error_reporting($reportLevel);

        // Establecer el juego de caracteres a UTF-8 de 4 bytes
        $conn->set_charset("utf8mb4");

        error_log("Conexión exitosa a la base de datos usando la configuración: {$config['nombre']}");
        return $conn;
    }

    /**
     * --- Fallo Total ---
     * Se ejecuta solo si se han agotado todos los intentos del bucle foreach.
     */

    error_reporting($reportLevel);

    header('Content-Type: application/json');
    die(json_encode([
        "status" => "error",
        "message" => "Error de conexión: No se pudo conectar a la base de datos con ninguna de las configuraciones."
    ]));
}