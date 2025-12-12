<?php
// ------------------------------------------------------------------
// Fichero: abrirServidor.php
// Autor: Manuel
// Fecha: 11/12/2025
// ------------------------------------------------------------------
// Descripción:
//  Función esencial para establecer la conexión con la base de datos
//  MySQL utilizando la extensión `mysqli`.
// ------------------------------------------------------------------

function abrirServidor()
{
    // Parámetros de conexión
    //Plesk
    $servername = "localhost";
    $username   = "root";
    $password   = "";
    //local
    //$username   = "root";
    //$password   = "";
    $dbname     = "aither";
    //$port       = 3306;

    // Crear conexión con MySQL
    $conn = new mysqli($servername, $username, $password, $dbname /*$port*/);

    // Verificar conexión
    if ($conn->connect_error) {
        die(json_encode([
            "status" => "error",
            "message" => "Error de conexión: " . $conn->connect_error
        ]));
    }

    // UTF-8
    $conn->set_charset("utf8mb4");

    return $conn;
}
