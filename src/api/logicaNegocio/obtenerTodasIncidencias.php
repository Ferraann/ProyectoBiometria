<?php
// ------------------------------------------------------------------
// Fichero: obtenerTodasIncidencias.php
// Autor: Manuel
// Fecha: 5/12/2025
// ------------------------------------------------------------------
// Descripción:
//  Función para obtener todas las incidencias con información
//  completa: descripción, IDs, nombres de usuario/técnico/estado.
// ------------------------------------------------------------------

function obtenerTodasIncidencias($conn)
{
    $sql = "
        SELECT 
            i.id,
            i.titulo,
            i.descripcion,
            i.fecha_creacion,
            i.fecha_finalizacion,
            i.id_user,
            u.nombre AS usuario,
            i.id_tecnico,
            COALESCE(tu.nombre, 'Sin asignar') AS tecnico,
            i.estado_id,
            e.nombre AS estado,
            i.id_sensor,
            COALESCE(s.nombre, CONCAT('Sensor #', i.id_sensor)) AS nombre_sensor,
            s.mac AS mac_sensor,
            s.problema AS sensor_con_problema

        FROM incidencias i
        LEFT JOIN usuario u           ON i.id_user = u.id
        LEFT JOIN tecnicos t          ON i.id_tecnico = t.usuario_id
        LEFT JOIN usuario tu          ON t.usuario_id = tu.id
        LEFT JOIN estado_incidencia e ON i.estado_id = e.id
        LEFT JOIN sensor s            ON i.id_sensor = s.id
        ORDER BY i.fecha_creacion DESC
    ";

    $result = $conn->query($sql);
    
    if (!$result) {
        return [];
    }
    
    $incidencias = [];
    while ($row = $result->fetch_assoc()) {
        $incidencias[] = $row;
    }
    
    return $incidencias;
}
?>