<?php
session_start();

// DEBUG: INICIO
error_log("POST data: " . print_r($_POST, true)); // Verifica otros campos
error_log("FILES data: " . print_r($_FILES, true)); // MUESTRA EL CONTENIDO DE FILES

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

// --- VERIFICACIÓN DE EXISTENCIA DEL USUARIO CRÍTICA ---
$check_user_stmt = $conn->prepare("SELECT id FROM usuario WHERE id = ?");
$check_user_stmt->bind_param("i", $id);
$check_user_stmt->execute();
$check_user_res = $check_user_stmt->get_result();

if ($check_user_res->num_rows === 0) {
    // Si el ID de la sesión no existe en la tabla principal
    $_SESSION['mensaje_error'] = "Error de autenticación: El usuario asociado a la sesión ya no existe.";
    $check_user_stmt->close();
    header("Location: $mensaje_destino");
    exit;
}
$check_user_stmt->close();
// --------------------------------------------------------


// ----------------------------------------------------------------------------------------
// MANEJO DE FOTO BLOB (Base64)
// ----------------------------------------------------------------------------------------

if (!empty($_POST['profile_image_base64'])) {

    // 1. Preparamos los datos para la función de lógica de negocio (guardarFotoPerfil)
    $foto_data = [
        'usuario_id' => $id,
        'foto' => $_POST['profile_image_base64'] // Contiene la cadena Base64
    ];

    // 2. Llamamos a la función de lógica de negocio
    $resultado_foto = guardarFotoPerfil($conn, $foto_data);

    if ($resultado_foto['status'] === 'ok') {
        // Marcamos la foto como actualizada en el array de datos
        // Usamos este campo como un flag para el chequeo final de cambios
        $data['foto_actualizada'] = true;

        // Añadimos un mensaje de éxito para la UX
        $_SESSION['mensaje_exito'] = "Foto de perfil actualizada correctamente.";

    } else {
        // Error de la base de datos o Base64
        $_SESSION['mensaje_error'] = "Error al guardar la foto: " . $resultado_foto['mensaje'];
        header("Location: $mensaje_destino");
        exit;
    }
}


// --- ACTUALIZACIÓN DE DATOS (Después de la verificación) ---

// ----------------------------------------------------------------------------------------
// 1. Nombre
// ----------------------------------------------------------------------------------------

// Nombre anterior
$nombre_anterior = $_SESSION['usuario_nombre']." ".$_SESSION['usuario_apellidos'];

// 1. Nombre
// Si se envió un nombre...
if (isset($_POST['nombre'])) {

    // Si el campo está vacío, disparamos el error
    if (empty($_POST['nombre'])) {
        $_SESSION['mensaje_error'] = "El nombre está vacío. Por favor, ingrese un nombre y apellido.";
        header("Location: $mensaje_destino");
        exit;
    }

    // Si el nombre es diferente al de la sesión, lo guardamos en $data.
    if ($_POST['nombre'] !== $nombre_anterior) {
        $partes = explode(" ", $_POST['nombre'], 2);
        $data['nombre'] = $partes[0];
        $data['apellidos'] = $partes[1] ?? "";
    }
    // Si el nombre es idéntico, NO hacemos nada y el script continúa.
    // La comprobación de 'No se detectaron modificaciones' se hace al final (Línea 231).
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

    // 2C. VALIDACIÓN DE CORREO DUPLICADO (NUEVA LÓGICA)

    // Solo verificamos si el correo es diferente al actual del usuario.
    if ($gmail_nuevo !== $_SESSION['usuario_correo']) {
        $stmt_check = $conn->prepare("SELECT id FROM usuario WHERE gmail = ?");
        $stmt_check->bind_param("s", $gmail_nuevo);
        $stmt_check->execute();
        $stmt_check->store_result();

        // Si encontramos alguna fila, el correo ya está en uso por otro usuario.
        if ($stmt_check->num_rows > 0) {
            $_SESSION['mensaje_error'] = "El correo electrónico introducido ya está asociado a otra cuenta.";
            $stmt_check->close();
            header("Location: $mensaje_destino");
            exit;
        }
        $stmt_check->close();
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
if (count($data) === 1) {
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
