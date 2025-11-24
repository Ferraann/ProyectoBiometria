<?php
// ------------------------------------------------------------------
// Fichero: conexion.php
// Autor: Manuel
// Coautor: Pablo
// Fecha: 30/10/2025
// ------------------------------------------------------------------
// Descripción:
//  Este archivo contiene la función para abrir una conexión con
//  la base de datos 'aither' y la pagina web.
// ------------------------------------------------------------------

function abrirServidor() {
    // Parámetros de conexión
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "aither";

    // Crear conexión con MySQL
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Verificar si hay errores en la conexión
    if ($conn->connect_error) {
        // Si falla, devolvemos un JSON con error y salimos
        die(json_encode([
            "status" => "error",
            "message" => "Error de conexión: " . $conn->connect_error
        ]));
    }

    // Configurar codificación UTF-8 para evitar problemas con acentos
    $conn->set_charset("utf8mb4");

    // Devolver la conexión abierta
    return $conn;
}
?>
