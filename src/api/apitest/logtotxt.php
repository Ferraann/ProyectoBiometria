<?php
// ------------------------------------------------------------------
// Fichero: logtotxt.php
// Autor: Manuel
// Fecha: 15/12/2025
// ------------------------------------------------------------------
// Descripción:
//   Recopila el contenido de archivos clave (API, Conexión, Lógica) 
//   para ser compartido en una sola salida de texto.
// ------------------------------------------------------------------

// La raíz de la API está un nivel arriba del script actual.
// __DIR__ es /api/apitest/
// $rootDir será /api/
$rootDir = dirname(__DIR__); 

$output = "========================================================\n";
$output .= "               RECOPILACIÓN DE CÓDIGO DE API REST\n";
$output .= "========================================================\n\n";

// ------------------------------------------------------------------
// 1. Archivos Principales (Raíz de la API)
// ------------------------------------------------------------------
$mainFiles = ['index.php', 'conexion.php']; 

foreach ($mainFiles as $file) {
    $filePath = $rootDir . '/' . $file;
    if (file_exists($filePath)) {
        $output .= "### INICIO: " . $file . " ############################\n";
        $output .= file_get_contents($filePath);
        $output .= "\n### FIN: " . $file . " ##############################\n\n";
    } else {
        $output .= "### ADVERTENCIA: Archivo no encontrado: " . $file . "\n\n";
    }
}

// ------------------------------------------------------------------
// 2. Archivos de Lógica de Negocio
// ------------------------------------------------------------------
$logicaNegocioDir = $rootDir . '/logicaNegocio/';
$logicFiles = glob($logicaNegocioDir . "*.php");

$output .= "\n========================================================\n";
$output .= "               CONTENIDO DE logicaNegocio/\n";
$output .= "========================================================\n\n";

if (empty($logicFiles)) {
    $output .= "### ADVERTENCIA: No se encontraron archivos PHP en logicaNegocio/.\n\n";
} else {
    foreach ($logicFiles as $file) {
        $fileName = basename($file);
        $output .= "### INICIO: logicaNegocio/" . $fileName . " #################\n";
        $output .= file_get_contents($file);
        $output .= "\n### FIN: logicaNegocio/" . $fileName . " ###################\n\n";
    }
}

$output .= "========================================================\n";
$output .= "                  FIN DE LA RECOPILACIÓN\n";
$output .= "========================================================\n";

echo $output;

?>