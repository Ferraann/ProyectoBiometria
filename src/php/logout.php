<?php
/**
 * @file logout.php
 * @brief Script para el cierre de sesión del usuario.
 * @details Este script se encarga de limpiar todas las variables de sesión,
 * destruir la sesión activa en el servidor y redirigir al usuario a la página de inicio de sesión.
 * @author Ferran
 * @date 19/12/2025
 */

/** * @brief Inicia el manejo de sesiones para poder acceder a la sesión actual.
 */
session_start();

/** * @brief Elimina todas las variables de la sesión actual.
 */
session_unset();

/** * @brief Destruye toda la información registrada de la sesión.
 */
session_destroy();

/** * @brief Redirección forzada a la interfaz de login tras el cierre de sesión.
 */
header("Location: ../html/login.html");

/** * @brief Finaliza la ejecución del script para asegurar la redirección.
 */
exit;