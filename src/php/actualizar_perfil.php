<?php
session_start();
require_once "../api/conexion.php";

foreach (glob(__DIR__ . "/../api/logicaNegocio/*.php") as $file) {
    require_once $file;
}

$conn = abrirServidor();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.html");
    exit;
}

$id = $_SESSION['usuario_id'];
$data = ["id" => $id];

// --- ACTUALIZACIÓN DE DATOS (Después de la verificación) ---

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

// --- INICIO DE LA VERIFICACIÓN DE SEGURIDAD ---
$passwordAntigua = $_POST['contrasena-antigua'] ?? '';

// Si se va a cambiar la contraseña, se DEBE proporcionar la contraseña antigua.
$requiereVerificacion = !empty($_POST['password']);

if ($requiereVerificacion) {

    // Si las contraseñas no coinciden --> error
    if ($_POST['password'] !== $_POST['repetir-contrasena']) {
        die("Las contraseñas no coinciden.");
    }

    // Campo de la contraseña antigua vacío
    if (empty($passwordAntigua)) {
        die("Debe introducir la contraseña antigua para establecer una nueva.");
    }
    // ------------------------------------

    // 1. Obtener el hash almacenado
    $stmt = $conn->prepare("SELECT password FROM usuario WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        // Usuario logeado no encontrado
        die("Error de autenticación interna.");
    }

    $user = $res->fetch_assoc();
    $hashAlmacenado = $user['password'];
    $stmt->close();

    // 2. Verificar la contraseña antigua
    if (!password_verify($passwordAntigua, $hashAlmacenado)) {
        die("La contraseña antigua es incorrecta. Por favor, inténtelo de nuevo.");
    }

    // Hasheamos la contraseña nueva antes de enviarla a la lógica de negocio.
    $data['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Si llegamos aquí, la contraseña antigua es correcta.
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
}

// 6. Redirigir de vuelta al perfil
header("Location: ../html/perfil_cliente.php");
exit;
