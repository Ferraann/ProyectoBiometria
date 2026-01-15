<?php
// php/api_estadisticas.php
header('Content-Type: application/json');
// $conn = new mysqli("127.0.0.1", "root", "", "prueba-mapa");
require_once "../api/conexion.php";
foreach (glob(__DIR__ . "/../api/logicaNegocio/*.php") as $file) {
    require_once $file;
}

if ($conn->connect_error) {
    die(json_encode(["error" => "Conexión fallida"]));
}

$modo = isset($_GET['modo']) ? $_GET['modo'] : 'evolucion';
$gas = isset($_GET['gas']) ? $_GET['gas'] : 'NO2';
// En un caso real, recibirías la fecha del datepicker. Por ahora usamos CURDATE() o una fecha fija.
$fecha = date('Y-m-d');

switch ($modo) {
    case 'evolucion':
        // Agrupa por hora y saca la media del gas seleccionado
        $sql = "SELECT HOUR(fecha_registro) as hora, AVG(valor) as media 
                FROM lecturas 
                WHERE gas_tipo = ? AND DATE(fecha_registro) = ?
                GROUP BY HOUR(fecha_registro) 
                ORDER BY hora ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $gas, $fecha);
        break;

    case 'minmax':
        // Máximos y mínimos de TODOS los gases hoy
        $sql = "SELECT gas_tipo, MIN(valor) as minimo, MAX(valor) as maximo, AVG(valor) as promedio
                FROM lecturas 
                WHERE DATE(fecha_registro) = ?
                GROUP BY gas_tipo";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $fecha);
        break;

    case 'comparativa':
        // Top 5 sensores más contaminantes (del gas seleccionado) hoy
        $sql = "SELECT d.ubicacion_nombre as nombre, AVG(l.valor) as media
                FROM dispositivos d
                JOIN lecturas l ON d.id_dispositivo = l.id_dispositivo
                WHERE l.gas_tipo = ? AND DATE(l.fecha_registro) = ?
                GROUP BY d.id_dispositivo
                ORDER BY media DESC
                LIMIT 5";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $gas, $fecha);
        break;

    default:
        echo json_encode([]);
        exit;
}

if (isset($stmt)) {
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    // Si no hay datos (para pruebas), generamos datos falsos
    if (empty($data)) {
        if ($modo === 'evolucion') {
            for ($i = 0; $i < 24; $i++) {
                $data[] = ["hora" => $i, "media" => rand(10, 80)];
            }
        } elseif ($modo === 'minmax') {
            $gases = ['CO', 'NO2', 'O3', 'SO2', 'PM10'];
            foreach ($gases as $g) {
                $data[] = ["gas_tipo" => $g, "minimo" => rand(5, 20), "maximo" => rand(80, 150), "promedio" => rand(30, 60)];
            }
        } elseif ($modo === 'comparativa') {
            $data = [
                ["nombre" => "Madrid Centro", "media" => rand(50, 90)],
                ["nombre" => "Plaza España", "media" => rand(40, 80)],
                ["nombre" => "Retiro", "media" => rand(30, 60)],
                ["nombre" => "Castellana", "media" => rand(20, 50)],
                ["nombre" => "Vallecas", "media" => rand(10, 40)]
            ];
        }
    }

    echo json_encode($data);
}
?>