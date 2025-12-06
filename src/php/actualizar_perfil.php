<?php
session_start();
require_once "../api/conexion.php";

foreach (glob(__DIR__ . "/../api/logicaNegocio/*.php") as $file) {
    require_once $file;
}

$conn = abrirServidor();

$mensaje_destino = "../html/perfil_cliente.php";

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
} else {
    if (isset($_POST['nombre'])) {
        $_SESSION['mensaje_error'] = "El nombre no puede estar vacío. Por favor, ingrese un nombre y apellido.";
        header("Location: $mensaje_destino");
        exit;
    }
}

// 2. Gmail
if (!empty($_POST['gmail'])) {
    if ($_POST['gmail'] !== $_POST['repetir-correo']) {
        $_SESSION['mensaje_error'] = "Los correos no coinciden.";
        header("Location: $mensaje_destino");
        exit;
    }

    // 2B. VALIDACIÓN DEL DOMINIO (Nuevo código)
    $gmail_nuevo = $_POST['gmail'];

    // Comprueba si el correo NO incluye NINGUNO de los dominios permitidos.
    // Si NO incluye A y NO incluye B y NO incluye C, entonces es inválido.
    if (
        strpos($gmail_nuevo, '@gmail.') === false &&
        strpos($gmail_nuevo, '@yahoo.') === false &&
        strpos($gmail_nuevo, '@outlook.') === false &&
        strpos($gmail_nuevo, '@hotmail.') === false &&
        strpos($gmail_nuevo, '@upv.edu.es') === false
    ) {
        $_SESSION['mensaje_error'] = "El dominio del nuevo correo no está permitido. Por favor, use una dirección de correo válida.";
        header("Location: $mensaje_destino");
        exit;
    }
    // Fin de la validación del dominio.

    // Si la validación del dominio pasa, se añade a $data
    $data['gmail'] = $gmail_nuevo;
}

// 3. Contraseña

// --- INICIO DE LA VERIFICACIÓN DE SEGURIDAD ---
$passwordAntigua = $_POST['contrasena-antigua'];

// Si se va a cambiar la contraseña, se DEBE proporcionar la contraseña antigua.
$requiereVerificacion = !empty($_POST['contrasena']);

if ($requiereVerificacion) {

    // Si las contraseñas no coinciden --> error
    if ($_POST['contrasena'] !== $_POST['repetir-contrasena']) {
        $_SESSION['mensaje_error'] = "Las contraseñas nuevas no coinciden.";
        header("Location: $mensaje_destino");
        exit;
    }

    // Campo de la contraseña antigua vacío
    if (empty($passwordAntigua)) {
        $_SESSION['mensaje_error'] = "Debe introducir la contraseña antigua para establecer una nueva."; // <<< CORREGIDO
        header("Location: $mensaje_destino"); // <<< CORREGIDO
        exit;
    }
    // ------------------------------------

    // 1. Obtener el hash almacenado
    $stmt = $conn->prepare("SELECT password FROM usuario WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        // Usuario logeado no encontrado
        $_SESSION['mensaje_error'] = "Error de autenticación interna.";
        header("Location: $mensaje_destino");
        exit;
    }

    $user = $res->fetch_assoc();
    $hashAlmacenado = $user['password'];
    $stmt->close();

    // 2. Verificar la contraseña antigua
    if (!password_verify($passwordAntigua, $hashAlmacenado)) {
        $_SESSION['mensaje_error'] = "La contraseña antigua es incorrecta. Por favor, inténtelo de nuevo.";
        header("Location: $mensaje_destino");
        exit;
    }

    // Hasheamos la contraseña nueva antes de enviarla a la lógica de negocio.
    $data['password'] = password_hash($_POST['contrasena'], PASSWORD_DEFAULT);

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
    $_SESSION['mensaje_exito'] = "Perfil actualizado correctamente.";
}

// 6. Redirigir de vuelta al perfil
header("Location: ../html/perfil_cliente.php");
exit;
