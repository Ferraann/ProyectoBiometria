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
                        <div class="dropdown-container"> 
                            <!--Seleccionar ver el mapa que quieras-->
                            <div class="dropdown-mapa" id="dropdown-button">
                                <span>Mis sensores personales</span>
                                <i class="fa-solid fa-chevron-down"></i>
                            </div>
                            <div class="dropdown-menu" >
                                <a href="#" class="dropdown-item">Mis sensores personales</a>
                                <a href="#" class="dropdown-item">Contaminación del aire - Mapa general</a>
                            </div>
                        </div>
                        <!--Selecctor de fecha-->
                        <div class="date-picker" id="date-picker-button">
                            <span>Fecha: 12/11/2025</span>
                            <i class="fa-solid fa-calendar-days"></i>
                        </div>
                    </div>
                    <!--Sitio del mapa interactivo-->
                    <div class="map-container">
                        <div class="map-placeholder">
                            <h2>Aquí irá el mapa interactivo</h2>
                        </div>
                        <div class="leyenda">
                            <!--Leyenda por ahora orientativa-->
                            <h4>Leyenda</h4>
                            <ul>
                                <li><span class="color-box bueno"></span> Bueno</li>
                                <li><span class="color-box moderado"></span> Moderado</li>
                                <li><span class="color-box insalubre"></span> Insalubre</li>
                            </ul>
                        </div>
                        <p class="actualizacion">Última actualización 20:34</p>
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
    </body>
    <script src="../js/dashboard_cliente.js"></script>
    <script src="../js/Fun_icono_perfil.js"></script>
</html>