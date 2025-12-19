<?php
/**
 * @file actualizar_perfil.php
 * @brief Script de control para la actualización de datos del perfil de usuario.
 * @details Procesa cambios de nombre, correo, contraseña y foto de perfil.
 * Implementa validaciones de seguridad, dominio de correo y requisitos de contraseña.
 * @author Ferran
 * @date 19/12/2025
 */

session_start();

/** * @name DebugLog
 * @{
 * Registros de depuración para monitorizar datos entrantes.
 */
error_log("POST data: " . print_r($_POST, true));
error_log("FILES data: " . print_r($_FILES, true));
/** @} */

require_once "../api/conexion.php";

/**
 * @brief Carga dinámica de todos los archivos de lógica de negocio.
 */
foreach (glob(__DIR__ . "/../api/logicaNegocio/*.php") as $file) {
    require_once $file;
}

/** @var mysqli $conn Instancia de conexión a la base de datos. */
$conn = abrirServidor();

/** @var string $mensaje_destino Ruta de redirección tras el procesamiento. */
$mensaje_destino = "../html/perfil_cliente.php";

/**
 * @brief Control de acceso: Redirige si no hay sesión activa.
 */
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.html");
    exit;
}

/** * @var int $id Identificador único del usuario obtenido de la sesión.
 * @var array $data Contenedor de campos modificados que se enviará a la lógica de negocio.
 */
$id = $_SESSION['usuario_id'];
$data = ["id" => $id];

/**
 * --- VERIFICACIÓN DE EXISTENCIA DEL USUARIO CRÍTICA ---
 * @note Asegura que el usuario no haya sido borrado de la DB mientras mantenía la sesión.
 */
$check_user_stmt = $conn->prepare("SELECT id FROM usuario WHERE id = ?");
$check_user_stmt->bind_param("i", $id);
$check_user_stmt->execute();
$check_user_res = $check_user_stmt->get_result();

if ($check_user_res->num_rows === 0) {
    $_SESSION['mensaje_error'] = "Error de autenticación: El usuario asociado a la sesión ya no existe.";
    $check_user_stmt->close();
    header("Location: $mensaje_destino");
    exit;
}
$check_user_stmt->close();

// ----------------------------------------------------------------------------------------
// MANEJO DE FOTO BLOB (Base64)
// ----------------------------------------------------------------------------------------

/**
 * @section FotoPerfil
 * Procesamiento de imagen en formato Base64.
 */
if (!empty($_POST['profile_image_base64'])) {

    $foto_data = [
        'usuario_id' => $id,
        'foto' => $_POST['profile_image_base64']
    ];

    /** @var array $resultado_foto Respuesta de la función guardarFotoPerfil. */
    $resultado_foto = guardarFotoPerfil($conn, $foto_data);

    if ($resultado_foto['status'] === 'ok') {
        $data['foto_actualizada'] = true;
        $_SESSION['mensaje_exito'] = "Foto de perfil actualizada correctamente.";
    } else {
        $_SESSION['mensaje_error'] = "Error al guardar la foto: " . $resultado_foto['mensaje'];
        header("Location: $mensaje_destino");
        exit;
    }
}

// ----------------------------------------------------------------------------------------
// 1. Nombre
// ----------------------------------------------------------------------------------------

/**
 * @section ActualizacionNombre
 * Compara el nombre completo enviado con el almacenado en sesión para detectar cambios.
 */
$nombre_anterior = $_SESSION['usuario_nombre']." ".$_SESSION['usuario_apellidos'];

if (isset($_POST['nombre'])) {
    if (empty($_POST['nombre'])) {
        $_SESSION['mensaje_error'] = "El nombre está vacío. Por favor, ingrese un nombre y apellido.";
        header("Location: $mensaje_destino");
        exit;
    }

    if ($_POST['nombre'] !== $nombre_anterior) {
        $partes = explode(" ", $_POST['nombre'], 2);
        $data['nombre'] = $partes[0];
        $data['apellidos'] = $partes[1] ?? "";
    }
}

// ----------------------------------------------------------------------------------------
// 2. Gmail
// ----------------------------------------------------------------------------------------

/**
 * @section ValidacionCorreo
 * Valida coincidencia, dominios permitidos y duplicidad en la base de datos.
 */
