<?php
$servername = "localhost";
$username   = "aitherdb";
$password   = "Sansaloni330.";
$dbname     = "aither";
$port       = 3306;

$conn = new mysqli($servername, $username, $password, $dbname, $port);

if ($conn->connect_errno) {
    echo "<h3>ERROR de conexión</h3>";
    echo "Código: " . $conn->connect_errno . "<br>";
    echo "Mensaje: " . $conn->connect_error . "<br>";
    die();
}

echo "<h3>Conexión exitosa</h3>";

$result = $conn->query("SHOW TABLES;");
if ($result) {
    echo "Tablas encontradas en '$dbname':<br>";
    while ($row = $result->fetch_array()) {
        echo "- " . $row[0] . "<br>";
    }
} else {
    echo "⚠ Conectó, pero no pudo listar tablas: " . $conn->error;
}
?>
