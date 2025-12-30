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
                                    <li><strong>Baja/Moderada:</strong> Irritación de ojos/nariz/garganta, tos, flema y disnea.</li>
                                    <li><strong>Alta:</strong> Inflamación de vías, bronquitis y edema pulmonar.</li>
                                </ul>
                            </div>
                            <div class="gas-limits">
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
                                <p><strong>Generación: </strong>se genera principalmente por la combustión de combustibles fósiles que contienen azufre (como el carbón y derivados del petróleo) y por la fundición de minerales ricos en azufre. Al quemarse, el azufre se oxida y se libera a la atmósfera. </p>
                                <p><strong>Fuentes:</strong> Industria energetica, Procesos industriales, Calefacción y transporte  </p>

                                <h4>Efectos Nocivos</h4>
                                <ul style="list-style-type: disc; padding-left: 20px; margin-top: 5px;">
                                    <li><strong>Baja/Moderada:</strong> Ojos y piel: Irritación severa, lagrimeo y quemaduras.</li>
                                    <li><strong>Alta:</strong>Sistema respiratorio: Irritado, inflamado, tos, asma, broncoconstricción. Casos graves: edema y neumonía.</li>
                                </ul>
                            </div>
                            <div class="gas-limits">
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
                                <p><strong>Efectos:</strong> Reduce la capacidad de la sangre para transportar oxígeno. Puede causar dolor de cabeza, fatiga y problemas cardíacos.</p>

                                <h4>Efectos Nocivos</h4>
                                <ul style="list-style-type: disc; padding-left: 20px; margin-top: 5px;">
                                    <li><strong>Baja Exposición:</strong> Dolor de cabeza (cefalea), fatiga, dificultad para respirar con esfuerzo, náuseas y mareos leves.</li>
                                    <li><strong>Alta Exposición:</strong>Confusión mental, vértigo, pérdida de coordinación muscular, dolor de pecho, visión borrosa, pérdida de conocimiento, coma y muerte.</li>
                                </ul>
                            </div>
                            <div class="gas-limits">
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
    <script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"
            integrity="sha256-WBkoXOwTeyKclOHuWtc+i2uENFpDZ9YPdf5Hf+D7ewM=" crossorigin=""></script>
    <script src="../js/leaflet-heat.js"></script>
    <script src="../js/map-logic.js"></script>

    <script src="../js/dashboard_cliente.js"></script>
    <script src="../js/Fun_icono_perfil.js"></script>
</html>