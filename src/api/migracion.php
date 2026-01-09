<?php
/**
 * @file migrar_por_usuario.php
 * @brief Migración dirigida al usuario migracion@migracion.com
 */

require_once 'conexion.php'; 
require_once 'conexionPrueba.php'; 

try {
    $db_origen = abrirServidorpruebas(); 
    $db_destino = abrirServidor();       

    echo "<h3>1. Verificando Usuario de Destino</h3>";

    $email_migracion = "migracion@migracion.com";
    $user_id = null;

    // --- PASO A: Buscar o Crear el Usuario ---
    $stmt_user = $db_destino->prepare("SELECT id FROM usuario WHERE gmail = ?");
    $stmt_user->bind_param("s", $email_migracion);
    $stmt_user->execute();
    $res_user = $stmt_user->get_result();

    if ($res_user->num_rows > 0) {
        $user_id = $res_user->fetch_assoc()['id'];
        echo "✔ Usuario existente encontrado (ID: $user_id).<br>";
    } else {
        $pass_temp = password_hash("migracion123", PASSWORD_DEFAULT);
        $stmt_new_user = $db_destino->prepare("INSERT INTO usuario (nombre, apellidos, gmail, password, activo) VALUES ('Migrador', 'Sistema', ?, ?, 1)");
        $stmt_new_user->bind_param("ss", $email_migracion, $pass_temp);
        $stmt_new_user->execute();
        $user_id = $db_destino->insert_id;
        echo "✔ Usuario creado con éxito (ID: $user_id).<br>";
    }

    // --- PASO B: Buscar o Crear Sensor para este usuario ---
    echo "<h3>2. Gestionando Sensor Asociado</h3>";
    
    // Buscamos si el usuario ya tiene algún sensor vinculado
    $stmt_check_sensor = $db_destino->prepare("SELECT sensor_id FROM usuario_sensor WHERE usuario_id = ? LIMIT 1");
    $stmt_check_sensor->bind_param("i", $user_id);
    $stmt_check_sensor->execute();
    $res_check = $stmt_check_sensor->get_result();

    $sensor_id = null;

    if ($res_check->num_rows > 0) {
        $sensor_id = $res_check->fetch_assoc()['sensor_id'];
        echo "✔ Usando sensor ya vinculado (ID: $sensor_id).<br>";
    } else {
        // Creamos un sensor nuevo
        $nueva_mac = "MIG-USER-" . $user_id;
        $stmt_create_s = $db_destino->prepare("INSERT INTO sensor (mac, nombre, modelo) VALUES (?, 'Sensor de Migración', 'Auto-Generado')");
        $stmt_create_s->bind_param("s", $nueva_mac);
        $stmt_create_s->execute();
        $sensor_id = $db_destino->insert_id;

        // Creamos la relación
        $stmt_rel = $db_destino->prepare("INSERT INTO usuario_sensor (usuario_id, sensor_id, actual, comentario) VALUES (?, ?, 1, 'Creado para volcado de datos')");
        $stmt_rel->bind_param("ii", $user_id, $sensor_id);
        $stmt_rel->execute();
        echo "✔ Nuevo sensor creado (ID: $sensor_id) y vinculado al usuario.<br>";
    }

    echo "<h3>3. Migrando Datos</h3>";

    // Consulta de origen
    $res_origen = $db_origen->query("
        SELECT l.gas_tipo, l.valor, l.unidad, l.fecha_registro, d.latitud, d.longitud 
        FROM lecturas l
        JOIN dispositivos d ON l.id_dispositivo = d.id_dispositivo
    ");

    // Preparar inserciones en destino
    $stmt_tipo = $db_destino->prepare("INSERT IGNORE INTO tipo_medicion (medida, unidad) VALUES (?, ?)");
    $stmt_medicion = $db_destino->prepare("INSERT INTO medicion (tipo_medicion_id, valor, hora, localizacion, sensor_id) VALUES (?, ?, ?, ?, ?)");

    $migrados = 0;
    while ($row = $res_origen->fetch_assoc()) {
        // Asegurar tipo_medicion
        $stmt_tipo->bind_param("ss", $row['gas_tipo'], $row['unidad']);
        $stmt_tipo->execute();

        // Obtener ID del tipo
        $stmt_f = $db_destino->prepare("SELECT id FROM tipo_medicion WHERE medida = ? AND unidad = ? LIMIT 1");
        $stmt_f->bind_param("ss", $row['gas_tipo'], $row['unidad']);
        $stmt_f->execute();
        $tipo_id = $stmt_f->get_result()->fetch_assoc()['id'];

        // Insertar medición
        $loc = $row['latitud'] . "," . $row['longitud'];
        $stmt_medicion->bind_param("idssi", $tipo_id, $row['valor'], $row['fecha_registro'], $loc, $sensor_id);
        
        if ($stmt_medicion->execute()) $migrados++;
    }

    echo "<br><b>✅ MIGRACIÓN EXITOSA</b>";
    echo "<p>Usuario: $email_migracion<br>Sensor ID: $sensor_id<br>Registros: $migrados</p>";

} catch (Exception $e) {
    echo "<br><span style='color:red;'>❌ ERROR: " . $e->getMessage() . "</span>";
} finally {
    if (isset($db_origen)) $db_origen->close();
    if (isset($db_destino)) $db_destino->close();
}