<!--
===============================================================================
NOMBRE: dashboard_cliente.html
DESCRIPCIÓN: dashboard o panel de control, esta pagina es la parte privada del usuario,
            una vez hace login esto es lo primero que ve. En el podemos encontrar, con dos apartados
            principales que son los mapas y las estadisticas. Tambien podra acceder a el soporte tecnico
            y proximamente a su perfil, ...
COPYRIGHT: © 2025 AITHER. Todos los derechos reservados.
FECHA: 10/11/2025
AUTOR: Greysy Burgos Salazar
APORTACIÓN: Estructura completa de la página HTML para el inicio de sesión
            con enlaces a recursos CSS y JavaScript externos.
===============================================================================
-->

<?php
session_start();

// 1. Configuración de errores para ver si falla la conexión (Solo en desarrollo)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$nombre = $_SESSION['usuario_nombre'] ?? 'Usuario';

// 2. Importar recursos
// Asegúrate de que estas rutas sean CORRECTAS respecto a dónde está este archivo
require_once '../api/conexion.php';
require_once '../api/logicaNegocio/obtenerMedicion.php';

// 3. Abrir conexión
$conn = abrirServidor();

if (!$conn) {
    die("Error crítico: No se pudo conectar a la base de datos.");
}

// 4. Configuración de IDs (DEBEN COINCIDIR CON TU TABLA tipo_medicion)
$MAPA_GASES = [
    "NO2"  => "1",
    "O3"   => "2",
    "SO2"  => "3",
    "CO"   => "4",
    "PM10" => "5"
];

$SERVER_DATA = [];

// 5. Carga de datos
foreach ($MAPA_GASES as $gas => $tipoMedida) {
    // Obtenemos los datos usando la lógica de negocio existente
    $datos = getMedicionesXTipo($conn, $tipoMedida);

    // Si devuelve null o false, lo convertimos a array vacío para evitar errores en JS
    $SERVER_DATA[$gas] = is_array($datos) ? $datos : [];
}

var_dump($SERVER_DATA); exit;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITHER | Panel de control</title>
    <link rel="icon" href="../img/logo_aither.png" type="image/png">
    <link rel="stylesheet" href="../css/dashboard_cliente.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="../css/mapa.css">

</head>
<body>
<!--
----------------------------------------------------------------------------
BLOQUE: <header>
DISEÑO LÓGICO: Contiene el logotipo y la barra de navegación principal.
DESCRIPCIÓN: Proporciona acceso rápido a secciones informativas y
un botón para iniciar sesión.
----------------------------------------------------------------------------
-->
<header>
    <!-- Logo de Aither | Al hacer clic tiene que llevar a la landing -->
    <a href="#"><img src="../img/logo_Aither_web.png" alt="Este es el logo de nuestro Proyecto: Aither"></a>
    <nav>
        <!--Mis sensores, soporte tecnico y perfil (configuracion y cerrar sesion)-->
        <ul>
            <li><a href="dashboard.php">Mis <br> sensores</a></li>
            <li>
                <a href="soporte_tecnico_cliente.php">Soporte <br> técnico</a>
            </li>
            <li class="profile-dropdown-container">
                <a href="#" class="nav-perfil" id="profile-toggle-button"><i class="fa-solid fa-circle-user"></i><span><?php echo htmlspecialchars($nombre); ?></span></a>
                <div class="profile-menu" id="profile-menu">
                    <div class="menu-header">
                        <i class="fa-solid fa-circle-user profile-icon-large"></i>
                        <span class="profile-name"><?php echo htmlspecialchars($nombre); ?></span>
                        <i class="fa-solid fa-xmark close-menu-btn" id="close-menu-button"></i>
                    </div>
                    <a href="perfil_cliente.php" class="menu-item">CONFIGURACIÓN</a>
                    <a href="../php/logout.php" class="menu-item logout-item">CERRAR SESIÓN</a>
                </div>
            </li>
        </ul>
    </nav>
