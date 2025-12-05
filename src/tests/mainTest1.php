<?php

// ------------------------------------------------------------------
// Fichero: mainTest1.php (CORREGIDO)
// Autor: Ferran Sansaloni Prats
// Fecha: 30/10/2025
// ------------------------------------------------------------------
// Descripción:
//   Script para probar la lógica de negocio con la función registrarUsuario.
//     1. Inserta un usuario de prueba pasando un array de datos.
//     2. Recupera los usuarios con el correo de prueba.
//     3. Muestra el contenido recuperado en formato JSON.
//     4. Limpieza: elimina el usuario de prueba para mantener la idempotencia.
// ------------------------------------------------------------------

// ------------------------------------------------------------------
// INCLUDES NECESARIOS
// ------------------------------------------------------------------
include '../api/conexion.php';
include '../api/logicaNegocio.php';

$conn = abrirServidor();

// -------------------------
// TEST 1: Insertar usuario
// -------------------------
echo "Test 1: Guardar usuario --> \t";

// email que usamos para el test
$emailTest = "esteeselcorreoparaeltest@gmail.com";

$datosUsuario = [
    'nombre'    => 'NombreTest',
    'apellidos' => 'ApellidosTest',
    'gmail'     => $emailTest,
    'password'  => '1234PruebaSegura',
];

// **2. Llamamos al metodo registrarUsuario con los 2 parámetros correctos: $conn y $datosUsuario**
// La función devuelve un array ['status' => 'ok'/'error', 'mensaje' => '...']
$resultado = registrarUsuario($conn, $datosUsuario);

if ($resultado['status'] === 'ok') {
    // Todo bien
    echo "Usuario insertado correctamente.\n";
    echo "Mensaje: **" . $resultado['mensaje'] . "**\n\n";
} else {
    // Error
    die("Error al insertar el usuario.\nMensaje: **" . $resultado['mensaje'] . "**\n\n");
}

// =========================
// TEST 2: Leer datos del usuario
// =========================
echo "Test 2: Recoger datos del usuario --> \n";

// Ejecutamos la consulta
$stmt = $conn->prepare("SELECT * FROM usuario WHERE gmail = ?");
$stmt->bind_param("s", $emailTest);
$stmt->execute();
$result = $stmt->get_result();

// Lista para los usuarios con el correo de prueba
$usuarios = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Añadimos los usuarios encontrados en la lista
        $usuarios[] = $row;
    }
    // Todo bien
    echo "Usuario(s) recuperado(s): **" . count($usuarios) . "**\n\n";
} else {
    // Error
    echo "No se han recuperado usuarios.\n\n";
}

// =========================
// TEST 3: Mostrar contenido
// =========================
// Mostramos los datos
echo "Test 3: Mostrar datos --> \n";
echo json_encode($usuarios, JSON_PRETTY_PRINT);

echo "\n\n";
// =========================
// TEST 4: Limpieza
// =========================
echo "Test 4: Eliminar el dato insertado (idempotente) --> \n";
// Si hay alguna usuario con ese correo electrónico que se elimine
if (!empty($usuarios)) {
    $delStmt = $conn->prepare("DELETE FROM usuario WHERE gmail = ?");
    $delStmt->bind_param("s", $emailTest);

    if ($delStmt->execute()) {
        echo "Usuario eliminado correctamente.\n";
    } else {
        echo "Error al eliminar usuario: " . $conn->error . "\n";
    }
} else {
    echo "No hay usuario para eliminar.\n";
}

$conn->close();

// ------------------------------------------------------------------
// ------------------------------------------------------------------
// ------------------------------------------------------------------
// ------------------------------------------------------------------
?>