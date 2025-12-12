<?php
// ------------------------------------------------------------------
// Fichero: index.php
// Autor: Manuel
// Coautor: Pablo
// Fecha: 30/10/2025
// ------------------------------------------------------------------
// Descripción:
//  Punto de entrada de la API REST. Gestiona las peticiones según
//  el método HTTP y delega en las funciones de logicaNegocio.php.
// ------------------------------------------------------------------

// ================= DEBUG  =================
// error_reporting(E_ALL);
// ini_set('display_errors', 1);
// ini_set('log_errors', 1);
// ==========================================

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// -------------------- INCLUSIONES --------------------
// Conexión a la base de datos
require_once(__DIR__ . '/conexion.php');     

// Lógica general
require_once(__DIR__ . '/logicaNegocio.php'); 

// Funciones individuales dentro del directorio logicaNegocio/
foreach (glob(__DIR__ . "/logicaNegocio/*.php") as $file) {
    require_once($file);
}

// Abrimos conexión
$conn = abrirServidor();

// -------------------- LECTURA DEL BODY --------------------
$rawBody = '';
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST' || $method === 'PUT') {
    $rawBody = file_get_contents("php://input");
    error_log("BODY CRUDO recibido: " . $rawBody);

    $input = json_decode($rawBody, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON inválido: " . json_last_error_msg());
        http_response_code(400);
        echo json_encode(["status" => "error", "mensaje" => "JSON inválido"]);
        exit;
    }

    if (!is_dir("logs")) mkdir("logs");
    file_put_contents("logs/last_request.json", $rawBody);
} else {
    $input = $_GET;
}

