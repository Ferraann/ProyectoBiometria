<?php
// ------------------------------------------------------------------
// Fichero: diagnosticotst.php
// Autor: Manuel
// Fecha: 15/12/2025
// ------------------------------------------------------------------
// Descripción:
//   Script para diagnosticar errores de inclusión o sintaxis
//   en los archivos dentro del directorio 'logicaNegocio/'.
// ------------------------------------------------------------------

// Configuración de errores para el script de diagnóstico
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 0); // No queremos que llene el log principal

echo "<h1>Diagnóstico de Archivos en logicaNegocio/</h1>";
echo "<pre>";

$logicaNegocioDir = dirname(__DIR__) . '/logicaNegocio/';
$archivosConError = [];
$archivos = glob($logicaNegocioDir . "*.php");

if (empty($archivos)) {
    echo "ERROR: No se encontraron archivos PHP en el directorio: " . $logicaNegocioDir . "\n";
    exit;
}

// ----------------------------------------------------------------
// 1. Definición de la función de manejo de errores
// ----------------------------------------------------------------

/**
 * Función que captura los errores de PHP (incluidos los fatales)
 * durante el proceso de require_once.
 */
function manejarErrorDeInclusion($errno, $errstr, $errfile, $errline) {
    global $archivosConError, $archivoActual;
    
    // Solo nos interesa capturar errores que rompan la ejecución
    // (E_ERROR, E_PARSE, E_WARNING, E_NOTICE, E_COMPILE_ERROR, etc.)
    
    // Marcamos el archivo actual como problemático
    if (!isset($archivosConError[$archivoActual])) {
        $archivosConError[$archivoActual] = [];
    }
    
    $archivosConError[$archivoActual][] = [
        "tipo" => "ERROR_PHP_NO_FATAL", // Podría ser un Warning o Notice
        "codigo" => $errno,
        "mensaje" => $errstr,
        "fichero" => $errfile,
        "linea" => $errline
    ];
    
    // Si es un error fatal, detenemos el manejador predeterminado
    return true; 
}

// Establecer el manejador de errores personalizado
set_error_handler("manejarErrorDeInclusion");


// ----------------------------------------------------------------
// 2. Iteración y Prueba de Inclusión Individual
// ----------------------------------------------------------------

echo "Archivos a probar: " . count($archivos) . "\n\n";

foreach ($archivos as $file) {
    $nombreArchivo = basename($file);
    echo "Probando archivo: **" . $nombreArchivo . "**...\n";
    
    // Almacenamos el nombre del archivo actual para el manejador de errores
    $archivoActual = $nombreArchivo; 
    
    // Intentamos la inclusión, envuelta en un control de salida para errores fatales.
    // Aunque el set_error_handler captura muchos, un error de Parse (E_PARSE) 
    // podría seguir causando problemas.
    
    try {
        // Usamos include_once en lugar de require_once para intentar continuar 
        // después de un error menos grave, aunque un error de sintaxis grave 
        // (E_PARSE o E_ERROR) puede seguir deteniendo la ejecución.
        include_once($file); 
        
        // Si la ejecución llega hasta aquí, el archivo no causó un fallo fatal
        if (!isset($archivosConError[$nombreArchivo])) {
            echo "   **OK**. Incluido sin errores fatales ni advertencias.\n";
        } else {
             echo "   **ADVERTENCIAS/NOTICES**. Incluido, pero se detectaron problemas leves.\n";
        }
    } catch (Throwable $e) {
        // Capturamos cualquier excepción (aunque en PHP viejo los fatales no son Throwable)
        echo "   **FALLO FATAL/EXCEPCIÓN**:\n";
        echo "      Mensaje: " . $e->getMessage() . "\n";
        echo "      Fichero: " . $e->getFile() . " (Línea: " . $e->getLine() . ")\n";
        $archivosConError[$nombreArchivo][] = [
            "tipo" => "EXCEPCION_FATAL",
            "mensaje" => $e->getMessage(),
            "fichero" => $e->getFile(),
            "linea" => $e->getLine()
        ];
    }
    
    echo "---------------------------------------------------------\n";
}

// ----------------------------------------------------------------
// 3. Restauración del Manejador de Errores y Resultados Finales
// ----------------------------------------------------------------

// Restaurar el manejador de errores predeterminado de PHP
restore_error_handler(); 

echo "\n\n##  Resumen de Resultados ##\n";
    echo "-----------------------------\n";

if (empty($archivosConError)) {
    echo "\n**¡ÉXITO!**\n";
    echo "Todos los " . count($archivos) . " archivos de 'logicaNegocio/' se incluyeron correctamente.\n";
} else {
    echo "\n **ATENCIÓN: Se encontraron problemas en los siguientes archivos:**\n";
    foreach ($archivosConError as $archivo => $errores) {
        echo "\n###  Archivo: **" . $archivo . "**\n";
        foreach ($errores as $error) {
            echo "* **" . $error['tipo'] . "**\n";
            echo "  > Mensaje: " . $error['mensaje'] . "\n";
            echo "  > Fichero: " . $error['fichero'] . "\n";
            echo "  > Línea: " . $error['linea'] . "\n";
        }
    }
    echo "\n\n**Acción Recomendada:** Revisa el archivo marcado con el error más grave (probablemente un `E_PARSE` o `E_ERROR`) para corregir la sintaxis o la lógica que está causando el fallo fatal.\n";
}

echo "</pre>";

?>