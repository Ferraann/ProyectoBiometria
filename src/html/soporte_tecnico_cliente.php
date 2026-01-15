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
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AITHER | Soporte Técnico</title>
    <link rel="stylesheet" href="../css/incidenciasform.css">

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <!--pop confirmacion de incidencia enviada-->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
    <a href="./dashboard.php"><img src="../img/logo_aitherTX.png" alt="Este es el logo de nuestro Proyecto: Aither"></a>
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
                    <a href="../index.html" class="menu-item logout-item">CERRAR SESIÓN</a>
                </div>
            </li>
        </ul>
    </nav>
</header>

<main class="soporte-container">
    <h1 class="page-title">SOPORTE TÉCNICO</h1>

        <div class="tabs">
            <button class="tab-btn active" onclick="openTab('guia', event)">Guía rápida</button>
            <button class="tab-btn" onclick="openTab('formulario', event)">Formulario</button>
        </div>

        <div id="guia" class="content-section active">
            <p class="section-desc">
                Aquí encontrarás guías rápidas en formato PDF con las instrucciones necesarias para instalar tu sensor, 
                vincular la app y consultar los datos desde la web.
            </p>
            
            <div class="pdf-list">
                <div class="pdf-item">
                    <div class="pdf-left">
                        <i class="fa-solid fa-file-pdf pdf-icon"></i>
                        <span class="pdf-name">ComoVincularTuSensor.pdf</span>
                    </div>
                    <a href="../img/ComoVincularTuSensor.pdf" target="_blank" rel="noopener noreferrer" class="pdf-download">
                        <i class="fa-solid fa-download"></i> </a>
                </div>

                <div class="pdf-item">
                    <div class="pdf-left">
                        <i class="fa-solid fa-file-pdf pdf-icon"></i>
                        <span class="pdf-name">FuncionamientoDelSensor.pdf</span>
                    </div>
                    <a href="#" class="pdf-download"><i class="fa-solid fa-download"></i></a>
                </div>
            </div>
        </div>
        
    <div id="formulario" class="content-section">
        <div class="form-intro">
            <h3>¿Tienes dudas o problemas? Envíanos tu mensaje y te ayudamos.</h3>
            <small>Los apartados con (*) son obligatorios.</small>
        </div>
        <form id="incidenciaForm">
            <!-- Campo oculto: se rellena solo con el id del usuario logueado -->
            <input type="hidden" name="id_user" id="id_user">

            
            <label class="custom-label">Asunto: *</label>
            <input type="text" name="titulo" class="custom-input" maxlength="150" required>
            
            <label class="custom-label">ID del Sensor (opcional):</label>
            <select type="number" name="sensor_id" id="sensor_id" class="custom-input" >
                <option value="" selected>-- Selecciona un sensor --</option>
            </select>

            
            <label class="custom-label">Consulta: *</label>
            <textarea name="descripcion" class="custom-input" rows="4" required></textarea>
            

            <!-- Botón para elegir imagen (por ahora solo selecciona) -->
            <div class="form-actions">
               <div class="file-upload-wrapper">
                    <span>Añadir foto:</span>
                    <label for="imagenInput" class="btn-camera">
                        <i class="fa-solid fa-camera"></i>
                    </label>
                    <input type="file" name="imagen" id="imagenInput" accept="image/*" multiple>
                </div>

                
                <button type="reset" class="btn-reset">BORRAR DATOS</button>
            </div>
            <div id="file-count" class="file-list-container"></div>
            <button type="submit" class="btn-send">Enviar</button>
            <div id="msg" class="msg-box"></div>
        </form>
    </div>
</main>
    <script>
        function openTab(tabName, evt) {
            // Ocultar todos los contenidos
            document.querySelectorAll('.content-section').forEach(sec => sec.classList.remove('active'));
            // Quitar clase active de los botones
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            // Mostrar el seleccionado
            document.getElementById(tabName).classList.add('active');
            // Activar el botón pulsado
            evt.currentTarget.classList.add('active');
        }
        
        // Script para que el botón "BORRAR DATOS" también borre el texto de los archivos
    document.querySelector('button[type="reset"]').addEventListener('click', function() {
        document.getElementById('file-count').textContent = "";
    });
    </script>

    <script src="../js/incidenciaform.js"></script>
    <script src="../js/Fun_icono_perfil.js"></script>
</body>
</html>