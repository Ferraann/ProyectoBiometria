<?php
// ------------------------------------------------------------------
// Fichero: lognegtest.php
// Autor: Manuel
// Fecha: 15/12/2025
// ------------------------------------------------------------------
// Descripción:
//   Script para testear las funciones de Lógica de Negocio de la API REST.
//   Realiza llamadas a varias funciones y muestra los resultados.
// ------------------------------------------------------------------
$BASE_URL = "https://fsanpra.upv.edu.es/src/php/index.php";

/* ==============================
   FUNCIONES AUXILIARES
============================== */

function post($accion, $data = []) {
    global $BASE_URL;
    $payload = array_merge(["accion" => $accion], $data);

    $ch = curl_init($BASE_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload)
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

function get($params = []) {
    global $BASE_URL;
    $url = $BASE_URL . "?" . http_build_query($params);
    return json_decode(file_get_contents($url), true);
}

function titulo($txt) {
    echo "\n================ $txt ================\n";
}

/* ==============================
   TESTS
============================== */

titulo("Registrar usuario");
$res = post("registrarUsuario", [
    "nombre" => "Test",
    "apellidos" => "Usuario",
    "gmail" => "test" . rand(1000,9999) . "@gmail.com",
    "password" => "123456"
]);
print_r($res);

titulo("Login usuario");
$res = post("login", [
    "gmail" => "test@gmail.com",
    "password" => "123456"
]);
print_r($res);

titulo("Crear tipo medición");
$res = post("crearTipoMedicion", [
    "medida" => "Temperatura",
    "unidad" => "ºC",
    "txt" => "Sensor térmico"
]);
print_r($res);

titulo("Crear sensor y relacion");
$res = post("crearSensorYRelacion", [
    "mac" => "AA:BB:CC:" . rand(10,99),
    "usuario_id" => 1
]);
print_r($res);

titulo("Guardar medición");
$res = post("guardarMedicion", [
    "tipo_medicion_id" => 1,
    "valor" => 23.5,
    "sensor_id" => 1,
    "localizacion" => "Valencia"
]);
print_r($res);

titulo("Crear incidencia");
$res = post("crearIncidencia", [
    "id_user" => 1,
    "titulo" => "Sensor roto",
    "descripcion" => "No envía datos",
    "sensor_id" => 1
]);
print_r($res);

titulo("Cerrar incidencia");
$res = post("cerrarIncidencia", [
    "incidencia_id" => 1
]);
print_r($res);

titulo("Sumar puntos");
$res = post("sumarPuntos", [
    "id_usuario" => 1,
    "puntos_a_sumar" => 50
]);
print_r($res);

titulo("Canjear recompensa");
$res = post("canjearRecompensa", [
    "id_usuario" => 1,
    "costo_puntos" => 10,
    "nombre_recompensa" => "Descuento 10%"
]);
print_r($res);

/* ==============================
   GETs
============================== */

titulo("Get mediciones");
print_r(get(["accion" => "getMediciones"]));

titulo("Get incidencias activas");
print_r(get(["accion" => "getIncidenciasActivas"]));

titulo("Get usuario por ID");
print_r(get(["accion" => "getUsuarioXId", "id" => 1]));

titulo("Es técnico");
print_r(get(["accion" => "esTecnico", "id" => 1]));

titulo("Es administrador");
print_r(get(["accion" => "esAdministrador", "id" => 1]));

titulo("Get sensores usuario");
print_r(get(["accion" => "getSensoresDeUsuario", "id" => 1]));

echo "\n✅ TEST FINALIZADO\n";
