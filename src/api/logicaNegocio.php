<?php
// ------------------------------------------------------------------
// Fichero: logicaNegocio.php
// Autor: Manuel
// Coautor: Pablo
// Fecha: 30/10/2025
// ------------------------------------------------------------------
// Descripción:
//  Aquí se definen todas las funciones lógicas que maneja la API.
//  Cada función se encarga de interactuar con la base de datos y
//  devolver los resultados al archivo index.php.
// ------------------------------------------------------------------

require '/p/vhosts/fsanpra.upv.edu.es/httpdocs/src/libs/PHPMailer-7.0.0/src/Exception.php';
require '/p/vhosts/fsanpra.upv.edu.es/httpdocs/src/libs/PHPMailer-7.0.0/src/PHPMailer.php';
require '/p/vhosts/fsanpra.upv.edu.es/httpdocs/src/libs/PHPMailer-7.0.0/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// -------------------------------------------------------------
// FUNCIÓN 1: Registrar un nuevo usuario
// -------------------------------------------------------------
function registrarUsuario($conn, $data)
{
    /* ---------- 1. Validaciones y saneado ---------- */
    if (!isset($data['nombre'], $data['apellidos'], $data['gmail'], $data['password'])) {
        return ["status" => "error", "mensaje" => "Faltan datos obligatorios para el registro."];
    }

    $nombre    = trim($data['nombre']);
    $apellidos = trim($data['apellidos']);
    $gmail     = trim($data['gmail']);
    $password  = trim($data['password']);
    
    // Validar formato de correo
    if (!filter_var($gmail, FILTER_VALIDATE_EMAIL)) {
        return ["status" => "error", "mensaje" => "El formato del correo electrónico no es válido."];
    }
    
    $hash      = password_hash($password, PASSWORD_DEFAULT);

    /* ---------- 2. ¿Existe el correo? ---------- */
    $stmt = $conn->prepare("SELECT id, activo FROM usuario WHERE gmail = ?");
    $stmt->bind_param("s", $gmail);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows) {
        $usr = $res->fetch_assoc();

        if ($usr['activo'] == 1) {
            return ["status" => "error", "mensaje" => "El correo ya está registrado y activado."];
        }

        /* usuario inactivo → lo borramos */
        $del = $conn->prepare("DELETE FROM usuario WHERE id = ?");
        $del->bind_param("i", $usr['id']);
        $del->execute();
    }

    /* ---------- 3. Crear token seguro ---------- */
    $token = bin2hex(random_bytes(32)); // 64 caracteres
    $token_expira = date("Y-m-d H:i:s", time() + 900); // 15 min de validez

    /* ---------- 4. Insertar nuevo usuario (inactivo + token + expiración) ---------- */
    $ins = $conn->prepare("INSERT INTO usuario 
        (nombre, apellidos, gmail, password, activo, token, token_expira)
        VALUES (?, ?, ?, ?, 0, ?, ?)");
    $ins->bind_param("ssssss", $nombre, $apellidos, $gmail, $hash, $token, $token_expira);

    if (!$ins->execute()) {
        return ["status" => "error", "mensaje" => "Error al registrar el usuario: " . $conn->error];
    }

    /* ---------- 5. Enviar correo de activación ---------- */
    $enlace = "http://localhost/ProyectoBiometria/src/html/activacion.html?token=" . urlencode($token);

    $asunto  = "Activa tu cuenta en AITHER";
    $cuerpo  = "<h2>¡Hola $nombre!</h2>
        <p>Gracias por registrarte. Pulsa el botón para activar tu cuenta:</p>
        <p><a href='$enlace' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:4px;'>Activar cuenta</a></p>
        <p>Si el botón no funciona, copia y pega esta dirección:<br>$enlace</p>";

    $enviado = false;

    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'no.reply.aither@gmail.com';
        $mail->Password   = 'esdf lkoc qprz rkum';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('noreply@aither.com', 'AITHER');
        $mail->addAddress($gmail);
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $cuerpo;

        $mail->send();
        $enviado = true;

    } catch (Exception $e) {
        error_log('PHPMailer error: ' . $mail->ErrorInfo);
        // No fallamos el registro por no poder enviar el email
        return ["status" => "ok", "mensaje" => "Usuario registrado correctamente, pero no se pudo enviar el correo de activación. Contacta con soporte."];
    }

    return ["status" => "ok", "mensaje" => "Usuario registrado correctamente. Revisa tu correo para activarlo."];
}



