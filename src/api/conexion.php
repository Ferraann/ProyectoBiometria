<?php
function abrirServidor() {
    // Parámetros de conexión (correctos para Plesk)
    $servername = "localhost";
    $username   = "aitherdb";
    $password   = "Sansaloni330.";
    $dbname     = "aither";
    $port       = 3306; 

    // Crear conexión con MySQL
    $conn = new mysqli($servername, $username, $password, $dbname, $port);

    // Verificar conexión
    if ($conn->connect_error) {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(["status" => "error", "mensaje" => "Error de conexión a la base de datos"]);
        exit;
    }

    // UTF-8
    $conn->set_charset("utf8mb4");

    return $conn;
}
?>
