<!--
===============================================================================
NOMBRE: perfil_cliente.html
DESCRIPCIÓN: En esta seccion privada del usuario podra encontrar sus datos personalers,
            datos de los sensores y podra modificarlos en caso de que sea necesario.
COPYRIGHT: © 2025 AITHER. Todos los derechos reservados.
FECHA: 13/11/2025
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
$password = $_SESSION['usuario_password'];
?>


<!DOCTYPE html>
<html lang="es">
<head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>AITHER | Perfil</title>
        <link rel="icon" href="../img/logo_aither.png" type="image/png">
        <link rel="stylesheet" href="../css/perfil_cliente.css">
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
        <script src="../js/actualizar_perfil.js" defer></script>
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
            <a href="./dashboard.php"><img src="../img/logo_Aither_web.png" alt="Este es el logo de nuestro Proyecto: Aither"></a>
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

    <main class="perfil-container">
        <section class="edit-perfil-section">
            <h2>EDITAR PERFIL</h2>
            
            <form class="perfil-form" action="../php/actualizar_perfil.php" method="POST">
                <div class="form-group foto-group">
                    <div class="foto-placeholder"><img src="../img/imagen-icono.webp" alt="Icono de usuario"></div>
                    <!-- <a href="#" class="edit-link">Editar <i class="fa-solid fa-pen"></i></a> -->
                </div>

                <div class="form-fields">
                    
                    <div class="input-row">
                        <label for="nombre">Nombre:</label>
                        <input type="text" id="nombre" name="nombre" value="<?php echo htmlspecialchars($nombreCompleto); ?>" disabled>
                        <a href="#" class="edit-icon"><i class="fa-solid fa-pen-to-square"></i></a>
                    </div>

                    <div class="input-row">
                        <label for="correo">Correo Electrónico:</label>
                        <input type="email" id="gmail" name="gmail" value="<?php echo htmlspecialchars($gmail); ?>" disabled>
                        <a href="#" class="edit-icon"><i class="fa-solid fa-pen-to-square"></i></a>
                    </div>
                    
                    <div class="input-row">
                        <label for="repetir-correo">Repetir correo:</label>
                        <input type="email" id="repetir-correo" name="repetir-correo" disabled>
                    </div>

                    <div class="input-row">
                        <label for="contrasena">Contraseña:</label>
                        <input type="password" id="contrasena"  name="password"value="<?php echo htmlspecialchars($password); ?>" disabled>
                        <a href="#" class="edit-icon"><i class="fa-solid fa-pen-to-square"></i></a>
                    </div>
                    
                    <div class="input-row">
                        <label for="repetir-contrasena">Repetir contraseña:</label>
                        <input type="password" id="repetir-contrasena" name="repetir-contrasena" disabled>
                    </div>
                </div>

                <button type="submit" class="btn-guardar">GUARDAR</button>
            </form>
        </section>

        <section class="config-sensores-section">
            <h2>CONFIGURAR SENSORES</h2>
            
            <nav class="sensor-tabs">
                <ul>
                    <li><a href="#" class="tab-link active" data-sensor="1">Sensor 1</a></li>
                    <li><a href="#" class="tab-link" data-sensor="2">Sensor 2</a></li>
                </ul>
            </nav>

            <div class="sensor-details active-tab" id="sensor-1-content">
                <ul>
                    <li>**ID del sensor:** <span>ES000123456</span></li>
                    <li>**Fecha de inicio:** <span>01/10/2025</span></li>
                    <li>**Batería restante:** <span>78%</span></li>
                    <li>**Incidencias antiguas:** <span>3 (Ver detalles)</span></li>
                </ul>
            </div>
            
            <div class="sensor-details" id="sensor-2-content">
                <ul>
                    <li>**ID del sensor:** <span>ES000654321</span></li>
                    <li>**Fecha de inicio:** <span>20/11/2025</span></li>
                    <li>**Batería restante:** <span>100%</span></li>
                    <li>**Incidencias antiguas:** <span>0</span></li>
                </ul>
            </div>

            </section>
    </main>
    <script src="../js/Fun_icono_perfil.js"></script>
    <script>
    const tabLinks = document.querySelectorAll('.sensor-tabs a'); // Correcto: .sensor-tabs
    const tabContents = document.querySelectorAll('.sensor-details'); // Correcto: .sensor-details

    tabLinks.forEach(link => {
        link.addEventListener('click', function(event) {
            event.preventDefault(); 
            
            // 1. Obtener el ID del sensor (usando data-sensor, no data-tab)
            const sensorId = this.dataset.sensor; 
            
            // 2. Remover 'active' de todos los enlaces y 'active-tab' de todos los contenidos
            tabLinks.forEach(l => l.classList.remove('active'));
            tabContents.forEach(c => c.classList.remove('active-tab')); // Clase correcta: active-tab

            // 3. Añadir 'active' al enlace clickeado
            this.classList.add('active');

            // 4. Mostrar el contenido correspondiente (usando el ID)
            const activeContent = document.getElementById(`sensor-${sensorId}-content`);
            if (activeContent) {
                activeContent.classList.add('active-tab');
            }
        });
    });
</script>

</body>
</html>
