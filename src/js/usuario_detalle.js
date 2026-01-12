/**
 * @file usuario_detalle.js
 * @brief Gestión de la ficha detallada de usuarios y administración de roles.
 * @details Este script permite visualizar el perfil completo de cualquier usuario del sistema,
 * incluyendo sus datos personales, foto de perfil y sensores vinculados. Además, proporciona
 * la lógica para que los administradores puedan promover o degradar roles (Admin/Técnico).
 * @author Manuel
 * @date 3/12/2025
 */

document.addEventListener("DOMContentLoaded", () => {

  /** @name Configuración y Estado
   * @{ */
  /** @brief URL del punto de entrada de la API. */
  const API_URL = "../api/index.php";
  /** @brief Analizador de parámetros para obtener el ID del usuario desde la URL. */
  const params = new URLSearchParams(location.search);
  /** @brief Identificador del usuario cuyo perfil se está visualizando. */
  const idUsuario = params.get("id");

  /** @brief Estado original del rol de administrador del usuario visto. @type {boolean} */
  let esAdmin;
  /** @brief Estado original del rol de técnico del usuario visto. @type {boolean} */
  let esTec;
  /** @} */

  /**
   * @brief Validación de seguridad inicial.
   * @details Redirige al listado si no se especifica un ID de usuario en la URL.
   */
  if (!idUsuario) {
    alert("No se indicó usuario");
    location.href = "incidencias.html";
    return;
  }

  /** @name Elementos de Control de Roles
   * @{ */
  /** @brief Switch para el rol de administrador. @type {HTMLInputElement} */
  const chkAdmin = document.getElementById("chk-admin");
  /** @brief Switch para el rol de técnico. @type {HTMLInputElement} */
  const chkTec = document.getElementById("chk-tecnico");
  /** @brief Botón para persistir los cambios de roles en la base de datos. @type {HTMLButtonElement} */
  const btnSave = document.getElementById("btn-guardar");
  /** @} */

  /**
   * @section 1. CARGA DE DATOS (IIFE ASÍNCRONO)
   */

  /**
   * @brief Función autoejecutable que inicializa el perfil del usuario.
   * @details Ejecuta tres bloques de carga en paralelo:
   * 1. Datos personales y foto de perfil (Blob).
   * 2. Roles actuales (Admin/Técnico).
   * 3. Listado de sensores vinculados.
   * @async
   */
  (async () => {
    try {
      /**
       * @section CargaPerfil
       * @brief Recuperación de información personal y multimedia.
       */
      const [resUser, resFoto] = await Promise.all([
        fetch(`${API_URL}?accion=getUsuarioXId&id=${idUsuario}`),
        fetch(`${API_URL}?accion=getFotoPerfil&id=${idUsuario}`),
      ]);

      const user = await resUser.json();
      if (!user || user.status === "error") throw new Error("Usuario no encontrado");

      // Inyección de datos en el DOM
      document.getElementById("titulo-perfil").textContent = `Perfil de ${user.nombre}`;
      document.getElementById("user-nombre").textContent = user.nombre;
      document.getElementById("user-apellidos").textContent = user.apellidos || "-";
      document.getElementById("user-gmail").textContent = user.gmail;
      document.getElementById("user-puntos").textContent = user.puntos || 0;

      /** @brief Gestión de la imagen de perfil como objeto URL (Blob). */
      const fotoPerfilElement = document.getElementById("foto-perfil");
      if (resFoto.ok) {
        const blob = await resFoto.blob();
        fotoPerfilElement.src = URL.createObjectURL(blob);
      }

      /**
       * @section CargaRoles
       * @brief Recuperación de permisos actuales del usuario visualizado.
       */
      const [resAdm, resTec_api] = await Promise.all([
        fetch(`${API_URL}?accion=esAdministrador&id=${idUsuario}`),
        fetch(`${API_URL}?accion=esTecnico&id=${idUsuario}`),
      ]);
      const { es_admin: admin } = await resAdm.json();
      const { es_tecnico: tec } = await resTec_api.json();

      /** @brief Sincronización de switches */
      chkAdmin.checked = admin;
      chkTec.checked = tec;
      esAdmin = admin;
      esTec = tec;

      /**
       * @section CargaSensores
       * @brief Obtención de los dispositivos asignados al usuario.
       */
      const resSens = await fetch(`${API_URL}?accion=getSensoresDeUsuario&id=${idUsuario}`);
      const sensores = await resSens.json();
      const lista = document.getElementById("lista-sensores");

      lista.innerHTML = !sensores || sensores.length === 0
          ? '<li class="empty">Sin sensores asignados</li>'
          : sensores.map(s => `<li><strong>${s.nombre || s.mac}</strong> – ${s.modelo || "Sin modelo"}</li>`).join("");

    } catch (e) {
      alert(e.message);
      location.href = "incidencias.html";
    }
  })();

  /**
   * @section 2. GUARDAR CAMBIOS DE ROLES
   */

  /**
   * @brief Gestiona la actualización de privilegios del usuario.
   * @details Implementa una lógica de seguridad por pasos:
   * 1. Verifica que el usuario que ejecuta la acción tenga rol de Admin.
   * 2. Evita que un administrador se elimine a sí mismo sin una confirmación explícita.
   * 3. Realiza peticiones POST individuales por cada rol modificado.
   * @listens click
   * @async
   */
  btnSave.addEventListener("click", async () => {

    /** @brief Verificación de seguridad del lado del cliente */
    if (typeof roles === 'undefined' || !roles.esAdmin) {
      alert("Acceso denegado: Solo los administradores pueden modificar roles.");
      chkAdmin.checked = esAdmin;
      chkTec.checked = esTec;
      return;
    }

    const nuevoAdmin = chkAdmin.checked;
    const nuevoTec = chkTec.checked;

    /** @brief Protección ante auto-degradación de privilegios. */
    if (String(idUsuario) === String(idUsuarioActivo) && esAdmin && !nuevoAdmin) {
      const confirmar = confirm("Estás a punto de quitarte tu propio rol de Administrador. ¿Estás seguro?");
      if (!confirmar) {
        chkAdmin.checked = true;
        return;
      }
    }

    try {
      /** @brief Actualización secuencial de roles mediante la API. */
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
        if (res.status !== "ok") throw new Error(res.mensaje || "Error técnico");
      }

      alert("Cambios guardados");
      location.reload();
    } catch (e) {
      alert("Error al guardar: " + e.message);
    }
  });
});