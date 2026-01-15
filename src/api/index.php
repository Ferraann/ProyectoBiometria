<?php
/**
 * @file index.php
 * @brief Punto de entrada unificado (Front Controller) de la API REST.
 * @details Este script actúa como la pasarela principal del sistema. Se encarga de:
 * - Configurar las cabeceras de seguridad y políticas de CORS.
 * - Cargar dinámicamente todos los módulos de lógica de negocio.
 * - Procesar y sanear las entradas JSON/GET según el método HTTP.
 * - Enrutar las peticiones a la función correspondiente y devolver la respuesta en formato JSON.
 * @author Manuel
 * @coauthor Pablo
 * @date 30/10/2025
 */

// ----------------------------------------------------------------------------------------
// 1. DIAGNÓSTICO Y LOGS DE EMERGENCIA
// ----------------------------------------------------------------------------------------

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Forzar registro de errores en un archivo propio
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/debug_api.log'); 

error_log("--- Nueva petición: " . $_SERVER['REQUEST_METHOD'] . " ---");

// ----------------------------------------------------------------------------------------
// 2. CONFIGURACIÓN DE CABECERAS (CORS)
// ----------------------------------------------------------------------------------------

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ----------------------------------------------------------------------------------------
// 3. CARGA DE DEPENDENCIAS Y MÓDULOS
// ----------------------------------------------------------------------------------------

require_once(__DIR__ . '/conexion.php');
error_log("Paso 1: conexion.php cargado");

/** @var mysqli $conn Instancia global de conexión a la base de datos. */
$conn = abrirServidor();
error_log("Paso 2: abrirServidor() ejecutado");

/** * @section CargaModulos 
 * Itera el directorio 'logicaNegocio' e incluye todos los archivos de funciones.
 */
$archivosModulos = glob(__DIR__ . "/logicaNegocio/*.php");
if ($archivosModulos) {
    foreach ($archivosModulos as $file) {
        error_log("Cargando módulo: " . basename($file));
        require_once($file);
    }
}
error_log("Paso 3: Módulos cargados correctamente");

// ----------------------------------------------------------------------------------------
// 4. PROCESAMIENTO DE DATOS DE ENTRADA
// ----------------------------------------------------------------------------------------

$method = $_SERVER['REQUEST_METHOD'];
$input = [];

/** @section Parsing Entradas 
 * Normaliza los datos independientemente de si vienen por URL (GET) o en el cuerpo (POST/PUT).
 */
if ($method === 'POST' || $method === 'PUT') {
    $rawBody = file_get_contents("php://input");
    $input = json_decode($rawBody, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode(["status" => "error", "mensaje" => "JSON inválido o mal formado"]);
        exit;
    }

    /** @note Se guarda registro del último body recibido para tareas de depuración. */
    if (is_dir(__DIR__ . "/logs") && is_writable(__DIR__ . "/logs")) {
    file_put_contents(__DIR__ . "/logs/last_request.json", $rawBody);
    }
} else {
    $input = $_GET;
}

// ----------------------------------------------------------------------------------------
// 5. ENRUTADOR DE ACCIONES (ROUTING)
// ----------------------------------------------------------------------------------------

