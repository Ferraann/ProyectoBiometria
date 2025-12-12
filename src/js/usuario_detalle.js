// ------------------------------------------------------------------
// Fichero: usuario_detalle.js
// Autor: Manuel
// Fecha: 11/12/2025
// ------------------------------------------------------------------
// Descripción:
//  Script que carga la ficha completa de un usuario del sistema.
//  
// Funcionalidad:
//  - Muestra datos básicos (nombre, apellidos, email, puntos) y foto de perfil.
//  - Muestra la lista de sensores actualmente asignados al usuario.
//  - Muestra los roles actuales (Administrador/Técnico).
//  - Permite a los Administradores modificar los roles del usuario a través de switches (funcionalidad restringida por permisos).
// ------------------------------------------------------------------
import { obtenerRoles } from "./permisos.js";

// ID del usuario logueado (quien usa la página)
const idUsuarioActivo = parseInt(
  window.sessionStorage.getItem("idUsuario") || "0"
);
let roles = null; // Almacenará los roles del usuario activo

document.addEventListener("DOMContentLoaded", () => {
  const API_URL = "../api/index.php";
  const params = new URLSearchParams(location.search);
  const idUsuario = params.get("id"); // ID del usuario cuyo perfil se está viendo

  if (!idUsuario) {
    alert("No se indicó usuario");
    location.href = "incidencias.html";
    return;
  }

  const chkAdmin = document.getElementById("chk-admin");
  const chkTec = document.getElementById("chk-tecnico");
  const btnSave = document.getElementById("btn-guardar");

  let esAdmin; // Roles originales del perfil VISTO
  let esTec; // Roles originales del perfil VISTO

  /* 1. Cargar datos básicos + foto ----------------------------- */
  (async () => {
    try {
      // Obtener roles del usuario ACTIVO
      roles = await obtenerRoles(idUsuarioActivo);

      // 1.1 Cargar datos del usuario que se está viendo
      const [resUser, resFoto] = await Promise.all([
        fetch(`${API_URL}?accion=getUsuarioXId&id=${idUsuario}`),
        fetch(`${API_URL}?accion=getFotoPerfil&id=${idUsuario}`),
      ]);
      const user = await resUser.json();
      if (!user || user.status === "error")
        throw new Error("Usuario no encontrado");

      document.getElementById(
        "titulo-perfil"
      ).textContent = `Perfil de ${user.nombre}`;
      document.getElementById("user-nombre").textContent = user.nombre;
      document.getElementById("user-apellidos").textContent =
        user.apellidos || "-";
      document.getElementById("user-gmail").textContent = user.gmail;
      document.getElementById("user-puntos").textContent = user.puntos || 0;

      // Cargar foto de perfil
      const fotoPerfilElement = document.getElementById("foto-perfil");
      if (resFoto.ok) {
        const blob = await resFoto.blob();
        fotoPerfilElement.src = URL.createObjectURL(blob);
      }

      /* 2. Cargar roles actuales del perfil VISTO ---------------------------------- */
      const [resAdm, resTec] = await Promise.all([
        fetch(`${API_URL}?accion=esAdministrador&id=${idUsuario}`),
        fetch(`${API_URL}?accion=esTecnico&id=${idUsuario}`),
      ]);
      const { es_admin: admin } = await resAdm.json();
      const { es_tecnico: tec } = await resTec.json();

      chkAdmin.checked = admin;
      chkTec.checked = tec;

      esAdmin = admin;
      esTec = tec;

      // Aplicar lógica de permisos: Deshabilita los switches si no es Admin
      aplicarLogicaPermisos(roles, chkAdmin, chkTec, btnSave);

      /* 3. Cargar sensores actuales -------------------------- */
      const resSens = await fetch(
        `${API_URL}?accion=getSensoresDeUsuario&id=${idUsuario}`
      );
      const sensores = await resSens.json();
      const lista = document.getElementById("lista-sensores");
      lista.innerHTML =
        !sensores || sensores.length === 0
          ? '<li class="empty">Sin sensores asignados</li>'
          : sensores
              .map(
                (s) =>
                  `<li><strong>${s.nombre || s.mac}</strong> – ${
                    s.modelo || "Sin modelo"
                  }</li>`
              )
              .join("");
    } catch (e) {
      alert(e.message);
      location.href = "incidencias.html";
    }
  })();

  /* 4. Guardar cambios de roles ------------------------------- */
  btnSave.addEventListener("click", async () => {
    // VERIFICACIÓN DE SEGURIDAD CRÍTICA
    if (!roles || !roles.esAdmin) {
      alert(
        "Acceso denegado: Solo los administradores pueden modificar roles."
      );
      // Restaurar el estado original (por si el usuario ha manipulado el DOM)
      chkAdmin.checked = esAdmin;
      chkTec.checked = esTec;
      return;
    }

    const nuevoAdmin = chkAdmin.checked;
    const nuevoTec = chkTec.checked;

    //Evitar que el Admin se quite su propio rol sin confirmar
    if (
      String(idUsuario) === String(idUsuarioActivo) &&
      esAdmin &&
      !nuevoAdmin
    ) {
      const confirmar = confirm(
        "Estás a punto de quitarte tu propio rol de Administrador. ¿Estás seguro? Perderás inmediatamente los privilegios de gestión."
      );
      if (!confirmar) {
        chkAdmin.checked = true; // Restaurar el switch
        return;
      }
    }

    try {
      if (nuevoAdmin !== esAdmin) {
        const acc = nuevoAdmin ? "asignarAdministrador" : "quitarAdministrador";
        const r = await fetch(API_URL, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ accion: acc, usuario_id: idUsuario }),
        });
        const res = JSON.parse(await r.text());
        if (res.status !== "ok") throw new Error(res.mensaje || "Error admin");
      }

      if (nuevoTec !== esTec) {
        const acc = nuevoTec ? "asignarTecnico" : "quitarTecnico";
        const r = await fetch(API_URL, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ accion: acc, usuario_id: idUsuario }),
        });
        const res = JSON.parse(await r.text());
        if (res.status !== "ok")
          throw new Error(res.mensaje || "Error técnico");
      }

      alert("Cambios guardados");
      location.reload();
    } catch (e) {
      alert("Error al guardar: " + e.message);
    }
  });
});

function aplicarLogicaPermisos(roles, chkAdmin, chkTec, btnSave) {
  if (roles && roles.esAdmin) {
    // Es Administrador: switches habilitados, botón visible
    chkAdmin.disabled = false;
    chkTec.disabled = false;
    if (btnSave) btnSave.style.display = "block";
  } else {
    // No es Administrador: switches deshabilitados, botón oculto
    chkAdmin.disabled = true; // Se ve, pero no se puede interactuar
    chkTec.disabled = true;
    if (btnSave) btnSave.style.display = "none"; // Ocultar el botón de guardar
  }
}