// -------------------------------------------------------------
// FUNCIÓN 1.5: Activar usuario
// -------------------------------------------------------------
function activarUsuario($conn, $token)
{
    // 1. Buscar el usuario por token
    $sql = "SELECT id, token_expira FROM usuario WHERE token = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        return ["status" => "error", "mensaje" => "Token inválido o ya usado."];
    }

    $usr = $res->fetch_assoc();

    // 2. Comprobar si el token ha expirado
    if (strtotime($usr['token_expira']) < time()) {
        return ["status" => "error", "mensaje" => "El enlace ha expirado. Solicita un nuevo correo de activación."];
    }

    // 3. Activar usuario
    $sql2 = "UPDATE usuario SET activo = 1, token = NULL, token_expira = NULL WHERE id = ?";
    $stmt2 = $conn->prepare($sql2);
    $stmt2->bind_param("i", $usr['id']);
    $stmt2->execute();

    if ($stmt2->affected_rows > 0) {
        return ["status" => "ok", "mensaje" => "Cuenta activada correctamente."];
    } else {
        return ["status" => "error", "mensaje" => "No se pudo activar la cuenta."];
    }
}


// -------------------------------------------------------------
// FUNCIÓN 2: Iniciar sesión (login)
// -------------------------------------------------------------
function loginUsuario($conn, $gmail, $password)
{
    session_start();

    /* 1. Datos del usuario */
    $stmt = $conn->prepare(
        "SELECT id, nombre, apellidos, gmail, password, activo
         FROM usuario
         WHERE gmail = ?"
    );
    $stmt->bind_param("s", $gmail);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        return ["status" => "error", "mensaje" => "Usuario no encontrado"];
    }
    $user = $res->fetch_assoc();

    /* 2. ¿Contraseña correcta? */
    if (!password_verify($password, $user['password'])) {
        return ["status" => "error", "mensaje" => "Contraseña incorrecta"];
    }

    /* 3. ¿Cuenta activada? */
    if (!$user['activo']) {
        return ["status" => "error", "mensaje" => "Cuenta no activada"];
    }

    // Guardamos los datos del usuario en la sesión
    $_SESSION['usuario_id'] = $user['id'];
    $_SESSION['usuario_nombre'] = $user['nombre'];
    $_SESSION['usuario_apellidos'] = $user['apellidos'];
    $_SESSION['usuario_correo'] = $user['gmail'];
    $_SESSION['usuario_password'] = $password;

    /* 4. Todo OK → devolvemos el usuario SIN el hash */
    unset($user['password']);
    return ["status" => "ok", "usuario" => $user];
}

// -------------------------------------------------------------
// FUNCIÓN 3: Obtener todas las mediciones
// -------------------------------------------------------------
function obtenerMediciones($conn)
{
    $sql = "SELECT m.id, tm.medida, tm.unidad, m.valor, m.hora, m.localizacion, s.mac
            FROM medicion m
            INNER JOIN tipo_medicion tm ON m.tipo_medicion_id = tm.id
            INNER JOIN sensor s ON m.sensor_id = s.id
            ORDER BY m.hora DESC";

    $result = $conn->query($sql);
    $datos = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $datos[] = $row;
        }
    }

    return $datos;
}

