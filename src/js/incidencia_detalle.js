/**
 * @file incidencia_detalle.js
 * @brief Gestión de la vista detallada de una incidencia.
 * @details Este script recupera parámetros de la URL para consultar una API, renderiza la información
 * de la incidencia, el historial, y gestiona las acciones de cambio de estado y asignación de técnicos.
 * @author Manuel
 * @date 2/12/2025
 */

/**
 * @name ParametrosURL
 * @{
 */
/** @brief Analizador de parámetros de la consulta en la URL. */
const params = new URLSearchParams(location.search);
/** @brief Identificador único de la incidencia extraído de la URL. */
const idIncidencia = params.get("id");
/** @} */

/** @brief Identificador del usuario activo recuperado de la sesión. */
const idUsuarioActivo = parseInt(window.sessionStorage.getItem("idUsuario") || "0");

/**
 * @brief Validación de entrada.
 * @details Si no existe un ID en la URL, redirige al listado general de incidencias.
 */
if (!idIncidencia) {
  alert("No se indicó una incidencia.");
  location.href = "incidencias.html";
}

/**
 * @brief Función autoejecutable asíncrona que inicializa la carga de datos.
 * @details Realiza múltiples peticiones fetch para obtener la incidencia, el usuario, el técnico
 * y el catálogo de estados. Implementa un bloque try/catch para manejar errores de red o de datos.
 * * @async
 * @returns {Promise<void>}
 */
