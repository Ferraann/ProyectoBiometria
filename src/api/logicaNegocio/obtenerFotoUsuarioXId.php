<?php
// ------------------------------------------------------------------
// Fichero: obtenerFotosIncidencias.php
// Autor: Manuel
// Fecha: 7/12/2025
// ------------------------------------------------------------------
// Descripción:
//  Función para obtener la foto asociadas a un usuario.
//  Devuelve la foto en formato base64 codificado.
// ------------------------------------------------------------------

function obtenerFotoPerfil($conn, $usuario_id) {
    $sql = "SELECT foto FROM fotos_perfil_usuario WHERE usuario_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $fotos = [];
    while ($row = $res->fetch_assoc()) {
        $fotos[] = ["foto" => base64_encode($row['foto'])];
    }
    return ["status" => "ok", "fotos" => $fotos];
}