</header>
<!--
----------------------------------------------------------------------------
BLOQUE: <main>
DISEÑO LÓGICO: La parte principal del dashboard, esta compuesto por dos secciones
que dan diferente informacion pero la estructura es parecida.
----------------------------------------------------------------------------
-->
<main>
    <div class="sensores-container">
        <!--Titulo-->
        <h1>MIS SENSORES</h1>
        <!--pestañas de navegacion entre mapas y estadisticas-->
        <nav class="sensores-nav">
            <ul>
                <li><a href="#" class="active" data-tab="mapas">Mapas</a></li>
                <li><a href="#" data-tab="estadisticas">Estadísticas</a></li>
            </ul>
        </nav>
        <!--seccion de los mapas-->
        <div class="tab-content active-tab-content" id="mapas-content" data-tab-content="mapas">

            <div class="map-controls">
                <div class="selector-gases-y-informacion">
                    <div class="dropdown-container">
                        <div class="dropdown-mapa" style="cursor: pointer;">
                            <span>Mapa general del aire</span>
                            <i class="fa-solid fa-chevron-down"></i>
                        </div>

                        <div class="dropdown-menu">
                            <a href="#" class="dropdown-item">Mapa general del aire</a>
                            <a href="#" class="dropdown-item">Mis sensores personales</a>
                        </div>
                    </div>
                    <button id="open-info-btn" class="dropdown-mapa">
                        <i class="fa-solid fa-circle-info"></i>
                        <span>Información de Gases</span>
                    </button>
                </div>
                <div class="date-picker">
                    <span>Fecha: <?php echo date("d/m/Y"); ?></span>
                    <i class="fa-solid fa-calendar-days"></i>
                </div>
            </div>

            <div class="mapa-dashboard">

                <div id="loader">Analizando atmósfera...</div>

                <div id="map-controls-widget">
                    <strong>MONITOR AIRE</strong><br>
                    <span>Visualización</span>
                    <select id="gasSelect">
                        <option value="MAX" selected>Riesgo Combinado</option>
                        <option disabled>──────────</option>
                        <option value="NO2">NO₂ (Dióxido de Nitrógeno)</option>
                        <option value="O3">O₃ (Ozono)</option>
                        <option value="PM10">PM10 (Partículas)</option>
                        <option value="SO2">SO₂ (Azufre)</option>
                        <option value="CO">CO (Monóxido de Carbono)</option>
                    </select>
                </div>

                <div id="map"></div>

                <div id="legend">
                    <div class="legend">
                        <h4 id="legend-title">Escala</h4>
                        <span id="legend-unit"></span>
                    </div>
                    <div class="legend-bar" id="legend-bar"></div>
                    <div class="legend-labels" id="legend-values"></div>
                </div>
            </div>
        </div>

        <div class="tab-content" id="estadisticas-content" data-tab-content="estadisticas">

            <div class="map-controls">
                <div class="selector-gases-y-informacion">
                    <div class="dropdown-container">
                        <select id="statsGasSelect" class="dropdown-mapa" style="cursor: pointer;">
                            <option value="NO2">NO₂ (Dióxido de Nitrógeno)</option>
                            <option value="O3">O₃ (Ozono)</option>
                            <option value="CO">CO (Monóxido de Carbono)</option>
                            <option value="SO2">SO₂ (Dióxido de Azufre)</option>
                            <option value="PM10">PM10 (Partículas)</option>
                        </select>
                    </div>
                </div>
                <div class="date-picker">
                    <span>Fecha: <?php echo date("d/m/Y"); ?></span>
                    <i class="fa-solid fa-calendar-days"></i>
                </div>
            </div>

            <div class="graph-grid">
                <div class="graph-placeholder" style="background: #202020; border: 1px solid #444; position: relative;">
                    <h3 style="color: #ffae00; margin-bottom: 10px; text-align: center;">Evolución Media Diaria (24h)</h3>
                    <div style="height: 300px; width: 100%;">
                        <canvas id="chartEvolucion"></canvas>
                    </div>
                </div>

                <div class="graph-placeholder" style="background: #202020; border: 1px solid #444; position: relative;">
                    <h3 style="color: #ffae00; margin-bottom: 10px; text-align: center;">Resumen Máximos/Mínimos Globales</h3>
                    <div style="height: 300px; width: 100%;">
                        <canvas id="chartMinMax"></canvas>
                    </div>
                </div>

                <div class="graph-placeholder" style="background: #202020; border: 1px solid #444; position: relative;">
                    <h3 style="color: #ffae00; margin-bottom: 10px; text-align: center;">Top 5 Sensores Más Contaminantes</h3>
                    <div style="height: 300px; width: 100%;">
                        <canvas id="chartTopSensores"></canvas>
                    </div>
                </div>

                <div class="graph-placeholder" style="background: #202020; border: 1px solid #444; position: relative; display: flex; justify-content: center; align-items: center; color: #666;">
                    <p>Próximamente: Predicción IA</p>
                </div>
            </div>
        </div>
