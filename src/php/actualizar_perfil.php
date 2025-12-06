<?php
session_start();











// DEBUG: INICIO
error_log("POST data: " . print_r($_POST, true)); // Verifica otros campos
error_log("FILES data: " . print_r($_FILES, true)); // MUESTRA EL CONTENIDO DE FILES

// Si $_FILES está vacío, muestra un error.
if (empty($_FILES) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("WARNING: La matriz \$FILES está vacía. Causa probable: límite de tamaño de POST.");
}
// DEBUG: FIN
















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
















// --- MANEJO DE LA SUBIDA DE ARCHIVO DE PERFIL ---

if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {

    $fileTmpPath = $_FILES['profile_image']['tmp_name'];
    $fileName = $_FILES['profile_image']['name'];
    $fileSize = $_FILES['profile_image']['size'];
    $fileType = $_FILES['profile_image']['type'];
    $fileNameCmps = explode(".", $fileName);
    $fileExtension = strtolower(end($fileNameCmps));

    // 1. Definir la ubicación y el nombre único (por seguridad)
    $uploadFileDir = __DIR__ . '/../img/perfiles/';
    $nuevoNombreArchivo = $id . '_' . time() . '.' . $fileExtension; // userID_timestamp.ext
    $dest_path = $uploadFileDir . $nuevoNombreArchivo;

    // 1. Si no existe el directorio perfiles, lo creo.
    if (!is_dir($uploadFileDir)) {
        // Tenta crear el directorio con permisos 0777 (lectura/escritura/ejecución para todos)
        // El 'true' es para crear directorios anidados si fuera necesario.
        if (!mkdir($uploadFileDir, 0777, true)) {
            $_SESSION['mensaje_error'] = "Error interno: El directorio de destino no existe y no se pudo crear.";
            header("Location: $mensaje_destino"); exit;
        }
    }
    // ------------------------------------

    // 2. Validación de seguridad (ej. tamaño máximo, solo imágenes)
    if ($fileSize > 5000000) { // 5MB
        $_SESSION['mensaje_error'] = "El archivo es demasiado grande (máx. 5MB).";
        header("Location: $mensaje_destino");
        exit;
    }

    $allowedfileExtensions = array('jpg', 'gif', 'png', 'jpeg', 'webp');
    if (!in_array($fileExtension, $allowedfileExtensions)) {
        $_SESSION['mensaje_error'] = "Formato de imagen no permitido.";
        header("Location: $mensaje_destino");
        exit;
    }

    // 3. Mover el archivo subido
    if(move_uploaded_file($fileTmpPath, $dest_path)) {
        // Éxito: Guardamos la nueva ruta de la imagen en $data
        $data['foto_ruta'] = $dest_path;

        // NOTA: Debes asegurarte de que tu función actualizarUsuario() sepa manejar
        // un campo nuevo como 'foto_ruta' y lo guarde en la DB.

       ;

    } else {
        $_SESSION['mensaje_error'] = "Hubo un error al guardar la imagen en el servidor.";
        header("Location: $mensaje_destino"); exit;
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

// Comprobar si hubo un archivo subido
$archivo_subido = isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK;









/*
if(!$archivo_subido) {
    $_SESSION['mensaje_error'] = "Foto no subida!";
    header("Location: $mensaje_destino");
    exit;
}
*/













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