// -------------------------------------------------------------
// FUNCIÓN 4: Guardar una nueva medición
// -------------------------------------------------------------
function guardarMedicion($conn, $data)
{
    // 1 Verificar parámetros obligatorios
    if (!isset($data['tipo_medicion_id'], $data['valor'], $data['sensor_id'], $data['localizacion'])) {
        return ["status" => "error", "mensaje" => "Faltan parámetros obligatorios."];
    }

    $sensor_id = $data['sensor_id'];

    // 2 Comprobar si existe una relación activa usuario-sensor con ese sensor
    $sqlCheck = "SELECT COUNT(*) as count FROM usuario_sensor WHERE sensor_id = ? AND actual = 1";
    $stmtCheck = $conn->prepare($sqlCheck);
    $stmtCheck->bind_param("i", $sensor_id);
    $stmtCheck->execute();
    $result = $stmtCheck->get_result();
    $row = $result->fetch_assoc();
    $existeRelacion = $row['count'];
    $stmtCheck->close();

    // 3 Si no hay relación activa, no permitir guardar la medición
    if ($existeRelacion == 0) {
        return [
            "status" => "error",
            "mensaje" => "No existe una relación activa entre el sensor y un usuario."
        ];
    }

    // 4 Insertar la medición si la relación es válida si existe la relacion usuario-sensor
    $sql = "INSERT INTO medicion (tipo_medicion_id, valor, sensor_id, localizacion)
            VALUES (?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "idis",
        $data['tipo_medicion_id'],
        $data['valor'],
        $data['sensor_id'],
        $data['localizacion']
    );

    if ($stmt->execute()) {
        return ["status" => "ok", "mensaje" => "Medición guardada correctamente."];
    } else {
        return ["status" => "error", "mensaje" => "Error al guardar medición: " . $conn->error];
    }
}

// -------------------------------------------------------------
// FUNCIÓN 5: Crear un nuevo tipo de medición
// -------------------------------------------------------------
function crearTipoMedicion($conn, $data)
{
    if (!isset($data['medida']) || !isset($data['unidad'])) {
        return ["status" => "error", "mensaje" => "Faltan parámetros: medida y unidad son obligatorios."];
    }

    $sql = "INSERT INTO tipo_medicion (medida, unidad, txt) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $data['medida'], $data['unidad'], $data['txt']);

    if ($stmt->execute()) {
        return ["status" => "ok", "mensaje" => "Tipo de medición creado correctamente.", "id" => $conn->insert_id];
    } else {
        return ["status" => "error", "mensaje" => "Error al crear tipo de medición: " . $conn->error];
    }
}

// -------------------------------------------------------------
// FUNCIÓN 6: Asignar un sensor a un usuario, creando el sensor si no existe o modificar la relación si ya existe
// -------------------------------------------------------------
function crearSensorYRelacion($conn, $data)
{
    if (!isset($data['mac']) || !isset($data['usuario_id'])) {
        return ["status" => "error", "mensaje" => "Faltan parámetros: mac y usuario_id son obligatorios."];
    }

    $mac = $data['mac'];
    $usuario_id = $data['usuario_id'];

    // 1. Comprobar si el sensor ya existe
    $sqlBuscarSensor = "SELECT id FROM sensor WHERE mac = ?";
    $stmtBuscar = $conn->prepare($sqlBuscarSensor);
    $stmtBuscar->bind_param("s", $mac);
    $stmtBuscar->execute();
    $result = $stmtBuscar->get_result();

    if ($row = $result->fetch_assoc()) {
        $sensor_id = $row['id'];
    } else {
        // 2. Si no existe, lo creamos
        $sqlInsertarSensor = "INSERT INTO sensor (mac, problema) VALUES (?, 0)";
        $stmtInsertar = $conn->prepare($sqlInsertarSensor);
        $stmtInsertar->bind_param("s", $mac);

        if ($stmtInsertar->execute()) {
            $sensor_id = $conn->insert_id;
        } else {
            return ["status" => "error", "mensaje" => "Error al crear el sensor: " . $conn->error];
        }
    }

    // 3. Cerrar relaciones anteriores activas de ese sensor
    $sqlCerrarRelaciones = "UPDATE usuario_sensor 
                            SET actual = 0, fin_relacion = NOW() 
                            WHERE sensor_id = ? AND actual = 1";
    $stmtCerrar = $conn->prepare($sqlCerrarRelaciones);
    $stmtCerrar->bind_param("i", $sensor_id);
    $stmtCerrar->execute();

    // 4. Crear nueva relación usuario-sensor
    $sqlNuevaRelacion = "INSERT INTO usuario_sensor (usuario_id, sensor_id, actual, inicio_relacion)
                         VALUES (?, ?, 1, NOW())";
    $stmtRelacion = $conn->prepare($sqlNuevaRelacion);
    $stmtRelacion->bind_param("ii", $usuario_id, $sensor_id);

    if ($stmtRelacion->execute()) {
        return [
            "status" => "ok",
            "mensaje" => "Sensor asignado correctamente.",
            "sensor_id" => $sensor_id,
            "id_relacion" => $conn->insert_id
        ];
    } else {
        return ["status" => "error", "mensaje" => "Error al crear la relación usuario-sensor: " . $conn->error];
    }
}

