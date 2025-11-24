/* 
===============================================================================
NOMBRE: registro.js
DESCRIPCIÓN: Script para gestionar el registro de nuevos usuarios en la 
             plataforma AITHER. Valida los datos del formulario y 
             envía la información al servidor mediante una petición asíncrona.
COPYRIGHT: © 2025 AITHER. Todos los derechos reservados.
FECHA: 04/11/2025
AUTOR: Sergi y Manuel
APORTACIÓN: Implementación de la lógica de validación de campos y comunicación 
            con el backend PHP para registrar usuarios desde el formulario.
===============================================================================
*/

// ------------------------------------------------------------------
// DECLARACIÓN DE VARIABLES
// ------------------------------------------------------------------
// Captura de referencias a los elementos HTML del formulario y 
// área donde se mostrarán los mensajes de error o éxito.
const form = document.getElementById("registreForm");
const msg = document.getElementById("message");

// ------------------------------------------------------------------
// FUNCIÓN: Evento 'submit' del formulario
// ------------------------------------------------------------------
// Se ejecuta al enviar el formulario, previene el comportamiento 
// por defecto, valida los campos y realiza una petición asincrónica
// al backend usando fetch.
form.addEventListener("submit", async (e) => {
  e.preventDefault(); // Evita que el formulario recargue la página
  msg.textContent = ""; // Limpia cualquier mensaje previo

  // ----------------------------------------------------------------
  // Captura y limpieza de valores ingresados por el usuario
  // ----------------------------------------------------------------
  const nombre = document.getElementById("nombre").value.trim();
  const apellidos = document.getElementById("apellido").value.trim();
  const gmail = document.getElementById("gmail").value.trim();
  const password = document.getElementById("password").value.trim();

  // ----------------------------------------------------------------
  // Validación básica de campos vacíos
  // ----------------------------------------------------------------
  if (!nombre || !apellidos || !email || !password) {
    msg.style.color = "#ff0000ff";
    msg.textContent = "Por favor, rellena todos los campos.";
    return; // Detiene la ejecución si hay campos vacíos
  }

  // ----------------------------------------------------------------
  // Preparación de datos para enviar al backend
  // ----------------------------------------------------------------
  // Se crea un objeto JSON que incluye la acción 'registrarUsuario' 
  // que identifica la petición en el backend, y los datos del usuario.
  const payload = {
    accion: "registrarUsuario",
    nombre: nombre,
    apellidos: apellidos,
    gmail: gmail,
    password: password
  };

  try {
    // ----------------------------------------------------------------
    // Petición asincrónica al servidor
    // ----------------------------------------------------------------
    // Se envía la información mediante POST como JSON, y se espera 
    // la respuesta en formato JSON.
    const response = await fetch("../api/index.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json" // Especifica que enviamos JSON
      },
      body: JSON.stringify(payload) // Convertimos el objeto a JSON
    });

    // ----------------------------------------------------------------
    // Procesamiento de la respuesta del servidor
    // ----------------------------------------------------------------
    const data = await response.json(); // Convertimos la respuesta a JSON

    // Verificamos si la petición fue exitosa según la API
    if (data.status === "ok") {
      msg.style.color = "green";
      msg.textContent = data.message || "Registro exitoso.";

      // Redirigimos al login tras 1.5 segundos
      setTimeout(() => window.location.href = "login.html", 1500);
    } else {
      // Mostramos mensaje de error si ocurrió algún problema
      msg.style.color = "#ff0000ff";
      msg.textContent = data.message || "Error en el registro.";
    }

  } catch (error) {
    // ----------------------------------------------------------------
    // Manejo de errores de conexión
    // ----------------------------------------------------------------
    console.error(error); // Loguea el error en consola
    msg.style.color = "#ff0000ff";
    msg.textContent = "Error de conexión con el servidor.";
  }
});
