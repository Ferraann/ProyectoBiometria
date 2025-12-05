<?php
// ------------------------------------------------------------------
// Fichero: obtenerEstadosIncidencias.php
// Autor: Manuel
// Fecha: 30/10/2025
// ------------------------------------------------------------------
// Descripción:
// Este archivo contiene la lógica de negocio para obtener los estados de las incidencias
// en el sistema de biometría. Permite recuperar una lista de todos los estados de incidencia
// disponibles en la base de datos. Se espera que este script sea invocado a través de una solicitud
// HTTP y que devuelva los estados en formato JSON.
// ------------------------------------------------------------------

function obtenerEstadosIncidencia($conn) {
    return $conn->query("SELECT id, nombre FROM estado_incidencia ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
}

// Ejemplo de uso
try {
    $conn = new PDO("mysql:host=localhost;dbname=biometria", "usuario", "contraseña");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $estados = obtenerEstadosIncidencia($conn);
    header('Content-Type: application/json');
    echo json_encode($estados);
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>