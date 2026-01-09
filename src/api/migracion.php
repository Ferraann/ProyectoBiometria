<?php
/**
 * @file migrar_con_depuracion.php
 */

// 1. Forzar visualización de errores y límites altos
error_reporting(E_ALL);
ini_set('display_errors', 1);
set_time_limit(600); 
ini_set('memory_limit', '512M');

// Forzar que el texto salga al navegador en tiempo real
if (ob_get_level()) ob_end_clean();
ob_implicit_flush(true);

require_once 'conexion.php'; 
require_once 'conexionPrueba.php'; 

echo "<h2>Iniciando Depuración de Migración</h2>";

try {
    echo "STEP 1: Intentando conectar a bases de datos... ";
    $db_origen = abrirServidorpruebas(); 
    $db_destino = abrirServidor();       
    echo "<span style='color:green;'>OK</span><br>";

    echo "STEP 2: Buscando usuario 'migracion@migracion.com'... ";
    $email = "migracion@migracion.com";
    $stmt_u = $db_destino->prepare("SELECT id FROM usuario WHERE gmail = ?");
    $stmt_u->bind_param("s", $email);
    $stmt_u->execute();
    $res_u = $stmt_u->get_result();
    $user_id = ($row = $res_u->fetch_assoc()) ? $row['id'] : null;
    
    if (!$user_id) {
        echo "Creando usuario... ";
        $pass = password_hash("migracion123", PASSWORD_DEFAULT);
        $db_destino->query("INSERT INTO usuario (nombre, apellidos, gmail, password, activo) VALUES ('Migrador', 'Sistema', '$email', '$pass', 1)");
        $user_id = $db_destino->insert_id;
    }
    echo "<span style='color:green;'>ID: $user_id</span><br>";

    echo "STEP 3: Gestionando Sensor... ";
    $res_s = $db_destino->query("SELECT sensor_id FROM usuario_sensor WHERE usuario_id = $user_id LIMIT 1");
    $sensor_id = ($row = $res_s->fetch_assoc()) ? $row['sensor_id'] : null;

    if (!$sensor_id) {
        $mac = "MIG-" . time();
        $db_destino->query("INSERT INTO sensor (mac, nombre) VALUES ('$mac', 'Sensor Migrado')");
        $sensor_id = $db_destino->insert_id;
        $db_destino->query("INSERT INTO usuario_sensor (usuario_id, sensor_id, actual) VALUES ($user_id, $sensor_id, 1)");
        echo "Nuevo sensor creado: $sensor_id... ";
    }
    echo "<span style='color:green;'>ID: $sensor_id</span><br>";

    echo "STEP 4: Asegurando Tipos de Medición (INSERT IGNORE)... ";
    // Cargamos los tipos únicos de la tabla de origen
    $res_tipos_orig = $db_origen->query("SELECT DISTINCT gas_tipo, unidad FROM lecturas");
    $stmt_t_ins = $db_destino->prepare("INSERT IGNORE INTO tipo_medicion (medida, unidad) VALUES (?, ?)");
    while ($t = $res_tipos_orig->fetch_assoc()) {
        $stmt_t_ins->bind_param("ss", $t['gas_tipo'], $t['unidad']);
        $stmt_t_ins->execute();
    }
    echo "<span style='color:green;'>OK</span><br>";

    echo "STEP 5: Mapeando Tipos a memoria... ";
    $tipos_map = [];
    $res_t = $db_destino->query("SELECT id, medida, unidad FROM tipo_medicion");
    while($t = $res_t->fetch_assoc()) {
        $tipos_map[$t['medida'] . "|" . $t['unidad']] = $t['id'];
    }
    echo "<span style='color:green;'>Tipos cargados: " . count($tipos_map) . "</span><br>";

    echo "STEP 6: Consultando lecturas de origen (JOIN lecturas + dispositivos)... ";
    $res_origen = $db_origen->query("
        SELECT l.gas_tipo, l.valor, l.unidad, l.fecha_registro, d.latitud, d.longitud 
        FROM lecturas l 
        JOIN dispositivos d ON l.id_dispositivo = d.id_dispositivo
    ");
    if (!$res_origen) throw new Exception("Error en query origen: " . $db_origen->error);
    $total_filas = $res_origen->num_rows;
    echo "<span style='color:green;'>Total a migrar: $total_filas</span><br>";

    echo "STEP 7: Iniciando bucle de inserción masiva...<br>";
    $stmt_med = $db_destino->prepare("INSERT INTO medicion (tipo_medicion_id, valor, hora, localizacion, sensor_id) VALUES (?, ?, ?, ?, ?)");
    
    $db_destino->autocommit(FALSE);
    $migrados = 0;

    while ($row = $res_origen->fetch_assoc()) {
        $key = $row['gas_tipo'] . "|" . $row['unidad'];
        $tipo_id = $tipos_map[$key] ?? null;

        if ($tipo_id) {
            $loc = $row['latitud'] . "," . $row['longitud'];
            $stmt_med->bind_param("idssi", $tipo_id, $row['valor'], $row['fecha_registro'], $loc, $sensor_id);
            if (!$stmt_med->execute()) {
                echo "<br><span style='color:orange;'>Error en fila $migrados: " . $stmt_med->error . "</span><br>";
            }
            $migrados++;
        }

        // Feedback visual cada 1000 filas
        if ($migrados % 1000 == 0) {
            echo "Procesados $migrados / $total_filas... <br>";
            $db_destino->commit(); 
        }
    }

    echo "STEP 8: Finalizando transacción... ";
    $db_destino->commit();
    $db_destino->autocommit(TRUE);
    echo "<span style='color:green;'>OK</span><br>";

    echo "<h3>✅ PROCESO COMPLETADO</h3>";
    echo "Registros migrados con éxito: <b>$migrados</b>";

} catch (Exception $e) {
    if (isset($db_destino)) $db_destino->rollback();
    echo "<br><h2 style='color:red;'>❌ EL SCRIPT SE DETUVO</h2>";
    echo "<b>Mensaje:</b> " . $e->getMessage();
} finally {
    if (isset($db_origen)) $db_origen->close();
    if (isset($db_destino)) $db_destino->close();
}