// -------------------- PROCESAMIENTO --------------------
switch ($method) {

    case "POST":
        $accion = $input['accion'] ?? null;
        switch ($accion) {
            case "registrarUsuario":          echo json_encode(registrarUsuario($conn, $input)); break;
            case "login":                     echo json_encode(loginUsuario($conn, $input['gmail'], $input['password'])); break;
            case "guardarMedicion":           echo json_encode(guardarMedicion($conn, $input)); break;
            case "crearTipoMedicion":         echo json_encode(crearTipoMedicion($conn, $input)); break;
            case "crearSensorYRelacion":      echo json_encode(crearSensorYRelacion($conn, $input)); break;
            case "activarUsuario":            echo json_encode(activarUsuario($conn, $input['token'])); break;
            case "finalizarRelacionSensor":   echo json_encode(marcarSensorConProblemas($conn, $input)); break;
            case "reactivarSensor":           echo json_encode(reactivarSensor($conn, $input)); break;
            case "actualizarUsuario":         echo json_encode(actualizarUsuario($conn, $input)); break;
            case "cerrarIncidencia":          echo json_encode(cerrarIncidencia($conn, $input)); break;
            case "crearIncidencia":           echo json_encode(crearIncidencia($conn, $input)); break;
            case "guardarFotoIncidencia":     echo json_encode(guardarFotoIncidencia($conn, $input)); break;
            case "modificarDatos":            echo json_encode(modificarDatos($conn, $input)); break;
            case "guardarDistanciaHoy":       echo json_encode(guardarDistanciaHoy($conn, $input)); break;
            case "actualizarEstadoIncidencia": echo json_encode(actualizarEstadoIncidencia($conn, $input)); break;
            case "asignarmeTecnicoIncidencia": echo json_encode(asignarTecnicoIncidencia($conn, $input)); break;
            case "guardarFotoPerfil":         echo json_encode(guardarFotoPerfil($conn, $input)); break;
            case "asignarTecnico":            echo json_encode(asignarTecnico($conn, $input['usuario_id'])); break;
            case "quitarTecnico":             echo json_encode(quitarTecnico($conn, $input['usuario_id'])); break;
            case "asignarAdministrador":      echo json_encode(asignarAdministrador($conn, $input['usuario_id'])); break;
            case "quitarAdministrador":       echo json_encode(quitarAdministrador($conn, $input['usuario_id'])); break;
            case "sumarPuntos":               echo json_encode(sumarPuntosUsuario($conn, $input)); break;
            case "canjearRecompensa":         echo json_encode(canjearRecompensa($conn, $input)); break;
            case "marcarSensorSinProblemas":  echo json_encode(sensorSinProblemas($conn, $input)); break;
            case "marcarSensorConProblemas":  echo json_encode(sensorConProblemas($conn, $input)); break;
            default:                          echo json_encode(["status" => "error", "mensaje" => "Acción POST no reconocida."]); break;
        }
        break;

    case "GET":
        $accion = $_GET['accion'] ?? null;
        switch ($accion) {
            case "getMediciones":          echo json_encode(obtenerMediciones($conn)); break;
            case "getIncidenciasActivas":  echo json_encode(obtenerIncidenciasActivas($conn)); break;
            case "getEstadisticas":        echo json_encode(obtenerEstadisticas($conn)); break;
            case "getPromedioDeRango":
                $lat_min = floatval($_GET['lat_min'] ?? 0);
                $lat_max = floatval($_GET['lat_max'] ?? 0);
                $lon_min = floatval($_GET['lon_min'] ?? 0);
                $lon_max = floatval($_GET['lon_max'] ?? 0);
                echo json_encode(promedioPorRango($conn, $lat_min, $lat_max, $lon_min, $lon_max));
                break;
            case "getTodasIncidencias":    echo json_encode(obtenerTodasIncidencias($conn)); break;
            case "getFotosIncidencia":     echo json_encode(obtenerFotosIncidencia($conn, $_GET['incidencia_id'])); break;
            case "getHistorialDistancias": echo json_encode(getHistorialDistancias($conn, $_GET)); break;
            case "getDistanciaFecha":      echo json_encode(getDistanciaFecha($conn, $_GET)); break;
            case "getIncidenciaXId":
                $id = intval($_GET['id'] ?? 0);
                $row = obtenerIncidenciaXId($conn, $id);
                echo json_encode($row ?: ["status" => "error", "mensaje" => "Incidencia no encontrada"]);
                break;
            case "getUsuarioXId":
                $id = intval($_GET['id'] ?? 0);
                if ($id === 0) {
                    http_response_code(400);
                    echo json_encode(["status" => "error", "mensaje" => "ID de usuario no válido"]);
                    exit;
                }
                $row = obtenerUsuarioXId($conn, $id);
                echo json_encode($row ?: ["status" => "error", "mensaje" => "Usuario no encontrado"]);
                break;
            case "esTecnico":             $id = intval($_GET['id'] ?? 0); echo json_encode(["es_tecnico" => esTecnico($conn, $id)]); break;
            case "esAdministrador":       $id = intval($_GET['id'] ?? 0); echo json_encode(["es_admin" => esAdministrador($conn, $id)]); break;
            case "getEstadosIncidencia":  echo json_encode(obtenerEstadosIncidencia($conn)); break;
            case "getFotoPerfil":         echo json_encode(obtenerFotoPerfil($conn, $input['usuario_id'])); break;
            case "getSensoresDeUsuario":  $id = intval($_GET['id'] ?? 0); echo json_encode(obtenerSensoresDeUsuario($conn, $id)); break;
            case "getSensorXId": 
                $id = intval($_GET['id'] ?? 0);
                if ($id === 0) { http_response_code(400); echo json_encode(["status" => "error", "mensaje" => "ID de sensor no válido"]); exit; }
                $row = obtenerSensorXId($conn, $id); 
                echo json_encode($row ?: ["status" => "error", "mensaje" => "Sensor no encontrado"]); 
                break;
            case "getObtenerSensoresUsuario":
                echo json_encode(obtenerListaSensores($conn, $_GET['usuario_id'])); 
                break;
            default: echo json_encode(["status" => "error", "mensaje" => "Acción GET no reconocida."]); break;
        }
        break;

    default:
        echo json_encode(["status" => "error", "mensaje" => "Método HTTP no soportado."]);
        break;
}

// Cerramos conexión
$conn->close();
?>
