/**
 * @file login.js
 * @brief Gestión del sistema de autenticación y registro de usuarios.
 * @details Este script controla la interfaz de acceso de AITHER, incluyendo:
 * - Animaciones de transición entre Login y Registro.
 * - Validación de fortaleza de contraseñas.
 * - Comunicación asíncrona con la API para autenticación y roles.
 * - Gestión de la persistencia de sesión mediante SessionStorage y LocalStorage.
 * - Control del popup interactivo de políticas de privacidad.
 * @author Ferran y Manuel
 * @date 04/11/2025
 * @copyright © 2025 AITHER. Todos los derechos reservados.
 */

/** @name ReferenciasDOM
 * @{
 */

/** @brief Contenedor principal de los formularios. @type {HTMLElement} */
const container = document.getElementById("container");
/** @brief Botón para activar el panel de registro. @type {HTMLElement} */
const signUpBtn = document.getElementById("signUpBtn");
/** @brief Botón para activar el panel de inicio de sesión. @type {HTMLElement} */
const signInBtn = document.getElementById("signInBtn");
/** @brief Formulario de inicio de sesión. @type {HTMLFormElement} */
const loginForm = document.querySelector(".sign-in-container form");
/** @brief Formulario de registro. @type {HTMLFormElement} */
const registerForm = document.querySelector(".sign-up-container form");
/** @brief Enlace de acción en el menú de navegación. @type {HTMLAnchorElement} */
const botonHeader = document.querySelector("nav ul li:last-child a");
/** @} */

/* ============================
   GESTIÓN DE MENSAJES
   ============================ */

/** @brief Elemento de mensaje para errores de login. */
let msgLogin = document.createElement("p");

msgLogin.id = "message-login";
msgLogin.style.marginTop = "10px";
msgLogin.style.fontWeight = "600";
msgLogin.style.fontSize = "14px"
msgLogin.style.textAlign = "center";
msgLogin.style.color = "white";
msgLogin.classList.remove("fade-out");

/** @brief Elemento de mensaje para errores de registro. */
let msgRegister = document.createElement("p");

msgRegister.id = "message-register";
msgRegister.style.marginTop = "10px";
msgRegister.style.fontWeight = "600";
msgRegister.style.fontSize = "14px";
msgRegister.style.display = "flex";
msgRegister.style.alignItems = "flex-start";
msgRegister.style.color = "white";
msgRegister.classList.remove("fade-out");


/**
 * @section MOSTRAR / OCULTAR CONTRASEÑA
 */

document.querySelectorAll(".toggle-password").forEach(icon => {
  icon.addEventListener("click", () => {
    const input = document.getElementById(icon.dataset.input);
    const esPass = input.type === "password";
    input.type = esPass ? "text" : "password";
    icon.src = esPass ? "../img/ojo.png" : "../img/ojo-cerrado.png";
  });
});

/**
 * @section ANIMACIÓN ENTRE LOGIN/REGISTER
 */

/** @brief Cuando se pulsa el boton de registro se añade la clase active */
signUpBtn.addEventListener("click", () => {
  container.classList.add("active");
});

/** @brief Cuando se pulsa el boton de inicio de sesión se quita la clase active */
signInBtn.addEventListener("click", () => {
  container.classList.remove("active");
});

/** @brief Cambios de color del botón del header según el estado */
container.addEventListener("transitionend", () => {
  botonHeader.classList.toggle("active", container.classList.contains("active"));
});

/** @brief Actualizar el estado del boton de inicio de sesión del header */
function updateHeaderLoginButton() {
  const isRegister = container.classList.contains("active");
  botonHeader.classList.toggle("disabled", !isRegister);
  botonHeader.classList.toggle("enabled", isRegister);
}

/** @brief Llamada inicial al cargar la página */
updateHeaderLoginButton();

/** @brief Actualizar tras animaciones o clics */
container.addEventListener("transitionend", updateHeaderLoginButton);
signUpBtn.addEventListener("click", updateHeaderLoginButton);
signInBtn.addEventListener("click", updateHeaderLoginButton);


/** @brief Interceptar click en el header */

botonHeader.addEventListener("click", (e) => {
  e.preventDefault();
  if (botonHeader.classList.contains("disabled")) return;
  container.classList.remove("active");
  updateHeaderLoginButton();
  document.getElementById("correo-sign-in")?.focus();
});

/**
 * @section FUNCIÓN AUXILIAR: Mostrar mensaje con desvanecimiento
 */
function mostrarMensaje(tipo, texto, duracion = 3000) {
  const msg = tipo === "login" ? msgLogin : msgRegister;
  msg.textContent = texto;
  msg.classList.remove("fade-out");

  const ref = tipo === "login"
    ? loginForm.querySelector(".forgot")
    : registerForm.querySelector(".btn-primary");

  ref.before(msg);

  setTimeout(() => msg.classList.add("fade-out"), duracion);
}

/**
 * @section EVENTO: LOGIN → consume API vía index.php (acción: login)
 */
