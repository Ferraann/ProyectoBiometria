<?php
session_start();
require_once "../api/conexion.php";

foreach (glob(__DIR__ . "/../api/logicaNegocio/*.php") as $file) {
    require_once $file;
}

$conn = abrirServidor();

// Donde se mostrara el mensaje
$mensaje_destino = "../html/perfil_cliente.php";

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.html");
    exit;
}

// Coge el id de la sesión y lo guarda en $data que posteriormente se enviará al metodo de la logica de negocio
$id = $_SESSION['usuario_id'];
$data = ["id" => $id];

// --- ACTUALIZACIÓN DE DATOS (Después de la verificación) ---

// ----------------------------------------------------------------------------------------
// 1. Nombre
// ----------------------------------------------------------------------------------------

// Si no esta vacío y es diferente al de la sesion ...
if (!empty($_POST['nombre'])) {
    // ... se guarda en $data, pero separado, por un lado el nombre y por el otro el apellido
    $partes = explode(" ", $_POST['nombre'], 2);
    $data['nombre'] = $partes[0];
    $data['apellidos'] = $partes[1] ?? "";
} else {
    // 2A. VALIDACIÓN DE CAMPO DE NOMBRE COMPLETO. EL NOMBRE ESTÁ VACIO
    if (isset($_POST['nombre'])) {
        $_SESSION['mensaje_error'] = "El nombre está vacío. Por favor, ingrese un nombre y apellido.";
        header("Location: $mensaje_destino");
        exit;
    }
    // ------------------------------------
}

//2B. VALIDACIÓN DE CAMPO DE NOMBRE COMPLETO. EL NOMBRE ES IGUAL AL QUE TENÍA
if ($_POST['nombre'] == ($_SESSION['usuario_nombre']." ".$_SESSION['usuario_apellidos'])) {
    $_SESSION['mensaje_error'] = "El nombre y apellidos introducidos son idénticos a los actuales. No se ha realizado ninguna modificación.";
    header("Location: $mensaje_destino");
    exit;
}
// ------------------------------------

// ----------------------------------------------------------------------------------------
// 2. Gmail
// ----------------------------------------------------------------------------------------

if (!empty($_POST['gmail'])) {
    // 2A. VALIDACIÓN DE CORREOS COINCIDENTES
    if ($_POST['gmail'] !== $_POST['repetir-correo']) {
        $_SESSION['mensaje_error'] = "Los correos no coinciden.";
        header("Location: $mensaje_destino");
        exit;
    }
    // ------------------------------------

    // 2B. VALIDACIÓN DEL DOMINIO
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
    // ------------------------------------

    // Si la validación del dominio pasa, se añade a $data
    $data['gmail'] = $gmail_nuevo;
}


// ----------------------------------------------------------------------------------------
// 3. Contraseña
// ----------------------------------------------------------------------------------------

// --- INICIO DE LA VERIFICACIÓN DE SEGURIDAD ---
$passwordAntigua = $_POST['contrasena-antigua'];

// Si se va a cambiar la contraseña, se DEBE proporcionar la contraseña antigua.
$requiereVerificacion = !empty($_POST['contrasena']);

if ($requiereVerificacion) {
    // 3A. CONTRASEÑAS COINCIDENTES
    if ($_POST['contrasena'] !== $_POST['repetir-contrasena']) {
        $_SESSION['mensaje_error'] = "Las contraseñas nuevas no coinciden.";
        header("Location: $mensaje_destino");
        exit;
    }
    // ------------------------------------

    // 3B. CONTRASEÑAS CON MÁS DE 8 CARACTERES
    if(strlen($_POST['contrasena']) < 8) {
        $_SESSION['mensaje_error'] = "La contraseña debe tener al menos 8 caracteres.";
        header("Location: $mensaje_destino");
        exit;
    }
    // ------------------------------------

    // 3C. CONTRASEÑA CON AL MENOS UN NUMERO, UNA MAYÚSCULA Y UN CARACTER ESPECIAL
    // Contraseña Nueva del POST
    $passwordNueva = $_POST['contrasena'];

    // 1. Verificar si contiene al menos un número (/\d/)
    $tieneNum = preg_match('/\d/', $passwordNueva);

    // 2. Verificar si contiene al menos una mayúscula (/[A-Z]/)
    $tieneMay = preg_match('/[A-Z]/', $passwordNueva);

    // 3. Verificar si contiene al menos un carácter especial
    // Nota: Solo se incluyen los caracteres que has definido en tu JS.
    $tieneEsp = preg_match('/[!@#$%^&*(),.?":{}|<>]/', $passwordNueva);

    // Si NO tiene número O NO tiene mayúscula O NO tiene especial, fallar.
    if (!$tieneNum || !$tieneMay || !$tieneEsp) {
        $_SESSION['mensaje_error'] = "La contraseña debe contener al menos un número, una mayúscula y un carácter especial.";
        header("Location: $mensaje_destino");
        exit;
    }
    // ------------------------------------

    // 3B. CAMPO DE CONTRASEÑA ANTIGUO VACÍO
    if (empty($passwordAntigua)) {
        $_SESSION['mensaje_error'] = "Debe introducir la contraseña antigua para establecer una nueva.";
        header("Location: $mensaje_destino");
        exit;
    }
    // ------------------------------------

    // Obtener el hash almacenado
    $stmt = $conn->prepare("SELECT password FROM usuario WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();

    // 3C. ERROR DE SEGURIDAD. El ID existe en la sesión, pero no en la DB.
    if ($res->num_rows === 0) {
        // Usuario logeado no encontrado
        $_SESSION['mensaje_error'] = "Error de autenticación interna.";
        header("Location: $mensaje_destino");
        exit;
    }

    // Guardar la contraseña del usaurio en $hashAlmacenado
    $user = $res->fetch_assoc();
    $hashAlmacenado = $user['password'];
    $stmt->close();

    // Verificar la contraseña antigua
    if (!password_verify($passwordAntigua, $hashAlmacenado)) {
        $_SESSION['mensaje_error'] = "La contraseña antigua es incorrecta. Por favor, inténtelo de nuevo.";
        header("Location: $mensaje_destino");
        exit;
    }

    // Hasheamos la contraseña nueva antes de enviarla a la lógica de negocio.
    $data['password'] = password_hash($_POST['contrasena'], PASSWORD_DEFAULT);

    // Si llegamos aquí, la contraseña antigua es correcta.
}

// ----------------------------------------------------------------------------------------
// 4. ¿Hay algo que modificar?
// ----------------------------------------------------------------------------------------

// No hay nada que modificar? --> Error
if (count($data) === 1 && isset($data['id'])) {
    $_SESSION['mensaje_error'] = "No se detectaron modificaciones para guardar.";
    header("Location: $mensaje_destino");
    exit;
}

// ----------------------------------------------------------------------------------------
// 4. Llamar a la lógica de negocio
// ----------------------------------------------------------------------------------------

// Guardamos el resultado de la llamada a acutalizarUsuario (metodo de la lógica de negocio)
$resultado = actualizarUsuario($conn, $data);

// Actualizar variables de sesión si todo salió bien
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
    // Mostrar mensajes de error o de éxito
    $_SESSION['mensaje_exito'] = "Perfil actualizado correctamente.";
}

// Redirigir de vuelta al perfil
header("Location: ../html/perfil_cliente.php");
exit;

// ----------------------------------------------------------------------------------------
// ----------------------------------------------------------------------------------------
// ----------------------------------------------------------------------------------------
// ----------------------------------------------------------------------------------------
