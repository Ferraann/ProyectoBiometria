<?php
// ------------------------------------------------------------------
// Fichero: conexion.php (Reemplaza abrirServidor.php)
// Autor: Manuel
// Fecha: 11/12/2025
// ------------------------------------------------------------------
// Descripción:
//  Función esencial para establecer la conexión con la base de datos
//  MySQL con lógica de Conmutación por Error (Failover).
// ------------------------------------------------------------------

function abrirServidor()
{
    $servername = "localhost";
    $dbname = "aither";
    
    // --- 1. Definir Configuraciones ---
    // La lista de configuraciones que intentaremos.
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
    
    // Desactivamos temporalmente el reporte de errores de conexión de PHP 
    // para manejarlo internamente.
    $reportLevel = error_reporting(0); 
    
    // --- 2. Bucle de Intento de Conexión ---
    foreach ($configuraciones as $config) {
        
        // Crear conexión con MySQL
        $conn = new mysqli($servername, $config['username'], $config['password'], $dbname);

        // Verificar conexión
        if ($conn->connect_error) {
            // La conexión falló. Registramos el error y pasamos al siguiente intento.
            error_log("Fallo de conexión en el intento {$config['nombre']}: " . $conn->connect_error);
            // Cierra el objeto mysqli fallido para liberar recursos
            if ($conn) {
                $conn->close();
            }
            continue; // Pasa al siguiente intento en el bucle
        }

        // --- 3. Conexión Exitosa ---
        
        // Restauramos el nivel de reporte de errores original
        error_reporting($reportLevel); 
        
        // Configuramos UTF-8
        $conn->set_charset("utf8mb4");
        
        error_log("Conexión exitosa a la base de datos usando la configuración: {$config['nombre']}");
        return $conn;
    }
    
    // --- 4. Fallo Total ---
    
    // Si el bucle termina, ninguna conexión funcionó.
    error_reporting($reportLevel); // Restauramos el reporte
    
    // Terminamos la ejecución con un mensaje de error JSON
    die(json_encode([
        "status" => "error",
        "message" => "Error de conexión: No se pudo conectar a la base de datos con ninguna de las configuraciones."
    ]));
}