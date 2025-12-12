<?php
function abrirServidor() {
    // Parámetros de conexión (correctos para Plesk)
    $servername = "localhost";    
    $username   = "root";
    $password   = "";
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
?>
