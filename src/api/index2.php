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

// header('Content-Type: application/json');
// header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
// header('Access-Control-Allow-Headers: Content-Type');

// if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
//     http_response_code(200);
//     exit;
// }

// -------------------- INCLUSIONES --------------------
// Conexión a la base de datos
require_once(__DIR__ . '/conexion.php');     


// Funciones individuales dentro del directorio logicaNegocio/
// foreach (glob(__DIR__ . "/logicaNegocio/*.php") as $file) {
//     require_once($file);
// }

// Abrimos conexión
$conn = abrirServidor();

// // -------------------- LECTURA DEL BODY --------------------
// $rawBody = '';
// $method = $_SERVER['REQUEST_METHOD'];

// if ($method === 'POST' || $method === 'PUT') {
//     $rawBody = file_get_contents("php://input");
//     error_log("BODY CRUDO recibido: " . $rawBody);

//     $input = json_decode($rawBody, true);
//     if (json_last_error() !== JSON_ERROR_NONE) {
//         error_log("JSON inválido: " . json_last_error_msg());
//         http_response_code(400);
//         echo json_encode(["status" => "error", "mensaje" => "JSON inválido"]);
//         exit;
//     }

//     if (!is_dir("logs")) mkdir("logs");
//     file_put_contents("logs/last_request.json", $rawBody);
// } else {
//     $input = $_GET;
// }

// // -------------------- PROCESAMIENTO --------------------
// switch ($method) {

//     default:
//         echo json_encode(["status" => "error", "mensaje" => "Método HTTP no soportado."]);
//         break;
// }

// Cerramos conexión
$conn->close();
?>