</main>


<div id="gas-info-panel" class="gas-info-modal">
    <div class="gas-info-content">
        <header class="modal-header">
            <h2> Guía de Contaminantes</h2>
            <span class="close-info" id="close-info-btn">&times;</span>
        </header>

        <div class="modal-body">

            <section id="info-o3" class="gas-card o3">
                <div class="gas-header">
                    <h3>O₃ - Ozono</h3>
                    <span class="gas-tag">Reacción fotoquímica compleja</span>
                </div>
                <div class="gas-grid">
                    <div class="gas-main-info">
                        <p><strong>Generación:</strong> No se emite de forma directa (reacción química con el sol).</p>
                        <p><strong>Fuentes:</strong> Tráfico rodado, industrias, refinerías, vapores de gasolina, disolventes y productos de limpieza (especialmente en días soleados).</p>

                        <h4>Efectos Nocivos</h4>
                        <ul style="list-style-type: disc; padding-left: 20px; margin-top: 5px;">
                            <li><strong>Baja exposición:</strong> Irritación, dolor de garganta/pecho, tos y falta de aire.</li>
                            <li><strong>Alta exposición:</strong> Reducción pulmonar, asma, bronquitis, enfisema y riesgo de infecciones. La exposición crónica puede causar daño permanente.</li>
                        </ul>
                    </div>
                    <div class="gas-limits">
                        <h4>Umbrales</h4>
                        <div class="limit-box eu"><span>UE (8h):</span> Máx 120 µg/m³</div>
                        <div class="limit-box oms"><span>OMS (8h):</span> Máx 100 µg/m³</div>
                    </div>
                </div>
            </section>

            <section id="info-no2" class="gas-card no2">
                <div class="gas-header">
                    <h3>NO₂ - Dióxido de Nitrógeno</h3>
                    <span class="gas-tag">Combustión a altas temperaturas</span>
                </div>
                <div class="gas-grid">
                    <div class="gas-main-info">
                        <p><strong>Generación:</strong> Combustión a altas temperaturas.</p>
                        <p><strong>Fuentes:</strong> Tráfico, centrales eléctricas, industria y fuentes interiores (estufas/calentadores).</p>

                        <h4>Efectos Nocivos</h4>
                        <ul style="list-style-type: disc; padding-left: 20px; margin-top: 5px;">
                            <li><strong>Baja exposición:</strong> Irritación de ojos/nariz/garganta, tos, flema y disnea.</li>
                            <li><strong>Alta exposición:</strong> Inflamación de vías, bronquitis y edema pulmonar.</li>
                        </ul>
                    </div>
                    <div class="gas-limits">
                        <h4>Umbrales</h4>
                        <div class="limit-box eu"><span>UE:</span> 40 (Anual) / 200 (1h) µg/m³</div>
                        <div class="limit-box oms"><span>OMS:</span> 10 (Anual) / 25 (24h) µg/m³</div>
                        <div class="limit-box alert" style="margin-top:5px; width:100%"><span>Otros:</span> Sup: 7 mg/m³ | Min: 5 mg/m³</div>
                    </div>
                </div>
            </section>

            <section id="info-so2" class="gas-card so2">
                <div class="gas-header">
                    <h3>SO₂ - Dióxido de Azufre</h3>
                    <span class="gas-tag">Gas irritante</span>
                </div>
                <div class="gas-grid">
                    <div class="gas-main-info">
                        <p><strong>Generación: </strong>Se genera principalmente por la combustión de combustibles fósiles que contienen azufre (como el carbón y derivados del petróleo) y por la fundición de minerales ricos en azufre. Al quemarse, el azufre se oxida y se libera a la atmósfera. </p>
                        <p><strong>Fuentes:</strong> Industria energetica, Procesos industriales, Calefacción y transporte.</p>

                        <h4>Efectos Nocivos</h4>
                        <ul style="list-style-type: disc; padding-left: 20px; margin-top: 5px;">
                            <li><strong>Baja exposición:</strong> Ojos y piel: Irritación severa, lagrimeo y quemaduras.</li>
                            <li><strong>Alta exposición:</strong>Sistema respiratorio: Irritado, inflamado, tos, asma, broncoconstricción. Casos graves: edema y neumonía.</li>
                        </ul>
                    </div>
                    <div class="gas-limits">
                        <h4>Umbrales</h4>
                        <div class="limit-box oms"><span>OMS:</span> 40 (24h) / 500 (10min) µg/m³</div>
                        <div class="limit-box alert"><span>Máx (60% VLD):</span> 75 μg/m³</div>
                        <div class="limit-box alert"><span>Mín (40% VLD):</span> 50 μg/m³</div>
                    </div>
                </div>
            </section>

            <section id="info-co" class="gas-card co">
                <div class="gas-header">
                    <h3>CO - Monóxido de Carbono</h3>
                    <span class="gas-tag">Gas incoloro (Combustión incompleta)</span>
                </div>
                <div class="gas-grid">
                    <div class="gas-main-info">
                        <p><strong>Generación:</strong> Producido por la combustión incompleta.</p>
                        <p><strong>Fuentes:</strong> La principal fuente es el tráfico rodado (coches, camiones, motos) debido a la quema de gasolina y diésel. Estufas de gas o leña mal ventiladas, calderas defectuosas, braseros de carbón, chimeneas obstruidas y humo de tabaco. </p>

                        <h4>Efectos Nocivos</h4>
                        <ul style="list-style-type: disc; padding-left: 20px; margin-top: 5px;">
                            <li><strong>Baja exposición:</strong> Dolor de cabeza (cefalea), fatiga, dificultad para respirar con esfuerzo, náuseas y mareos leves.</li>
                            <li><strong>Alta exposición:</strong>Confusión mental, vértigo, pérdida de coordinación muscular, dolor de pecho, visión borrosa, pérdida de conocimiento, coma y muerte.</li>
                        </ul>
                    </div>
                    <div class="gas-limits">
                        <h4>Umbrales</h4>
                        <div class="limit-box oms"><span>OMS (24h):</span> 4000 µg/m³</div>
                        <div class="limit-box oms"><span>OMS (1h/8h):</span> 25 ppm / 9 ppm</div>
                        <div class="limit-box alert" style="width:100%"><span>Eval:</span> Sup: 7 mg/m³ | Inf: 5 mg/m³</div>
                    </div>
                </div>
            </section>

        </div>
    </div>
</div>


</body>
<script>
    window.SERVER_DATA = <?= json_encode(
        $SERVER_DATA,
        JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK
    ); ?>;
</script>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="../js/map-logic.js"></script>
<script src="../js/dashboard_cliente.js"></script>
<script src="../js/Fun_icono_perfil.js"></script>
</html>