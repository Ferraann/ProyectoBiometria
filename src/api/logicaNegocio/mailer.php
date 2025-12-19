<?php
/**
 * @file mailer.php
 * @brief Cargador de dependencias para el sistema de mensajería.
 * @details Este archivo centraliza la inclusión de las librerías de PHPMailer, 
 * facilitando el envío de correos electrónicos a través del protocolo SMTP. 
 * Es fundamental para procesos como la activación de cuenta y el canje de recompensas.
 * @author Manuel
 * @date 05/12/2025
 */

/** * @section InclusionLibrerias 
 * Carga manual de los componentes esenciales de PHPMailer.
 */

/** @note Clase base para la gestión de errores y excepciones internas de la librería. */
require __DIR__ . '/../../libs/PHPMailer-7.0.0/src/Exception.php';

/** @note Núcleo principal que gestiona la creación y envío de objetos de correo. */
require __DIR__ . '/../../libs/PHPMailer-7.0.0/src/PHPMailer.php';

/** @note Implementación del protocolo SMTP para la conexión segura con servidores de correo (ej. Gmail). */
require __DIR__ . '/../../libs/PHPMailer-7.0.0/src/SMTP.php';

/** * @section InstruccionesUso 
 * Para implementar el envío de correos en otros archivos, copie y pegue el siguiente bloque 
 * en la parte superior del fichero destino:
 * * @code
 * require_once __DIR__ . '/mailer.php';
 * use PHPMailer\PHPMailer\PHPMailer;
 * use PHPMailer\PHPMailer\Exception;
 * @endcode
 */
?>