loginForm.addEventListener("submit", async e => {
  e.preventDefault();

  const gmail = document.getElementById("correo-sign-in").value.trim();
  const password = document.getElementById("contraseña-sign-in").value.trim();

  if (!gmail || !password) {
    mostrarMensaje("login", "Por favor, rellena todos los campos.");
    return;
  }

  try {
    /** @brief PASO 1: Intentar iniciar sesión (POST) */
    const response = await fetch("../api/index.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ accion: "login", gmail, password })
    });

    const dataLogin = await response.json();

    if (dataLogin.status === "ok") {

      const usuario = dataLogin.usuario;
      const usuarioId = usuario.id;

      window.sessionStorage.setItem("idUsuario", usuarioId.toString());
      localStorage.setItem("user", JSON.stringify(usuario));

      /** @brief PASO 2: Verificar roles mediante llamadas GET */
      const urlTecnico = "https://fsanpra.upv.edu.es/src/html/incidencias.html";
      const urlAdmin = "https://fsanpra.upv.edu.es/src/html/incidencias.html";
      let redirectURL = "dashboard.php"; // URL por defecto si no es ni técnico ni administrador

      const resTecnico = await fetch(`../api/index.php?accion=esTecnico&id=${usuarioId}`);
      const dataTecnico = await resTecnico.json();
      const esTecnico = dataTecnico.es_tecnico || false;

      /** @brief Llamada 2b: ¿Es administrador? */
      const resAdmin = await fetch(`../api/index.php?accion=esAdministrador&id=${usuarioId}`);
      const dataAdmin = await resAdmin.json();
      const esAdministrador = dataAdmin.es_admin || false;

      /** @brief PASO 3: Redirigir según el rol */
      if (esTecnico) {
        redirectURL = urlTecnico;
      }

      if (esAdministrador) {
        redirectURL = urlAdmin;
      }

      /** @brief Redirigir a la URL determinada */
      window.location.href = redirectURL;

    } else {
      mostrarMensaje("login", dataLogin.message || "Credenciales incorrectas.");
    }
  } catch (err) {
    console.error(err);
    mostrarMensaje("login", "Error de conexión con el servidor.");
  }
});

/**
 * @section EVENTO: REGISTRO → consume API vía index.php (acción: registrarUsuario)
 */
registerForm.addEventListener("submit", async e => {
  e.preventDefault();

  const nombre = document.getElementById("nombre").value.trim();
  const apellidos = document.getElementById("apellidos").value.trim();
  const gmail = document.getElementById("correo-sign-up").value.trim();
  const password = document.getElementById("contraseña-sign-up").value.trim();
  const confirm = document.getElementById("confirmar-contraseña-sign-up").value.trim();
  const politica = registerForm.querySelector("input[type='checkbox']").checked;

  /** @brief Validaciones previas */
  if (!nombre || !apellidos || !gmail || !password || !confirm) {
    mostrarMensaje("register", "Por favor, completa todos los campos.");
    return;
  }

  /** @brief Validar fortaleza de contraseña */
  const tieneNum = /\d/.test(password);
  const tieneMay = /[A-Z]/.test(password);
  const tieneEsp = /[!@#$%^&*(),.?":{}|<>]/.test(password);
  if (password.length < 8 || !tieneNum || !tieneMay || !tieneEsp) {
    mostrarMensaje("register",
      "La contraseña debe tener 8 o más caracteres , un número, una mayúscula y un carácter especial.",
      5000);
    return;
  }

  if (password !== confirm) {
    mostrarMensaje("register", "Las contraseñas no coinciden.");
    return;
  }

  if (!politica) {
    mostrarMensaje("register", "Debes aceptar la política de privacidad.");
    return;
  }

  try {
    const response = await fetch("../api/index.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        accion: "registrarUsuario",
        nombre,
        apellidos,
        gmail,
        password
      })
    });

    const data = await response.json();

    if (data.status === "ok") {
      mostrarMensaje("register", "Registro correcto. Revisa tu correo para activar la cuenta.");
      msgRegister.style.color = "green";
      setTimeout(() => container.classList.remove("active"), 1500);
    } else {
      mostrarMensaje("register", data.message || "Error al registrarse.");
    }
  } catch (err) {
    console.error(err);
    mostrarMensaje("register", "Error de conexión con el servidor.");
  }
});

/**
 * @section Popup de la politica de privacidad
 */

/** @brief Abrir el popup cuando se pulsa en "popup-politica" */
const popupLinks = document.querySelectorAll(".popup-politica");
const popup = document.getElementById("popup-politica");
const btnAccept = document.getElementById("btnAccept");
const popupText = document.getElementById("popupText");
const closePopup = document.getElementById("closePopup");
const inputCheckmark = document.getElementById("checkmark");
const checkmarkSpan = registerForm.querySelector(".politica .checkmark");


/** @brief Abrir popup al pulsar el texto  */
popupLinks.forEach(link => {
  link.addEventListener("click", () => {
    popup.style.display = "flex";
    btnAccept.disabled = true; // Reiniciamos
    popupText.scrollTop = 0; // Scroll al inicio
  });
});

/** @brief ABRIR POPUP AL PULSAR LA CASILLA VISIBLE (SPAN) */
checkmarkSpan.addEventListener("click", (e) => {
  e.preventDefault();

  /** @brief Si el checkbox real está deshabilitado, es porque necesita aceptar la política. */
  if (inputCheckmark.disabled) {
    /** @brief Comportamiento 1: Abrir Popup */
    popup.style.display = "flex";
    btnAccept.disabled = true;
    popupText.scrollTop = 0;

  } else {
    /** @brief Comportamiento 2: Simular clic en el checkbox real para marcar/desmarcar */

    inputCheckmark.checked = !inputCheckmark.checked;

  }
});

/** @brief Cerrar popup al pulsar la X */
closePopup.addEventListener("click", () => {
  popup.style.display = "none";
});

/** @brief Detectar scroll al final */
popupText.addEventListener("scroll", () => {
  if (popupText.scrollTop + popupText.clientHeight >= popupText.scrollHeight - 1) {
    btnAccept.disabled = false;
  }
});

/** @brief Al pulsar aceptar, cerrar popup y habilitar el checkbox */
btnAccept.addEventListener("click", () => {
  popup.style.display = "none";
  inputCheckmark.disabled = false;
  inputCheckmark.checked = true;
});
