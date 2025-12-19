<?php
/**
 * @file test_total_aither.php
 * @brief Lanzador automático de pruebas para todos los métodos de la API AITHER.
 * @details Este script carga dinámicamente toda la lógica de negocio y ejecuta tests 
 * de integración simulados para validar la integridad de cada función del index.php.
 * @author Manuel
 * @date 19/12/2025
 */

require_once 'MockDatabase.php';

/** @section CargaLógica Importación masiva de funciones de negocio. */
foreach (glob(__DIR__ . "/logicaNegocio/*.php") as $file) {
    require_once($file);
}

$conn = new MockConn();
/** @var array $dummyInput Datos de ejemplo para alimentar las funciones. */
$dummyInput = ["sensor_id" => 1, "usuario_id" => 1, "token" => "tk_123", "gmail" => "a@b.com", "password" => "123"];

echo "====================================================\n";
echo "   AITHER API: FULL AUTOMATED TEST SUITE (DOXYGEN)  \n";
echo "====================================================\n\n";

/** @section TestsUsuarios Pruebas de gestión de cuentas y perfiles. */
runTest("Usuario: registrarUsuario", function() use ($conn) {
    registrarUsuario($conn, ["nombre"=>"N", "apellidos"=>"A", "gmail"=>"t@t.com", "password"=>"p"]); 
});
runTest("Usuario: loginUsuario", function() use ($conn) { loginUsuario($conn, "a@b.com", "123"); });
runTest("Usuario: activarUsuario", function() use ($conn) { activarUsuario($conn, "token"); });
runTest("Usuario: obtenerUsuarioXId", function() use ($conn) { obtenerUsuarioXId($conn, 1); });
runTest("Usuario: guardarFotoPerfil", function() use ($conn, $dummyInput) { guardarFotoPerfil($conn, $dummyInput); });

/** @section TestsRoles Pruebas de validación y asignación de rangos. */
runTest("Roles: esTecnico / esAdministrador", function() use ($conn) {
    esTecnico($conn, 1);
    esAdministrador($conn, 1);
});
runTest("Roles: Gestión de permisos (Asignar/Quitar)", function() use ($conn) {
    asignarTecnico($conn, 1);
    quitarTecnico($conn, 1);
    asignarAdministrador($conn, 1);
    quitarAdministrador($conn, 1);
});

/** @section TestsSensores Pruebas de hardware y telemetría. */
runTest("Sensor: obtenerSensoresDeUsuario", function() use ($conn) { obtenerSensoresDeUsuario($conn, 1); });
runTest("Sensor: obtenerSensorXId", function() use ($conn) { obtenerSensorXId($conn, 1); });
runTest("Sensor: crearSensorYRelacion", function() use ($conn, $dummyInput) { crearSensorYRelacion($conn, $dummyInput); });
runTest("Sensor: marcarSensorProblemas", function() use ($conn, $dummyInput) { sensorConProblemas($conn, $dummyInput); });

/** @section TestsIncidencias Pruebas del sistema de tickets y soporte. */
runTest("Incidencias: obtenerTodasIncidencias", function() use ($conn) { obtenerTodasIncidencias($conn); });
runTest("Incidencias: obtenerIncidenciasActivas", function() use ($conn) { obtenerIncidenciasActivas($conn); });
runTest("Incidencias: crearIncidencia", function() use ($conn, $dummyInput) { crearIncidencia($conn, $dummyInput); });
runTest("Incidencias: asignarTecnicoIncidencia", function() use ($conn, $dummyInput) { asignarTecnicoIncidencia($conn, $dummyInput); });
runTest("Incidencias: guardarFotoIncidencia", function() use ($conn, $dummyInput) { guardarFotoIncidencia($conn, $dummyInput); });

/** @section TestsMediciones Pruebas de datos ambientales. */
runTest("Mediciones: obtenerMediciones", function() use ($conn) { obtenerMediciones($conn); });
runTest("Mediciones: guardarMedicion", function() use ($conn, $dummyInput) { guardarMedicion($conn, $dummyInput); });
runTest("Mediciones: promedioPorRango", function() use ($conn) { promedioPorRango($conn, 39, 40, -1, 0); });

/** @section TestsGamificacion Pruebas de puntos y distancias. */
runTest("Gamificación: sumarPuntosUsuario", function() use ($conn) { sumarPuntosUsuario($conn, ["id_usuario"=>1, "puntos_a_sumar"=>10]); });
runTest("Gamificación: canjearRecompensa", function() use ($conn, $dummyInput) { canjearRecompensa($conn, $dummyInput); });
runTest("Distancia: guardarDistanciaHoy / getHistorial", function() use ($conn, $dummyInput) {
    guardarDistanciaHoy($conn, $dummyInput);
    getHistorialDistancias($conn, []);
});

echo "\n====================================================\n";
echo "   REPORTE: TODOS LOS MÉTODOS HAN SIDO PROBADOS     \n";
echo "====================================================\n";