if (!empty($_POST['gmail'])) {
    if ($_POST['gmail'] !== $_POST['repetir-correo']) {
        $_SESSION['mensaje_error'] = "Los correos no coinciden.";
        header("Location: $mensaje_destino");
        exit;
    }

    $gmail_nuevo = $_POST['gmail'];

    // Filtro de dominios permitidos
    if (
        strpos($gmail_nuevo, '@gmail.') === false &&
        strpos($gmail_nuevo, '@yahoo.') === false &&
        strpos($gmail_nuevo, '@outlook.') === false &&
        strpos($gmail_nuevo, '@hotmail.') === false &&
        strpos($gmail_nuevo, '@upv.edu.es') === false
    ) {
        $_SESSION['mensaje_error'] = "El dominio del nuevo correo no está permitido.";
        header("Location: $mensaje_destino");
        exit;
    }

    if ($gmail_nuevo !== $_SESSION['usuario_correo']) {
        $stmt_check = $conn->prepare("SELECT id FROM usuario WHERE gmail = ?");
        $stmt_check->bind_param("s", $gmail_nuevo);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $_SESSION['mensaje_error'] = "El correo electrónico introducido ya está asociado a otra cuenta.";
            $stmt_check->close();
            header("Location: $mensaje_destino");
            exit;
        }
        $stmt_check->close();
    }

    $data['gmail'] = $gmail_nuevo;
}

// ----------------------------------------------------------------------------------------
// 3. Contraseña
// ----------------------------------------------------------------------------------------

/**
 * @section ValidacionContrasena
 * Implementa requisitos de complejidad (Longitud, Mayúsculas, Números, Especiales)
 * y verificación de la contraseña antigua mediante hash.
 */
$passwordAntigua = $_POST['contrasena-antigua'];
$requiereVerificacion = !empty($_POST['contrasena']);

if ($requiereVerificacion) {
    if ($_POST['contrasena'] !== $_POST['repetir-contrasena']) {
        $_SESSION['mensaje_error'] = "Las contraseñas nuevas no coinciden.";
        header("Location: $mensaje_destino");
        exit;
    }

    if(strlen($_POST['contrasena']) < 8) {
        $_SESSION['mensaje_error'] = "La contraseña debe tener al menos 8 caracteres.";
        header("Location: $mensaje_destino");
        exit;
    }

    $passwordNueva = $_POST['contrasena'];
    $tieneNum = preg_match('/\d/', $passwordNueva);
    $tieneMay = preg_match('/[A-Z]/', $passwordNueva);
    $tieneEsp = preg_match('/[!@#$%^&*(),.?":{}|<>]/', $passwordNueva);

    if (!$tieneNum || !$tieneMay || !$tieneEsp) {
        $_SESSION['mensaje_error'] = "La contraseña debe contener al menos un número, una mayúscula y un carácter especial.";
        header("Location: $mensaje_destino");
        exit;
    }

    if (empty($passwordAntigua)) {
        $_SESSION['mensaje_error'] = "Debe introducir la contraseña antigua.";
        header("Location: $mensaje_destino");
        exit;
    }

    $stmt = $conn->prepare("SELECT password FROM usuario WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        $_SESSION['mensaje_error'] = "Error de autenticación interna.";
        header("Location: $mensaje_destino");
        exit;
    }

    $user = $res->fetch_assoc();
    $hashAlmacenado = $user['password'];
    $stmt->close();

    if (!password_verify($passwordAntigua, $hashAlmacenado)) {
        $_SESSION['mensaje_error'] = "La contraseña antigua es incorrecta.";
        header("Location: $mensaje_destino");
        exit;
    }

    $data['password'] = password_hash($_POST['contrasena'], PASSWORD_DEFAULT);
}

// ----------------------------------------------------------------------------------------
// 4. Ejecución de Cambios
// ----------------------------------------------------------------------------------------

/**
 * @brief Chequeo final de modificaciones.
 * Si solo contiene el 'id', significa que no hay cambios reales.
 */
if (count($data) === 1) {
    $_SESSION['mensaje_error'] = "No se detectaron modificaciones para guardar.";
    header("Location: $mensaje_destino");
    exit;
}

/** * @brief Llamada a la función de actualización en la capa de persistencia.
 * @see actualizarUsuario()
 */
$resultado = actualizarUsuario($conn, $data);

if ($resultado["status"] === "ok") {
    // Sincronización de la sesión con los nuevos datos
    if (isset($data['nombre'])) $_SESSION['usuario_nombre'] = $data['nombre'];
    if (isset($data['apellidos'])) $_SESSION['usuario_apellidos'] = $data['apellidos'];
    if (isset($data['gmail'])) $_SESSION['usuario_correo'] = $data['gmail'];

    $_SESSION['mensaje_exito'] = "Perfil actualizado correctamente.";
}

header("Location: ../html/perfil_cliente.php");
exit;