<?php
// ------------------------------------------------------------------
// Fichero: logicaNegocio.php
// Autor: Manuel
// Coautor: Pablo
// Fecha: 5/12/2025
// ------------------------------------------------------------------
// Descripción:
//  Aquí se definen todas las funciones lógicas que maneja la API.
//  Cada función se encarga de interactuar con la base de datos y
//  devolver los resultados al archivo index.php.  
// ------------------------------------------------------------------


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