(async () => {
  try {
    /**
     * @section CargaDatos
     * @brief Peticiones al backend.
     */

    /**
     * @brief 1. Cargar datos básicos de la incidencia
     * @note Muestra la fila de finalización solo si el dato existe en el objeto de la incidencia.
     */
    const resInc = await fetch(
      `../api/index.php?accion=getIncidenciaXId&id=${idIncidencia}`
    );
    const inc = await resInc.json();
    if (!inc || inc.status === "error")
      throw new Error("Incidencia no encontrada");

    /**
     * @brief 2. Carga de información de usuario (creador) y técnico.
     * Se maneja la ausencia de técnico para evitar peticiones a IDs nulos.
     */

    // 2a. Cargar el usuario creador (siempre debería existir)
    const resUser = await fetch(
      `../api/index.php?accion=getUsuarioXId&id=${inc.id_user}`
    );
    const user = await resUser.json();

    // 2b. Cargar el técnico (SOLO si existe id_tecnico)
    let tec = { status: "error" }; // Valor por defecto
    if (inc.id_tecnico && inc.id_tecnico !== "0" && inc.id_tecnico !== 0) {
      try {
        const resTec = await fetch(
          `../api/index.php?accion=getUsuarioXId&id=${inc.id_tecnico}`
        );
        tec = await resTec.json();
      } catch (error) {
        console.warn("No se pudo obtener datos del técnico asignado.");
      }
    }

    /**
     * @brief 3. Cargar catálogo de estados disponibles
     */
    const resEst = await fetch("../api/index.php?accion=getEstadosIncidencia");
    const estados = await resEst.json();

    /**
     * @section Renderizado
     * @brief Inyección de datos en el DOM.
     */
    document.getElementById(
      "incidencia-titulo-id"
    ).textContent = `${inc.titulo} (${inc.id})`;
    document.getElementById("descripcion-texto").textContent =
      inc.descripcion || "-";

    /**
     * @brief 4. Formateo del nombre del Usuario Creador
     */
    const nombreUser =
      user.status !== "error"
        ? `${user.nombre} ${user.apellidos ?? ""}`.trim()
        : "Anónimo";
    document.getElementById("link-usuario").textContent = nombreUser;
    document.getElementById(
      "link-usuario"
    ).href = `usuario_detalle.html?id=${inc.id_user}&perfil=usuario`;
    /** * ... (resto del código inicial igual hasta llegar al renderizado del técnico)
     */

    // 5. Formateo del nombre del Técnico Asignado
    const nombreTec =
      tec && tec.status === "ok"
        ? `${tec.nombre} ${tec.apellidos ?? ""}`.trim()
        : "Sin asignar";

    document.getElementById("link-tecnico").textContent = nombreTec;
    document.getElementById("link-tecnico").href =
      tec && tec.id ? `usuario_detalle.html?id=${tec.id}&perfil=tecnico` : "#";

    /**
     * @section Lógica de Reasignación
     * @brief Configura el botón de asignación para permitir reemplazos.
     */
    const btnAsignarme = document.getElementById("btn-asignarme");

    // Si el usuario en sesión YA es el técnico de esta incidencia
    if (inc.id_tecnico == idUsuarioActivo) {
      btnAsignarme.textContent = "Ya eres el técnico";
      btnAsignarme.disabled = true;
      btnAsignarme.style.opacity = "0.5";
    } else if (inc.id_tecnico && inc.id_tecnico != 0) {
      // Si ya hay un técnico pero no es el usuario actual
      btnAsignarme.textContent = "Reemplazar técnico anterior";
      btnAsignarme.style.backgroundColor = "#e67e22"; // Color naranja de advertencia/cambio
    } else {
      // Si no hay técnico asignado
      btnAsignarme.textContent = "Asignarme incidencia";
      btnAsignarme.style.backgroundColor = "#28a745"; // Color verde
    }

    /**
     * @brief Evento para que el usuario en sesión se asigne la incidencia (o reemplace al actual).
     */
    btnAsignarme.addEventListener("click", async () => {
      if (!idUsuarioActivo) return alert("No hay usuario activo en la sesión.");

      const mensajeConfirm =
        inc.id_tecnico && inc.id_tecnico != 0
          ? `Esta incidencia ya tiene un técnico (${nombreTec}). ¿Deseas reemplazarlo y convertirte en el nuevo responsable?`
          : "¿Confirmas que quieres asignarte esta incidencia?";

      if (!confirm(mensajeConfirm)) return;

      try {
        const res = await fetch("../api/index.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            accion: "asignarmeTecnicoIncidencia",
            incidencia_id: idIncidencia,
            tecnico_id: idUsuarioActivo,
          }),
        });

        const data = await res.json();
        if (data.status === "ok") {
          alert("Te has asignado la incidencia con éxito.");
          location.reload();
        } else {
          alert("Error al asignar: " + data.mensaje);
        }
      } catch (error) {
        console.error("Error en la petición:", error);
        alert("Error de conexión con el servidor.");
      }
    });

    // ... (Resto del código de guardar estado igual)
    /**
     * @brief 6. Gestión de visibilidad de la fecha de cierre.
     * Muestra la fila de finalización solo si el dato existe en el objeto de la incidencia.
     */
    const filaFin = document.getElementById("fila-finalizacion");
    if (filaFin) {
      if (inc.fecha_finalizacion) {
        filaFin.style.display = "flex";
        document.getElementById("fecha-finalizacion").textContent = new Date(
          inc.fecha_finalizacion
        ).toLocaleString();
      } else {
        filaFin.style.display = "none";
      }
    }

    /**
     * @brief 7. Rellenar select de estados dinámicamente
     */
    const select = document.getElementById("select-estados");
    select.innerHTML = "";
    estados.forEach((e) => {
      const opt = document.createElement("option");
      opt.value = e.id;
      opt.textContent = e.nombre;
      select.appendChild(opt);
    });
    select.value = inc.estado_id;

    /**
     * @section Acciones
     * @brief Listeners para interactuar con la API.
     */

    /**
     * @brief Evento para actualizar el estado de la incidencia.
     * @listens click
     */
    document
      .getElementById("btn-guardar-estado")
      .addEventListener("click", async () => {
        const nuevoEstadoId = select.value;
        const res = await fetch("../api/index.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            accion: "actualizarEstadoIncidencia",
            incidencia_id: idIncidencia,
            estado_id: nuevoEstadoId,
          }),
        });
        const data = await res.json();
        if (data.status === "ok") {
          alert("Estado actualizado.");
          location.reload();
        } else {
          alert("Error: " + data.mensaje);
        }
      });

    /**
     * @brief Evento para que el usuario en sesión se asigne la incidencia.
     * @listens click
     */
    document
      .getElementById("btn-asignarme")
      .addEventListener("click", async () => {
        if (!idUsuarioActivo) return alert("No hay usuario activo.");
        const ok = confirm("¿Quieres asignarte esta incidencia?");
        if (!ok) return;

        const res = await fetch("../api/index.php", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            accion: "asignarmeTecnicoIncidencia",
            incidencia_id: idIncidencia,
            tecnico_id: idUsuarioActivo,
          }),
        });
        const data = await res.json();
        if (data.status === "ok") {
          alert("Incidencia asignada.");
          location.reload();
        } else {
          alert("Error: " + data.mensaje);
        }
      });
  } catch (e) {
    /** @brief Manejo global de errores: Alerta y redirección. */
    alert(e.message);
    location.href = "incidencias.html";
  }
})();
