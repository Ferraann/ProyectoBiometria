/**
 * @file registro.js
 * @brief Gestión del registro de nuevos usuarios en la plataforma AITHER.
 * @details Valida los datos introducidos en el formulario de registro y realiza
 * el envío asíncrono hacia el backend PHP. Incluye manejo de respuestas y
 * redirección automática.
 * @author Sergi y Manuel
 * @date 04/11/2025
 * @copyright © 2025 AITHER. Todos los derechos reservados.
 */

/**
 * @section DECLARACIÓN DE VARIABLES
 */

/** @name Elementos del DOM
 * @{
 */
/** @brief Referencia al formulario de registro. @type {HTMLFormElement} */
const form = document.getElementById("registreForm");
/** @brief Contenedor para mostrar mensajes de estado al usuario. @type {HTMLElement} */
const msg = document.getElementById("message");
/** @} */

/**
 * @section FUNCIÓN: Evento 'submit' del formulario
 */

/**
 * @brief Manejador del evento de envío del formulario.
 * @details Realiza las siguientes acciones:
 * 1. Previene la recarga de la página.
 * 2. Captura y sanea (trim) los valores de los inputs.
 * 3. Valida la presencia de todos los campos obligatorios.
 * 4. Envía los datos mediante una petición POST asíncrona a la API.
 * 5. Gestiona la redirección a login.html en caso de éxito.
 * * @param {Event} e Objeto del evento de envío.
 * @async
 * @returns {Promise<void>}
 */
form.addEventListener("submit", async (e) => {
  e.preventDefault();
  msg.textContent = "";

  /**
   * @section Captura y limpieza de valores ingresados por el usuario
   */

  /** @var {string} nombre Nombre del usuario. */
  const nombre = document.getElementById("nombre").value.trim();
  /** @var {string} apellidos Apellidos del usuario. */
  const apellidos = document.getElementById("apellido").value.trim();
  /** @var {string} gmail Correo electrónico (identificador). */
  const gmail = document.getElementById("gmail").value.trim();
  /** @var {string} password Contraseña elegida. */
  const password = document.getElementById("password").value.trim();

  /**
   * @section Validación básica de campos vacíos
   */

  /** @note Se verifica que todos los campos contengan información antes de proceder. */
  if (!nombre || !apellidos || !gmail || !password) {
    msg.style.color = "#ff0000ff";
    msg.textContent = "Por favor, rellena todos los campos.";
    return; // Detiene la ejecución si hay campos vacíos
  }

  /**
   * @section Preparación de datos para enviar al backend
   */

  /** * @brief Objeto de datos (Payload) para la API.
   * @property {string} accion Identificador de la operación en el backend.
   */
  const payload = {
    accion: "registrarUsuario",
    nombre: nombre,
    apellidos: apellidos,
    gmail: gmail,
    password: password
  };

  try {
    /**
     * @section Petición asincrónica al servidor
     */

    /** @brief Realiza la llamada fetch al controlador principal de la API. */
    const response = await fetch("../api/index.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json"
      },
      body: JSON.stringify(payload)
    });

    /**
     * @section Procesamiento de la respuesta del servidor
     */

    /** @var {Object} data Respuesta deserializada del servidor. */
    const data = await response.json();

    if (data.status === "ok") {
      msg.style.color = "green";
      msg.textContent = data.message || "Registro exitoso.";

      /** @brief Redirección retardada tras confirmar el éxito para mejorar la UX. */
      setTimeout(() => window.location.href = "login.html", 1500);
    } else {
      msg.style.color = "#ff0000ff";
      msg.textContent = data.message || "Error en el registro.";
    }

  } catch (error) {
    /**
     * @section Manejo de errores de conexión
     */

    /** @note Captura fallos de red o errores críticos de ejecución. */
    console.error(error);
    msg.style.color = "#ff0000ff";
    msg.textContent = "Error de conexión con el servidor.";
  }
});