// -------------------------------------------------------------
// FUNCIÓN 7: Terminar relación de un sensor y marcarlo con problema
// -------------------------------------------------------------
function marcarSensorConProblemas($conn, $data)
{
    if (!isset($data['sensor_id'])) {
        return ["status" => "error", "mensaje" => "Falta el parámetro sensor_id."];
    }

    $sensor_id = $data['sensor_id'];

    // 1. Finalizar relaciones activas del sensor
    $sqlFinalizar = "UPDATE usuario_sensor 
                     SET actual = 0, fin_relacion = NOW() 
                     WHERE sensor_id = ? AND actual = 1";
    $stmtFin = $conn->prepare($sqlFinalizar);
    $stmtFin->bind_param("i", $sensor_id);
    $stmtFin->execute();

    // 2. Marcar el sensor como con problema
    $sqlProblema = "UPDATE sensor SET problema = 1 WHERE id = ?";
    $stmtProb = $conn->prepare($sqlProblema);
    $stmtProb->bind_param("i", $sensor_id);

    if ($stmtProb->execute()) {
        return [
            "status" => "ok",
            "mensaje" => "Sensor marcado con problema y relación finalizada.",
            "filas_relaciones_finalizadas" => $stmtFin->affected_rows
        ];
    } else {
        return ["status" => "error", "mensaje" => "Error al actualizar sensor: " . $conn->error];
    }
}

// -------------------------------------------------------------
// FUNCIÓN 8: Reactivar un sensor tras reparación
// -------------------------------------------------------------
function reactivarSensor($conn, $data)
{
    if (!isset($data['sensor_id'])) {
        return ["status" => "error", "mensaje" => "Falta el parámetro sensor_id."];
    }

    $sensor_id = $data['sensor_id'];

    $sql = "UPDATE sensor SET problema = 0 WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $sensor_id);

    if ($stmt->execute()) {
        return ["status" => "ok", "mensaje" => "Sensor reactivado correctamente."];
    } else {
        return ["status" => "error", "mensaje" => "Error al reactivar sensor: " . $conn->error];
    }
}

// -------------------------------------------------------------
// FUNCIÓN 9: Actualizar datos de un usuario
// -------------------------------------------------------------
function actualizarUsuario($conn, $data)
{
    /* 1. Comprobamos ID obligatorio */
    if (empty($data['id'])) {
        return ["status" => "error", "message" => "Falta el id del usuario."];
    }
    $id = (int)$data['id'];

    /* 2. Campos actualizables (solo los que llegan) */
    $allowed = ['nombre', 'apellidos', 'gmail', 'password', 'activo'];
    $setParts = [];
    $types    = '';
    $values   = [];

    foreach ($allowed as $field) {
        if (!isset($data[$field])) {
            continue;
        }
        /* hashear password si viene */
        if ($field === 'password') {
            $data[$field] = password_hash($data[$field], PASSWORD_DEFAULT);
        }
        $setParts[] = "$field = ?";
        $types      .= in_array($field, ['activo'], true) ? 'i' : 's';
        $values[]    = $data[$field];
    }

    if (!$setParts) {
        return ["status" => "error", "message" => "No hay nada que actualizar."];
    }

    /* 3. WHERE id = ? */
    $types .= 'i';
    $values[] = $id;

    $sql = "UPDATE usuario SET " . implode(', ', $setParts) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return ["status" => "error", "message" => "Error preparando consulta: " . $conn->error];
    }

    $stmt->bind_param($types, ...$values);

    if ($stmt->execute()) {
        return ["status" => "ok", "message" => "Usuario actualizado correctamente."];
    } else {
        return ["status" => "error", "message" => "Error al actualizar usuario: " . $stmt->error];
    }
}

