/* 
===============================================================================
NOMBRE: login.js
DESCRIPCIÓN: 
COPYRIGHT: © 2025 AITHER. Todos los derechos reservados.
FECHA: 04/11/2025
AUTOR: Ferran y Manuel
APORTACIÓN: 
===============================================================================
*/
// ELEMENTOS DEL DOM
const container = document.getElementById("container");
const signUpBtn = document.getElementById("signUpBtn");
const signInBtn = document.getElementById("signInBtn");

// FORMULARIOS
const loginForm = document.querySelector(".sign-in-container form");
const registerForm = document.querySelector(".sign-up-container form");

// Botón del header
const botonHeader = document.querySelector("nav ul li:last-child a");

// ============================
// MENSAJES DE ERROR (independientes)
// ============================

// Estilos del mensaje para login
let msgLogin = document.createElement("p");
msgLogin.id = "message-login";
msgLogin.style.marginTop = "10px";
msgLogin.style.fontWeight = "600";
msgLogin.style.fontSize = "14px"
msgLogin.style.textAlign = "center";
msgLogin.style.color = "white";
msgLogin.classList.remove("fade-out");

// Estilos del mensaje para registro
let msgRegister = document.createElement("p");
msgRegister.id = "message-register";
msgRegister.style.marginTop = "10px";
msgRegister.style.fontWeight = "600";
msgRegister.style.fontSize = "14px";
msgRegister.style.display = "flex";
msgRegister.style.alignItems = "flex-start";
msgRegister.style.color = "white";
msgRegister.classList.remove("fade-out");


// ============================
// MOSTRAR / OCULTAR CONTRASEÑA
// ============================

document.querySelectorAll(".toggle-password").forEach(icon => {
  icon.addEventListener("click", () => {
    const input = document.getElementById(icon.dataset.input);
    const esPass = input.type === "password";
    input.type = esPass ? "text" : "password";
    icon.src = esPass ? "../img/ojo.png" : "../img/ojo-cerrado.png";
  });
});


// ============================
// ANIMACIÓN ENTRE LOGIN Y REGISTRO
// ============================

// Cuando se pulsa el boton de registro se añade la clase active
signUpBtn.addEventListener("click", () => {
  container.classList.add("active");
});

// Cuando se pulsa el boton de inicio de sesión se quita la clase active
signInBtn.addEventListener("click", () => {
  container.classList.remove("active");
});

// Cambios de color del botón del header según el estado
container.addEventListener("transitionend", () => {
  botonHeader.classList.toggle("active", container.classList.contains("active"));
});

// Actualizar el estado del boton de inicio de sesión del header
function updateHeaderLoginButton() {
  const isRegister = container.classList.contains("active");
  botonHeader.classList.toggle("disabled", !isRegister);
  botonHeader.classList.toggle("enabled", isRegister);
}

// Llamada inicial al cargar la página
updateHeaderLoginButton();

// Actualizar tras animaciones o clics
container.addEventListener("transitionend", updateHeaderLoginButton);
signUpBtn.addEventListener("click", updateHeaderLoginButton);
signInBtn.addEventListener("click", updateHeaderLoginButton);

// Interceptar click en el header
botonHeader.addEventListener("click", (e) => {
  e.preventDefault();
  // Si el hoton header tiene la clase disabled nada
  if (botonHeader.classList.contains("disabled")) return;
  // Si no, es decir, que tiene la clase active, primero se le quita la clase active y se llama a updateHeaderLoginButton()
  container.classList.remove("active");
  updateHeaderLoginButton();
  document.getElementById("correo-sign-in")?.focus();
});

// ---------------------------------------------------------------------------
// FUNCIÓN AUXILIAR: Mostrar mensaje con desvanecimiento
// ---------------------------------------------------------------------------
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

// ---------------------------------------------------------------------------
// EVENTO: LOGIN → consume API vía index.php (acción: login)
// ---------------------------------------------------------------------------
loginForm.addEventListener("submit", async e => {
  e.preventDefault();

  const gmail = document.getElementById("correo-sign-in").value.trim();
  const password = document.getElementById("contraseña-sign-in").value.trim();

  if (!gmail || !password) {
    mostrarMensaje("login", "Por favor, rellena todos los campos.");
    return;
  }

  try {
    const response = await fetch("../api/index.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ accion: "login", gmail, password })
    });

    const data = await response.json();

    if (data.status === "ok") {
      // Guardar usuario en localStorage y redirigir
      localStorage.setItem("user", JSON.stringify(data.usuario));
      window.location.href = "dashboard.php"; // si el login es exitoso lleva aqui, cambiar
    } else {
      mostrarMensaje("login", data.message || "Credenciales incorrectas.");
    }
  } catch (err) {
    console.error(err);
    mostrarMensaje("login", "Error de conexión con el servidor.");
  }
});

// ---------------------------------------------------------------------------
// EVENTO: REGISTRO → consume API vía index.php (acción: registrarUsuario)
// ---------------------------------------------------------------------------
registerForm.addEventListener("submit", async e => {
  e.preventDefault();

  const nombre = document.getElementById("nombre").value.trim();
  const apellidos = document.getElementById("apellidos").value.trim();
  const gmail = document.getElementById("correo-sign-up").value.trim();
  const password = document.getElementById("contraseña-sign-up").value.trim();
  const confirm = document.getElementById("confirmar-contraseña-sign-up").value.trim();
  const politica = registerForm.querySelector("input[type='checkbox']").checked;

  // Validaciones previas
  if (!nombre || !apellidos || !gmail || !password || !confirm) {
    mostrarMensaje("register", "Por favor, completa todos los campos.");
    return;
  }

  // Validar fortaleza de contraseña
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


// ---------------------------------------------------------------------------
// Popup de la politica de privacidad
// ---------------------------------------------------------------------------

// Abrir el popup cuando se pulsa en "popup-politica"
const popupLinks = document.querySelectorAll(".popup-politica");
const popup = document.getElementById("popup-politica");
const btnAccept = document.getElementById("btnAccept");
const popupText = document.getElementById("popupText");
const closePopup = document.getElementById("closePopup");
const checkmark = document.getElementById("checkmark");

// Abrir popup al pulsar el texto
popupLinks.forEach(link => {
  link.addEventListener("click", () => {
    popup.style.display = "flex";
    btnAccept.disabled = true; // Reiniciamos
    popupText.scrollTop = 0; // Scroll al inicio
  });
});

// Cerrar popup al pulsar la X
closePopup.addEventListener("click", () => {
  popup.style.display = "none";
});

// Detectar scroll al final
popupText.addEventListener("scroll", () => {
  if (popupText.scrollTop + popupText.clientHeight >= popupText.scrollHeight - 1) {
    btnAccept.disabled = false;
  }
});

// Al pulsar aceptar, cerrar popup y habilitar el checkbox
btnAccept.addEventListener("click", () => {
  popup.style.display = "none";
  checkmark.disabled = false; // Ahora sí se puede marcar
  checkmark.checked = true;   // Marcamos automáticamente
});
