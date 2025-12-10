<?php
function abrirServidor() {
    $servername = "localhost";
    $username   = "root";
    $password   = "";
    $dbname     = "aither";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        header('Content-Type: application/json');
        die(json_encode([
            "status" => "error",
            "message" => "Error de conexión: " . $conn->connect_error
        ]));
    }

    $conn->set_charset("utf8mb4");
    return $conn;
}
?>