// -------------------------------------------------------------
// FUNCIÓN 10: Crear una nueva incidencia
// -------------------------------------------------------------
function crearIncidencia($conn, $data)
{
    if (!isset($data['id_user'], $data['titulo'], $data['descripcion'])) {
        return ["status" => "error", "mensaje" => "Faltan parámetros."];
    }

    // Buscar dinámicamente el estado "Abierta"
    $sqlEstado = "SELECT id FROM estado_incidencia WHERE nombre = 'Abierta' LIMIT 1";
    $resEstado = $conn->query($sqlEstado);
    $estadoRow = $resEstado ? $resEstado->fetch_assoc() : null;
    $estadoInicial = $estadoRow ? $estadoRow['id'] : 1; // fallback por seguridad

    // Insertar la nueva incidencia
    $sql = "INSERT INTO incidencias (id_user, titulo, descripcion, estado_id) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issi", $data['id_user'], $data['titulo'], $data['descripcion'], $estadoInicial);

    if ($stmt->execute()) {
        return [
            "status" => "ok",
            "id_incidencia" => $conn->insert_id,
            "mensaje" => "Incidencia creada correctamente con estado inicial 'Abierta'."
        ];
    } else {
        return [
            "status" => "error",
            "mensaje" => "Error al registrar incidencia: " . $conn->error
        ];
    }
}

// -------------------------------------------------------------
// FUNCIÓN 11: Obtener incidencias activas
// -------------------------------------------------------------
function obtenerIncidenciasActivas($conn)
{
    $sql = "
    SELECT 
        i.id,
        u.nombre                                   AS usuario,
        i.titulo,
        i.descripcion,
        i.fecha_creacion,
        e.nombre                                   AS estado,
        i.id_tecnico,
        COALESCE(tu.nombre, 'Sin asignar')         AS tecnico
    FROM incidencias i
    LEFT JOIN usuario u  ON i.id_user   = u.id
    LEFT JOIN estado_incidencia e ON i.estado_id = e.id
    LEFT JOIN tecnicos t          ON i.id_tecnico = t.usuario_id
    LEFT JOIN usuario tu          ON t.usuario_id = tu.id
    WHERE e.nombre NOT IN ('Cerrada', 'Cancelada')
    ORDER BY i.fecha_creacion DESC
";

    $result = $conn->query($sql);
    $incidencias = [];
    while ($row = $result->fetch_assoc()) {
        $incidencias[] = $row;
    }
    return $incidencias;
}

// -------------------------------------------------------------
// FUNCIÓN 12: Cerrar una incidencia
// -------------------------------------------------------------
function cerrarIncidencia($conn, $data)
{
    if (!isset($data['incidencia_id'])) {
        return ["status" => "error", "mensaje" => "Falta el parámetro incidencia_id."];
    }

    // Obtenemos el ID del estado "Cerrada"
    $sqlEstado = "SELECT id FROM estado_incidencia WHERE nombre = 'Cerrada' LIMIT 1";
    $resEstado = $conn->query($sqlEstado);
    $estadoRow = $resEstado->fetch_assoc();
    $estadoId = $estadoRow ? $estadoRow['id'] : 4; // fallback por si acaso

    // Actualizamos la incidencia
    $sql = "UPDATE incidencias SET estado_id = ?, fecha_cierre = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $estadoId, $data['incidencia_id']);

    if ($stmt->execute()) {
        return ["status" => "ok", "mensaje" => "Incidencia cerrada correctamente."];
    } else {
        return ["status" => "error", "mensaje" => "Error al cerrar incidencia: " . $conn->error];
    }
}

