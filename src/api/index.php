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

/* ================= DEBUG  ================= */
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
/* ========================================== */
header('Content-Type: application/json');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once('conexion.php');
require_once('logicaNegocio.php');

// Abrimos conexión a la base de datos
$conn = abrirServidor();

// Capturar body crudo json
$rawBody = '';

// Detectamos el método HTTP (GET, POST, etc.)
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST' || $method === 'PUT') {
    // Leemos el contenido crudo del body
    $rawBody = file_get_contents("php://input");

    // Mostramos en consola (solo visible en terminal o log)
    error_log("BODY CRUDO recibido: " . $rawBody);

    // Intentamos decodificar el JSON recibido
    $input = json_decode($rawBody, true);

    // Si no es un JSON válido, respondemos con error
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON inválido: " . json_last_error_msg());
        http_response_code(400);
        echo json_encode(["status" => "error", "mensaje" => "JSON inválido"]);
        exit;
    }

    // Guardar copia del JSON recibido (para debug)
    if (!is_dir("logs")) mkdir("logs");
    file_put_contents("logs/last_request.json", $rawBody);
} else {
    // Para GET y otros métodos, usamos los parámetros normales
    $input = $_GET;
}

//console.log("Accion a la API:", $accion);
//console.log("Enviando a la API:", $input);

// Procesamos la petición según el método
switch ($method) {

    // ---------------------------------------------------------
    // MÉTODO POST -> Crear/modificar registros
    // ---------------------------------------------------------
    case "POST":
        $accion = $input['accion'] ?? null;

        switch ($accion) {
            case "registrarUsuario":
                echo json_encode(registrarUsuario($conn, $input));
                break;

            case "login":
                echo json_encode(loginUsuario($conn, $input['gmail'], $input['password']));
                break;

            case "guardarMedicion":
                echo json_encode(guardarMedicion($conn, $input));
                break;

            case "crearTipoMedicion":
                echo json_encode(crearTipoMedicion($conn, $input));
                break;

            case "crearSensorYRelacion":
                echo json_encode(crearSensorYRelacion($conn, $input));
                break;

            case "activarUsuario":
                echo json_encode(activarUsuario($conn, $input['token']));
                break;

            case "finalizarRelacionSensor":   // marcar sensor con problema
                echo json_encode(marcarSensorConProblemas($conn, $input));
                break;

            case "reactivarSensor":            // reactivar sensor reparado
                echo json_encode(reactivarSensor($conn, $input));
                break;

            case "actualizarUsuario":
                echo json_encode(actualizarUsuario($conn, $input));
                break;

            case "cerrarIncidencia":
                echo json_encode(cerrarIncidencia($conn, $input));
                break;

            case "crearIncidencia":
                echo json_encode(crearIncidencia($conn, $input));
                break;

            case "guardarFotoIncidencia":
                echo json_encode(guardarFotoIncidencia($conn, $input));
                break;

            case "modificarDatos":
                echo json_encode(modificarDatos($conn, $input));
                break;

            default:
                echo json_encode(["status" => "error", "mensaje" => "Acción POST no reconocida."]);
                break;
        }
        break;

    // -----------------------------------------------------
    // MÉTODO GET -> Consultas y estadísticas
    // -----------------------------------------------------
    case "GET":
        $accion = $_GET['accion'] ?? null;

        switch ($accion) {
            case "getMediciones":
                echo json_encode(obtenerMediciones($conn));
                break;

            case "getIncidenciasActivas":
                echo json_encode(obtenerIncidenciasActivas($conn));
                break;

            case "getEstadisticas":
                echo json_encode(obtenerEstadisticas($conn));
                break;

            case "getPromedioDeRango":
                $lat_min = floatval($_GET['lat_min'] ?? 0);
                $lat_max = floatval($_GET['lat_max'] ?? 0);
                $lon_min = floatval($_GET['lon_min'] ?? 0);
                $lon_max = floatval($_GET['lon_max'] ?? 0);
                echo json_encode(promedioPorRango($conn, $lat_min, $lat_max, $lon_min, $lon_max));
                break;

            case "getTodasIncidencias":
                echo json_encode(obtenerTodasIncidencias($conn));
                break;

            case "getFotosIncidencia":
                echo json_encode(obtenerFotosIncidencia($conn, $_GET['incidencia_id']));
                break;
            default:
                echo json_encode(["status" => "error", "mensaje" => "Acción GET no reconocida."]);
                break;
        }
        break;

    // -----------------------------------------------------
    // MÉTODOS NO SOPORTADOS
    // -----------------------------------------------------
    default:
        echo json_encode(["status" => "error", "mensaje" => "Método HTTP no soportado."]);
        break;
}

// Cerramos conexión
$conn->close();
