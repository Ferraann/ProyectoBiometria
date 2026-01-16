<?php
require_once 'conexion.php'; 
require_once 'logicaNegocio/guardarMedicion.php'; 

// 1. Establecemos la conexión
$conn = abrirServidor(); 

// 2. Simulamos un "Paseo Aleatorio" (Random Walk)
// Definimos un valor base inicial si no existe (usamos sesiones para recordar el último valor)
session_start();
if (!isset($_SESSION['ultimo_valor'])) {
    $_SESSION['ultimo_valor'] = 25.0; // Valor inicial
}

// Generamos una variación pequeña (entre -0.2 y 0.2) para que sea suave
$variacion = (rand(-20, 20) / 100); 
$nuevoValor = $_SESSION['ultimo_valor'] + $variacion;

// Evitamos que el valor se dispare a rangos imposibles (ej. mantener entre 20 y 30)
if ($nuevoValor > 30) $nuevoValor = 29.5;
if ($nuevoValor < 20) $nuevoValor = 20.5;

$_SESSION['ultimo_valor'] = $nuevoValor;

// 3. Preparamos los datos
$datosPrueba = [
    "sensor_id" => 1,
    "valor" => round($nuevoValor, 2), // Redondeamos a 2 decimales
    "tipo_medicion_id" => 1,
    "localizacion" => "Laboratorio Biometría"
];

// 4. Ejecutamos la inserción
$resultado = guardarMedicion($conn, $datosPrueba);

// 5. Configuración de la interfaz de usuario
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="5">
    <title>Simulador de Sensor Aither</title>
    <style>
        body { font-family: sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background: #f0f2f5; }
        .card { background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: center; }
        .valor { font-size: 3rem; color: #007bff; font-weight: bold; }
        .status { margin-top: 1rem; padding: 0.5rem; border-radius: 5px; }
        .ok { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Simulando Sensor ID: <?php echo $datosPrueba['sensor_id']; ?></h2>
        <div class="valor"><?php echo $datosPrueba['valor']; ?> °C</div>
        <p>Enviando datos cada 5 segundos...</p>
        
        <div class="status <?php echo ($resultado['status'] == 'ok' ? 'ok' : 'error'); ?>">
            <strong>Resultado:</strong> <?php echo $resultado['mensaje']; ?>
        </div>
        
        <p><small>Hora del envío: <?php echo date('H:i:s'); ?></small></p>
    </div>
</body>
</html>
<?php $conn->close(); ?>