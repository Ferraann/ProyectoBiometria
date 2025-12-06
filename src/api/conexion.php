<?php
function abrirServidor() {
    // Parámetros de conexión (correctos para Plesk)
    $servername = "localhost:3306";
    $username   = "aitherdb";
    $password   = "Sansaloni330.";
    $dbname     = "aither";

    // Crear conexión con MySQL
    $conn = new mysqli($servername, $username, $password, $dbname);

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
?>
