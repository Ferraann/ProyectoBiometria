<?php
/**
 * @file migrar_por_usuario.php
 */

set_time_limit(600); // 10 minutos
ini_set('memory_limit', '512M');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'conexion.php'; 
require_once 'conexionPrueba.php'; 

try {
    $db_origen = abrirServidorpruebas(); 
    $db_destino = abrirServidor();       

    echo "<h3>1. Verificando Usuario y Sensor</h3>";

    $email_migracion = "migracion@migracion.com";
    
    // Buscar o Crear Usuario (Simplificado)
    $stmt_u = $db_destino->prepare("SELECT id FROM usuario WHERE gmail = ?");
    $stmt_u->bind_param("s", $email_migracion);
    $stmt_u->execute();
    $res_u = $stmt_u->get_result();
    $user_id = ($row = $res_u->fetch_assoc()) ? $row['id'] : null;

    if (!$user_id) {
        $pass = password_hash("migracion123", PASSWORD_DEFAULT);
        $db_destino->query("INSERT INTO usuario (nombre, apellidos, gmail, password, activo) VALUES ('Migrador', 'Sistema', '$email_migracion', '$pass', 1)");
        $user_id = $db_destino->insert_id;
        echo "✔ Usuario creado: $user_id<br>";
    }

    // Buscar o Crear Sensor
    $res_s = $db_destino->query("SELECT sensor_id FROM usuario_sensor WHERE usuario_id = $user_id LIMIT 1");
    $sensor_id = ($row = $res_s->fetch_assoc()) ? $row['sensor_id'] : null;

    if (!$sensor_id) {
        $mac = "MIG-" . time();
        $db_destino->query("INSERT INTO sensor (mac, nombre) VALUES ('$mac', 'Sensor Migrado')");
        $sensor_id = $db_destino->insert_id;
        $db_destino->query("INSERT INTO usuario_sensor (usuario_id, sensor_id, actual) VALUES ($user_id, $sensor_id, 1)");
        echo "✔ Sensor creado: $sensor_id<br>";
    }

    echo "<h3>2. Migrando Datos (Modo Rápido)</h3>";

    // CARGAR TIPOS DE MEDICIÓN EN MEMORIA (Para evitar miles de SELECT)
    $db_destino->query("INSERT IGNORE INTO tipo_medicion (medida, unidad) SELECT DISTINCT gas_tipo, unidad FROM fsanpra_prueba-mapa.lecturas"); 
    
    $tipos_map = [];
    $res_t = $db_destino->query("SELECT id, medida, unidad FROM tipo_medicion");
    while($t = $res_t->fetch_assoc()) {
        $tipos_map[$t['medida'] . $t['unidad']] = $t['id'];
    }

    // MIGRACIÓN MASIVA
    $res_origen = $db_origen->query("SELECT l.gas_tipo, l.valor, l.unidad, l.fecha_registro, d.latitud, d.longitud 
                                     FROM lecturas l JOIN dispositivos d ON l.id_dispositivo = d.id_dispositivo");

    $stmt_medicion = $db_destino->prepare("INSERT INTO medicion (tipo_medicion_id, valor, hora, localizacion, sensor_id) VALUES (?, ?, ?, ?, ?)");

    // INICIO TRANSACCIÓN (Esto evita el Error 500)
    $db_destino->autocommit(FALSE);
    $migrados = 0;

    while ($row = $res_origen->fetch_assoc()) {
        $key = $row['gas_tipo'] . $row['unidad'];
        $tipo_id = $tipos_map[$key] ?? null;

        if ($tipo_id) {
            $loc = $row['latitud'] . "," . $row['longitud'];
            $stmt_medicion->bind_param("idssi", $tipo_id, $row['valor'], $row['fecha_registro'], $loc, $sensor_id);
            $stmt_medicion->execute();
            $migrados++;
        }

        if ($migrados % 500 == 0) $db_destino->commit(); // Guardar en bloques de 500
    }

    $db_destino->commit(); // Guardar resto
    $db_destino->autocommit(TRUE);

    echo "<b>✅ MIGRACIÓN EXITOSA: $migrados registros.</b>";

} catch (Exception $e) {
    if(isset($db_destino)) $db_destino->rollback();
    echo "<br><span style='color:red;'>❌ ERROR: " . $e->getMessage() . "</span>";
}