// -------------------------------------------------------------
// FUNCIÓN 13: Guardar foto de incidencia
// -------------------------------------------------------------
function guardarFotoIncidencia($conn, $data)
{
    if (empty($data['incidencia_id']) || empty($data['fotos']) || !is_array($data['fotos'])) {
        return ["status" => "error", "mensaje" => "Faltan parámetros: incidencia_id o fotos."];
    }

    $incidencia_id = (int)$data['incidencia_id'];
    $fotos = $data['fotos']; // array base64

    $stmt = $conn->prepare("INSERT INTO fotos_incidencia (incidencia_id, foto) VALUES (?, ?)");

    foreach ($fotos as $base64) {
        // Quitar posible cabecera
        $base64 = preg_replace('/^data:image\/\w+;base64,/', '', $base64);
        $blob = base64_decode($base64);
        if ($blob === false) {
            return ["status" => "error", "mensaje" => "Una de las imágenes no es válida."];
        }
        $stmt->bind_param("ib", $incidencia_id, $blob);
        $stmt->send_long_data(1, $blob); // blob > 16 MB si hiciera falta
        $stmt->execute();
    }
    $stmt->close();

    return ["status" => "ok", "mensaje" => "Fotos guardadas correctamente."];
}

// -------------------------------------------------------------
// FUNCIÓN 14: Get fotos de incidencia
// -------------------------------------------------------------
function obtenerFotosIncidencia($conn, $incidencia_id) {
    $sql = "SELECT foto FROM fotos_incidencia WHERE incidencia_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $incidencia_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $fotos = [];
    while ($row = $res->fetch_assoc()) {
        $fotos[] = ["foto" => base64_encode($row['foto'])];
    }
    return ["status" => "ok", "fotos" => $fotos];
}

// -------------------------------------------------------------
// FUNCIÓN 15: Obtener todas las incidencias con nombre de técnico  
// -------------------------------------------------------------  
function obtenerTodasIncidencias($conn)
{
    $sql = "
        SELECT 
            i.id,
            u.nombre                                   AS usuario,
            i.titulo,
            i.descripcion,
            i.fecha_creacion,
            e.nombre                                   AS estado,
            i.id_tecnico,
            COALESCE(tu.nombre, 'Sin asignar')         AS tecnico
        FROM incidencias i
        LEFT JOIN usuario u  ON i.id_user   = u.id
        LEFT JOIN estado_incidencia e ON i.estado_id = e.id
        LEFT JOIN tecnicos t          ON i.id_tecnico = t.usuario_id
        LEFT JOIN usuario tu          ON t.usuario_id = tu.id
        ORDER BY i.fecha_creacion DESC
    ";

    $result = $conn->query($sql);
    $incidencias = [];
    while ($row = $result->fetch_assoc()) {
        $incidencias[] = $row;
    }
    return $incidencias;
}

// -------------------------------------------------------------
// FUNCIÓN 1X: Obtener estadísticas generales: nº de sensores,nº de sensores activos, valor promedio, última medición
// -------------------------------------------------------------
function obtenerEstadisticas($conn)
{
    $stats = [];

    $result = $conn->query("SELECT COUNT(*) AS total FROM sensor");
    $stats['total_sensores'] = $result->fetch_assoc()['total'];

    $result = $conn->query("SELECT COUNT(*) AS activos FROM usuario_sensor WHERE actual = 1");
    $stats['sensores_activos'] = $result->fetch_assoc()['activos'];

    $result = $conn->query("SELECT MAX(hora) AS ultima_medicion FROM medicion");
    $stats['ultima_medicion'] = $result->fetch_assoc()['ultima_medicion'];

    return ["status" => "ok", "estadisticas" => $stats];
}