/** @section Router Distribución de peticiones según el parámetro 'accion'. */
switch ($method) {

    case "POST":
        $accion = $input['accion'] ?? null;
        switch ($accion) {
            case "registrarUsuario":           echo json_encode(registrarUsuario($conn, $input)); break;
            case "login":                     echo json_encode(loginUsuario($conn, $input['gmail'], $input['password'])); break;
            case "guardarMedicion":           echo json_encode(guardarMedicion($conn, $input)); break;
            case "crearTipoMedicion":         echo json_encode(crearTipoMedicion($conn, $input)); break;
            case "crearSensorYRelacion":      echo json_encode(crearSensorYRelacion($conn, $input)); break;
            case "activarUsuario":            echo json_encode(activarUsuario($conn, $input['token'])); break;
            case "actualizarUsuario":         echo json_encode(actualizarUsuario($conn, $input)); break;
            case "cerrarIncidencia":          echo json_encode(cerrarIncidencia($conn, $input)); break;
            case "crearIncidencia":           echo json_encode(crearIncidencia($conn, $input)); break;
            case "guardarFotoIncidencia":     echo json_encode(guardarFotoIncidencia($conn, $input)); break;
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
            case "canjearRecompensa":         echo json_encode(canjearRecompensa($conn, $input));  break;
            case "usarRecompensaFisica":      echo json_encode(marcarRecompensaComoUsada($conn, $input)); break;
            default:                          echo json_encode(["status" => "error", "mensaje" => "Acción POST no reconocida."]); break;
        }
        break;

    case "GET":
        $accion = $_GET['accion'] ?? null;
        switch ($accion) {
            case "getMediciones":          echo json_encode(obtenerMediciones($conn)); break;
            case "getTodasIncidencias":    echo json_encode(obtenerTodasIncidencias($conn)); break;
            case "getFotosIncidencia":     echo json_encode(obtenerFotosIncidencia($conn, $_GET['incidencia_id'])); break;
            case "getHistorialDistancias": echo json_encode(getHistorialDistancias($conn, $_GET)); break;
            case "getDistanciaFecha":      echo json_encode(getDistanciaFecha($conn, $_GET)); break;
            case "getIncidenciaXId":
                $id = intval($_GET['id'] ?? 0);
                echo json_encode(obtenerIncidenciaXId($conn, $id) ?: ["status" => "error", "mensaje" => "Incidencia no encontrada"]);
                break;
            case "getUsuarioXId":
                $id = intval($_GET['id'] ?? 0);
                if ($id === 0) { http_response_code(400); echo json_encode(["status" => "error", "mensaje" => "ID inválido"]); exit; }
                echo json_encode(obtenerUsuarioXId($conn, $id) ?: ["status" => "error", "mensaje" => "Usuario no encontrado"]);
                break;
            case "esTecnico":             $id = intval($_GET['id'] ?? 0); echo json_encode(["es_tecnico" => esTecnico($conn, $id)]); break;
            case "esAdministrador":       $id = intval($_GET['id'] ?? 0); echo json_encode(["es_admin" => esAdministrador($conn, $id)]); break;
            case "getEstadosIncidencia":  echo json_encode(obtenerEstadosIncidencia($conn)); break;
            case "getFotoPerfil":         echo json_encode(obtenerFotoPerfil($conn, $input['usuario_id'])); break;
            case "getSensoresDeUsuario":  $id = intval($_GET['id'] ?? 0); echo json_encode(obtenerSensoresDeUsuario($conn, $id)); break;
            case "getSensorXId": 
                $id = intval($_GET['id'] ?? 0);
                echo json_encode(obtenerSensorXId($conn, $id) ?: ["status" => "error", "mensaje" => "Sensor no encontrado"]); 
                break;
            case "getObtenerSensoresUsuario": echo json_encode(obtenerListaSensores($conn, $_GET['usuario_id'])); break;
            case "getRecompensasDisponibles": echo json_encode(obtenerRecompensasDisponibles($conn));   break;
            case "getMedicionesXTipo":    
                $tipoId = isset($_GET['tipo_id']) ? intval($_GET['tipo_id']) : 0;      
                    if ($tipoId > 0) {
                        echo json_encode(rellenarMapa($conn, $tipoId));
                    } else {
                        http_response_code(400);
                        echo json_encode(["status" => "error", "mensaje" => "ID de tipo de medición no válido"]);
                    }
                break;
            default: echo json_encode(["status" => "error", "mensaje" => "Acción GET no reconocida."]); break;
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["status" => "error", "mensaje" => "Método HTTP no soportado."]);
        break;
}

// Finalización de la sesión de base de datos
$conn->close();
?>