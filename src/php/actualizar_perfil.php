<?php
session_start();
require_once "../api/conexion.php";
require_once "../api/logicaNegocio.php"; // donde está actualizarUsuario()

$conn = abrirServidor();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.html");
    exit;
}

$id = $_SESSION['usuario_id'];
$data = ["id" => $id];

// 1. Nombre
if (!empty($_POST['nombre']) && $_POST['nombre'] != ($_SESSION['usuario_nombre']." ".$_SESSION['usuario_apellidos'])) {
    $partes = explode(" ", $_POST['nombre'], 2);
    $data['nombre'] = $partes[0];
    $data['apellidos'] = $partes[1] ?? "";
}

// 2. Gmail
if (!empty($_POST['gmail'])) {
    if ($_POST['gmail'] !== $_SESSION['usuario_correo']) {
        if ($_POST['gmail'] !== $_POST['repetir-correo']) {
            die("Los correos no coinciden.");
        }
        $data['gmail'] = $_POST['gmail'];
    }
}

// 3. Contraseña
if (!empty($_POST['password'])) {
    if ($_POST['password'] !== $_POST['repetir-contrasena']) {
        die("Las contraseñas no coinciden.");
    }
    else if ($data['password'] !== $_POST['contrasena-antigua']) {
        die("La contraseña antigua no coincide.");
    }

    $data['password'] = $_POST['password'];
}

// 4. Llamar a la lógica de negocio
$resultado = actualizarUsuario($conn, $data);

// 5. Actualizar variables de sesión si todo salió bien
if ($resultado["status"] === "ok") {

    if (isset($data['nombre'])) {
        $_SESSION['usuario_nombre'] = $data['nombre'];
    }
    if (isset($data['apellidos'])) {
        $_SESSION['usuario_apellidos'] = $data['apellidos'];
    }
    if (isset($data['gmail'])) {
        $_SESSION['usuario_correo'] = $data['gmail'];
    }
    if (isset($data['password'])) {
        $_SESSION['usuario_password'] = $_POST['password']; // sin hash
    }
}

// 6. Redirigir de vuelta al perfil
header("Location: ../html/perfil_cliente.php");
exit;