// -------------------------------------------------------------
// FUNCIÓN 1X: Obtener promedio de cada tipo de mediciones en un rango geográfico
// Puede que no funcione todavia
// -------------------------------------------------------------
function promedioPorRango($conn, $lat_min, $lat_max, $lon_min, $lon_max)
{
    $sql = "
        SELECT tm.medida, tm.unidad, AVG(m.valor) AS promedio
        FROM medicion m
        INNER JOIN tipo_medicion tm ON m.tipo_medicion_id = tm.id
        WHERE 
            CAST(SUBSTRING_INDEX(m.localizacion, ',', 1) AS DECIMAL(10,6)) BETWEEN ? AND ?
            AND CAST(SUBSTRING_INDEX(m.localizacion, ',', -1) AS DECIMAL(10,6)) BETWEEN ? AND ?
        GROUP BY tm.id, tm.medida, tm.unidad
        ORDER BY tm.medida ASC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("dddd", $lat_min, $lat_max, $lon_min, $lon_max);
    $stmt->execute();
    $result = $stmt->get_result();

    $promedios = [];
    while ($row = $result->fetch_assoc()) {
        $promedios[] = [
            "medida" => $row['medida'],
            "unidad" => $row['unidad'],
            "promedio" => round($row['promedio'], 2)
        ];
    }

    return [
        "status" => "ok",
        "rango" => [
            "lat_min" => $lat_min,
            "lat_max" => $lat_max,
            "lon_min" => $lon_min,
            "lon_max" => $lon_max
        ],
        "promedios" => $promedios
    ];
}

// -------------------------------------------------------------
// FUNCIÓN 15: Obtener promedio de cada tipo de mediciones en un rango geográfico
// Puede que no funcione todavia
// -------------------------------------------------------------
function modificarDatos($conn, $data){
    if (!isset($data['id'])) {
        return ["status" => "error", "mensaje" => "Falta el ID del usuario"];
    }

    $id = $data['id'];

    // Obtenemos los valores enviados (si no vienen, no se modifican)
    $nombre    = $data['nombre']    ?? null;
    $apellidos = $data['apellidos'] ?? null;
    $correo    = $data['gmail']    ?? null;
    $password  = $data['password']  ?? null;

    // Construimos SQL dinámico según lo que llegó
    $campos = [];
    $params = [];
    $tipos  = "";

    if ($nombre !== null) {
        $campos[] = "nombre = ?";
        $params[] = $nombre;
        $tipos   .= "s";
    }

    if ($apellidos !== null) {
        $campos[] = "apellidos = ?";
        $params[] = $apellidos;
        $tipos   .= "s";
    }

    if ($correo !== null) {
        $campos[] = "gmail = ?";
        $params[] = $correo;
        $tipos   .= "s";
    }

    if ($password !== null) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $campos[] = "password = ?";
        $params[] = $hash;
        $tipos   .= "s";
    }

    if (empty($campos)) {
        return ["status" => "error", "mensaje" => "No hay datos para actualizar"];
    }

    // SQL final
    $sql = "UPDATE usuario SET " . implode(", ", $campos) . " WHERE id = ?";
    $params[] = $id;
    $tipos   .= "i";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($tipos, ...$params);

    if (!$stmt->execute()) {
        return ["status" => "error", "mensaje" => $conn->error];
    }

    // -------------------------------
    //   OBTENER DATOS ACTUALIZADOS
    // -------------------------------
    $sqlUser = "SELECT id, nombre, apellidos, gmail AS correo FROM usuario WHERE id = ?";
    $stmtUser = $conn->prepare($sqlUser);
    $stmtUser->bind_param("i", $id);
    $stmtUser->execute();
    $resUser = $stmtUser->get_result();
    $usuarioActualizado = $resUser->fetch_assoc();

    return [
        "status" => "ok",
        "mensaje" => "Usuario actualizado correctamente",
        "usuario" => $usuarioActualizado
    ];
}