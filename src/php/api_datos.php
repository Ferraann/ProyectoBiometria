<?php
header('Content-Type: application/json');
$conn = new mysqli("127.0.0.1", "root", "", "prueba-mapa");

$gas = isset($_GET['gas']) ? $_GET['gas'] : 'CO';

// Esta consulta evita buscar en 1.4M de filas innecesariamente
$sql = "SELECT d.latitud AS lat, d.longitud AS lon, l.valor AS value
        FROM dispositivos d 
        INNER JOIN lecturas l ON d.id_dispositivo = l.id_dispositivo 
        WHERE l.gas_tipo = ? 
        AND l.fecha_registro = (
            SELECT MAX(fecha_registro) 
            FROM lecturas 
            WHERE id_dispositivo = d.id_dispositivo AND gas_tipo = ?
        )";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $gas, $gas);
$stmt->execute();
$result = $stmt->get_result();

$puntos = [];
while ($row = $result->fetch_assoc()) {
    $puntos[] = [
        "lat" => (float)$row['lat'],
        "lon" => (float)$row['lon'],
        "value" => (float)$row['value']
    ];
}
echo json_encode($puntos);