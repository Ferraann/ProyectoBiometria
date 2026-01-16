<?php
session_start();

// Configuración de errores (Desactivar en producción)
// error_reporting(0);

/*
// Seguridad: Descomentar cuando el login esté 100% operativo
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.html");
    exit;
}
*/

date_default_timezone_set('Europe/Madrid');
$nombre = $_SESSION['usuario_nombre'] ?? 'Usuario';

// ===============================
// 1. CARGA DE RECURSOS Y CONEXIÓN
// ===============================
require_once '../api/conexion.php';
require_once '../api/logicaNegocio/obtenerMedicion.php';

$conn = abrirServidor();

// ===============================
// 2. CONFIGURACIÓN DE GASES
// ===============================
// IDs deben coincidir con tu tabla 'tipo_medicion' en la BBDD
$MAPA_GASES = [
    "NO2"  => "1",
    "O3"   => "2",
    "SO2"  => "3",
    "CO"   => "4",
    "PM10" => "5"
];

$SERVER_DATA = [];

// ===============================
// 3. OBTENCIÓN DE DATOS
// ===============================
if ($conn) {
    foreach ($MAPA_GASES as $gas => $tipoMedida) {
        $datos = getMedicionesXTipo($conn, $tipoMedida);
        // Aseguramos que siempre sea un array para evitar errores en JS
        $SERVER_DATA[$gas] = is_array($datos) ? $datos : [];
    }
} else {
    // Fallback por si falla la conexión
    foreach ($MAPA_GASES as $gas => $id) {
        $SERVER_DATA[$gas] = [];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITHER | Panel de control</title>

    <link rel="icon" href="../img/logo_aither.png" type="image/png">

    <link rel="stylesheet" href="../css/dashboard_cliente.css">
    <link rel="stylesheet" href="../css/mapa.css">

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<header>
    <a href="#"><img src="../img/logo_Aither_web.png" alt="Logo Aither"></a>
    <nav>
        <ul>
            <li><a href="dashboard.php">Mis <br> sensores</a></li>
            <li><a href="soporte_tecnico_cliente.php">Soporte <br> técnico</a></li>

            <li class="profile-dropdown-container">
                <a href="#" class="nav-perfil" id="profile-toggle-button">
                    <i class="fa-solid fa-circle-user"></i>
                    <span><?php echo htmlspecialchars($nombre); ?></span>
                </a>
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

<main>
    <div class="sensores-container">
        <h1>MIS SENSORES</h1>

        <nav class="sensores-nav">
            <ul>
                <li><a href="#" class="active" data-tab="mapas">Mapas</a></li>
                <li><a href="#" data-tab="estadisticas">Estadísticas</a></li>
            </ul>
        </nav>

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
                        <option value="ESTACIONES">Estaciones Oficiales</option>
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
                    <h3 style="color: #ffae00; margin-bottom: 10px; text-align: center;">Resumen Máximos Y Mínimos Globales</h3>
                    <div style="height: 300px; width: 100%;">
                        <canvas id="chartMinMax"></canvas>
                    </div>
                </div>

                <div class="graph-placeholder" style="background: #202020; border: 1px solid #444; position: relative; display: flex; justify-content: center; align-items: center; color: #666;">
                    <p>Próximamente: Predicción IA</p>
                </div>
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
                        <p><strong>Fuentes:</strong> Tráfico, industrias, disolventes (especialmente en días soleados).</p>
                        <h4>Efectos Nocivos</h4>
                        <ul style="list-style-type: disc; padding-left: 20px; margin-top: 5px;">
                            <li><strong>Baja exposición:</strong> Irritación, tos y falta de aire.</li>
                            <li><strong>Alta exposición:</strong> Reducción pulmonar, asma, daño permanente.</li>
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
                        <p><strong>Fuentes:</strong> Tráfico, centrales eléctricas, industria.</p>
                        <h4>Efectos Nocivos</h4>
                        <ul style="list-style-type: disc; padding-left: 20px; margin-top: 5px;">
                            <li><strong>Baja exposición:</strong> Irritación de ojos/garganta, tos.</li>
                            <li><strong>Alta exposición:</strong> Inflamación de vías, bronquitis.</li>
                        </ul>
                    </div>
                    <div class="gas-limits">
                        <h4>Umbrales</h4>
                        <div class="limit-box eu"><span>UE:</span> 40 (Anual) µg/m³</div>
                        <div class="limit-box oms"><span>OMS:</span> 10 (Anual) µg/m³</div>
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
                        <p><strong>Generación: </strong>Combustión de fósiles con azufre y fundición de minerales.</p>
                        <p><strong>Fuentes:</strong> Industria energética, calefacción y transporte.</p>
                        <h4>Efectos Nocivos</h4>
                        <ul style="list-style-type: disc; padding-left: 20px; margin-top: 5px;">
                            <li><strong>Baja exposición:</strong> Irritación severa, lagrimeo.</li>
                            <li><strong>Alta exposición:</strong> Asma, broncoconstricción, neumonía.</li>
                        </ul>
                    </div>
                    <div class="gas-limits">
                        <h4>Umbrales</h4>
                        <div class="limit-box oms"><span>OMS:</span> 40 (24h) µg/m³</div>
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
                        <p><strong>Generación:</strong> Combustión incompleta.</p>
                        <p><strong>Fuentes:</strong> Tráfico, estufas mal ventiladas, tabaco.</p>
                        <h4>Efectos Nocivos</h4>
                        <ul style="list-style-type: disc; padding-left: 20px; margin-top: 5px;">
                            <li><strong>Baja exposición:</strong> Cefalea, fatiga, mareos.</li>
                            <li><strong>Alta exposición:</strong> Confusión, pérdida de conocimiento, coma.</li>
                        </ul>
                    </div>
                    <div class="gas-limits">
                        <h4>Umbrales</h4>
                        <div class="limit-box oms"><span>OMS (24h):</span> 4000 µg/m³</div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>

<script>
    // Pasamos el array de PHP a una variable global de JS de forma segura
    window.SERVER_DATA = <?= json_encode(
        $SERVER_DATA,
        JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK
    ); ?>;
</script>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script src="../js/map-logic.js"></script>

<script src="../js/dashboard_cliente.js"></script>

<script src="../js/Fun_icono_perfil.js"></script>

</body>
</html>