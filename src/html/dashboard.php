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

// Si NO hay un usuario logeado, redirigir al login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.html");
    exit;
}

// Construimos el nombre completo
$nombre = $_SESSION['usuario_nombre'];
$nombreCompleto = $_SESSION['usuario_nombre'] . " " . $_SESSION['usuario_apellidos'];
$gmail = $_SESSION['usuario_correo'];
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

        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css"
              integrity="sha256-kLaT2GOSpHechhsozzB+flnD+zUyjE2LlfWPgU04xyI=" crossorigin="" />
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
                                <!--Seleccionar ver el mapa que quieras-->
                                <div class="dropdown-mapa" id="dropdown-button">
                                    <span>Contaminación del aire - Mapa general</span>
                                    <i class="fa-solid fa-chevron-down"></i>
                                </div>
                                <div class="dropdown-menu" >
                                    <a href="#" class="dropdown-item">Contaminación del aire - Mapa general</a>
                                    <a href="#" class="dropdown-item">Mis sensores personales</a>
                                </div>
                            </div>

                            <button id="open-info-btn" class="dropdown-mapa">
                                <i class="fa-solid fa-circle-info"></i>
                                <span>Información de Gases</span>
                            </button>
                        </div>

                        <!--Selecctor de fecha-->
                        <div class="date-picker" id="date-picker-button">
                            <span>Fecha: 12/11/2025</span>
                            <i class="fa-solid fa-calendar-days"></i>
                        </div>
                    </div>
                    <!--Sitio del mapa interactivo-->
                <div class="mapa-dashboard">
                    <div id="mapa"></div>
                </div>
                </div>
                <!--Seccion de las estadisticas-->
                <div class="tab-content" id="estadisticas-content" data-tab-content="estadisticas">
                    <div class="map-controls">
                        <div class="dropdown-container">
                            <!--seleccion de que mapa se quieren ver las estadisticas-->
                            <div class="dropdown-mapa" id="dropdown-stats-button">
                                <span>Contaminación del aire - Gráficos</span>
                                <i class="fa-solid fa-chevron-down"></i>
                            </div>
                            <div class="dropdown-menu" >
                                <a href="#" class="dropdown-item">Mis sensores personales</a>
                                <a href="#" class="dropdown-item">Contaminación del aire - Mapa general</a>
                            </div>
                        </div>
                        <!--selecctor de fecha-->
                        <div class="date-picker" id="date-picker-stats-button">
                            <span>Fecha: 12/11/2025</span>
                            <i class="fa-solid fa-calendar-days"></i>
                        </div>
                    </div>
                    <!--graficas-->
                    <div class="graph-grid">
                        <div class="graph-placeholder">
                            <h3>Gráfico de evolución temporal</h3>
                        </div>
                        <div class="graph-placeholder">
                            <h3>Niveles máximos y mínimos</h3>
                        </div>
                        <div class="graph-placeholder">
                            <h3>Comparativa de sensores</h3>
                        </div>
                        <div class="graph-placeholder">
                            <h3>Otro dato relevante</h3>
                        </div>
                    </div>
                </div>
            </div>
        </main>


        <div id="gas-info-panel" class="gas-info-modal">
            <div class="gas-info-content">
                <header class="modal-header">
                    <h2><i class="fa-solid fa-wind"></i> Guía de Salud y Contaminantes</h2>
                    <span class="close-info" id="close-info-btn">&times;</span>
                </header>

                <div class="modal-body">
                    <section id="info-no2" class="gas-card no2">
                        <div class="gas-header">
                            <h3>NO₂ - Dióxido de Nitrógeno</h3>
                            <span class="gas-tag">Combustión a alta temperatura</span>
                        </div>
                        <div class="gas-grid">
                            <div class="gas-main-info">
                                <h4><i class="fa-solid fa-house-chimney"></i> Fuentes y Origen</h4>
                                <p>Tráfico vehicular, centrales eléctricas e industria. En interiores: estufas y calentadores.</p>
                                <h4><i class="fa-solid fa-lungs"></i> Efectos en la salud</h4>
                                <p><strong>Baja:</strong> Irritación de ojos, nariz y garganta. Tos y dificultad respiratoria.</p>
                                <p><strong>Alta:</strong> Bronquitis, inflamación de vías respiratorias y edema pulmonar.</p>
                            </div>
                            <div class="gas-limits">
                                <div class="limit-box eu"><span>Límite UE:</span> 40 µg/m³ (Anual)</div>
                                <div class="limit-box oms"><span>Guía OMS:</span> 10 µg/m³ (Anual)</div>
                            </div>
                        </div>
                    </section>

                    <section id="info-o3" class="gas-card o3">
                        <div class="gas-header">
                            <h3>O₃ - Ozono Troposférico</h3>
                            <span class="gas-tag">Reacción química con calor y sol</span>
                        </div>
                        <div class="gas-grid">
                            <div class="gas-main-info">
                                <h4><i class="fa-solid fa-sun"></i> Origen</h4>
                                <p>No se emite directamente; se forma por la reacción de contaminantes con la radiación solar.</p>
                                <h4><i class="fa-solid fa-mask-ventilator"></i> Salud</h4>
                                <p>Reduce la función pulmonar y exacerba el asma. La exposición crónica daña el tejido pulmonar.</p>
                            </div>
                            <div class="gas-limits">
                                <div class="limit-box eu"><span>Objetivo UE:</span> 120 µg/m³ (8h)</div>
                                <div class="limit-box oms"><span>Recom. OMS:</span> 100 µg/m³ (8h)</div>
                            </div>
                        </div>
                    </section>

                    <section id="info-co" class="gas-card co">
                        <div class="gas-header">
                            <h3>CO - Monóxido de Carbono</h3>
                            <span class="gas-tag">Gas asfixiante invisible</span>
                        </div>
                        <div class="gas-grid">
                            <div class="gas-main-info">
                                <h4><i class="fa-solid fa-triangle-exclamation"></i> Peligro Crítico</h4>
                                <p>Reemplaza el oxígeno en la sangre. Afecta principalmente al cerebro y al corazón.</p>
                                <h4><i class="fa-solid fa-stethoscope"></i> Síntomas</h4>
                                <p>Debilidad, mareos, náuseas, confusión y pérdida de control muscular.</p>
                            </div>
                            <div class="gas-limits">
                                <div class="limit-box oms"><span>Límite 24h:</span> 4 mg/m³</div>
                                <div class="limit-box alert"><span>Umbral Superior:</span> 7 mg/m³</div>
                            </div>
                        </div>
                    </section>

                    <section id="info-so2" class="gas-card so2">
                        <div class="gas-header">
                            <h3>SO₂ - Dióxido de Azufre</h3>
                            <span class="gas-tag">Gas altamente irritante</span>
                        </div>
                        <div class="gas-grid">
                            <div class="gas-main-info">
                                <h4><i class="fa-solid fa-eye"></i> Efectos inmediatos</h4>
                                <p>Irritación severa de ojos (quemaduras) y sistema respiratorio (disnea y sibilancias).</p>
                                <h4><i class="fa-solid fa-circle-exclamation"></i> Riesgo crónico</h4>
                                <p>Vinculado a enfermedades cardiovasculares y daños en ecosistemas.</p>
                            </div>
                            <div class="gas-limits">
                                <div class="limit-box oms"><span>Guía OMS:</span> 40 µg/m³ (24h)</div>
                                <div class="limit-box alert"><span>Umbral Máx:</span> 75 μg/m³</div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>


    </body>
    <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"
            integrity="sha256-WBkoXOwTeyKclOHuWtc+i2uENFpDZ9YPdf5Hf+D7ewM=" crossorigin=""></script>
    <script src="../js/leaflet-heat.js"></script>
    <script src="../js/map-logic.js"></script>

    <script src="../js/dashboard_cliente.js"></script>
    <script src="../js/Fun_icono_perfil.js"></script>
</html>