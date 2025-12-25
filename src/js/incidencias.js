/**
 * @file incidencias.js
 * @brief Script principal para la visualización y gestión del listado de incidencias.
 * @details Este script se encarga de recuperar el catálogo completo de incidencias,
 * gestionar filtros en tiempo real, controlar el orden de visualización y cargar de forma
 * optimizada las imágenes asociadas.
 * @author Manuel
 * @date 7/12/2025
 */

/** * @brief Identificador del usuario activo recuperado de la sesión.
 * @type {number}
 */
const idUsuarioActivo = parseInt(window.sessionStorage.getItem("idUsuario") || "0");

/**
 * @section ELEMENTOS DEL DOM
 */

/** @name ElementosDOM
 * @{
 */
/** @brief Contenedor principal de la lista de incidencias. @type {HTMLElement} */
const lista = document.getElementById("lista-incidencias");
/** @brief Input de texto para búsqueda libre. @type {HTMLInputElement} */
const buscador = document.getElementById("buscador");
/** @brief Selector para filtrar por técnico. @type {HTMLSelectElement} */
const selTecnico = document.getElementById("filtroTecnico");
/** @brief Selector para filtrar por estado. @type {HTMLSelectElement} */
const selEstado = document.getElementById("filtroEstado");
/** @brief Selector para filtrar por presencia de fotos. @type {HTMLSelectElement} */
const selFotos = document.getElementById("filtroFotos");
/** @brief Selector para el orden de fecha. @type {HTMLSelectElement} */
const selOrden = document.getElementById("ordenFecha");
/** @} */

/**
 * @section FEEDBACK VISUAL
 */

/** @brief Párrafo de notificación creado dinámicamente. */
const msg = document.createElement("p");
msg.id = "message-incidencias";
msg.style.cssText = "margin-top:10px;font-weight:600;font-size:14px;text-align:center;color:white;";
document.querySelector("header").after(msg);

/* ----------  ---------- */
/**
 * @section VARIABLES DE ESTADO
 */

/** @brief URL base de la API. */
const API_URL = "../api/index.php";
/** @brief Almacén de todas las incidencias cargadas inicialmente. @type {Object[]} */
let incidencias = [];
/** @brief Almacén de incidencias que superan los filtros actuales. @type {Object[]} */
let incidenciasFiltradas = [];
/** @brief Mapa de caché para disponibilidad de fotos (ID -> Boolean). @type {Object} */
let mapaFotos = {};

/**
 * @brief Muestra un mensaje temporal en la interfaz con efecto de desvanecimiento.
 * @param {string} texto Mensaje a mostrar.
 * @param {number} [dur=3000] Duración en milisegundos.
 */
const mostrarMensaje = (texto, dur = 3000) => {
  msg.textContent = texto;
  msg.classList.remove("fade-out");
  setTimeout(() => msg.classList.add("fade-out"), dur);
};

/**
 * @section CARGA INICIAL
 */

/**
 * @brief Inicializa la aplicación cargando datos desde la API al cargar el DOM.
 * @details Realiza la limpieza del JSON recibido, rellena los filtros y ejecuta el primer renderizado.
 * @listens DOMContentLoaded
 * @async
 */
window.addEventListener("DOMContentLoaded", async () => {
  try {
    const res = await fetch(`${API_URL}?accion=getTodasIncidencias`);
    const textoCrudo = await res.text();

    // Limpieza de caracteres previos al JSON (evitar errores de parseo)
    const textoLimpio = textoCrudo.replace(/^[^\[\{]*/, "");
    const data = JSON.parse(textoLimpio);

    if (!Array.isArray(data)) throw new Error("Respuesta no es array");
    incidencias = data;

    rellenarSelectTecnicos();
    rellenarSelectEstado();

    /** @brief Determina qué incidencias tienen fotos antes de renderizar. */
    await detectarFotos();

    aplicarFiltrosYRender();
  } catch (e) {
    console.error(e);
    mostrarMensaje("Error al cargar incidencias.", 4000);
  }
});

/**
 * @section RELLENADO DE FILTROS
 */

/**
 * @brief Extrae y rellena el selector de técnicos con valores únicos del dataset.
 */
function rellenarSelectTecnicos() {
  const tecnicos = [...new Set(incidencias.map((i) => i.id_tecnico).filter(Boolean))];
  tecnicos.forEach((id) => {
    const opt = document.createElement("option");
    opt.value = id;
    opt.textContent = `Técnico ID ${id}`;
    selTecnico.appendChild(opt);
  });
}

/**
 * @brief Extrae y rellena el selector de estados con valores únicos del dataset.
 */
function rellenarSelectEstado() {
  const estados = [...new Set(incidencias.map((i) => i.estado))];
  estados.forEach((e) => {
    const opt = document.createElement("option");
    opt.value = e;
    opt.textContent = e;
    selEstado.appendChild(opt);
  });
}

/**
 * @section GESTIÓN DE FOTOS
 */

/**
 * @brief Identifica qué incidencias poseen fotos adjuntas.
 * @details Ejecuta peticiones concurrentes para optimizar la velocidad de carga inicial.
 * @async
 */
async function detectarFotos() {
  const promesas = incidencias.map(async (inc) => {
    try {
      const res = await fetch(`${API_URL}?accion=getFotosIncidencia&incidencia_id=${inc.id}`);
      const data = await res.json();
      mapaFotos[inc.id] = (data.status === "ok" && data.fotos?.length > 0);
    } catch {
      mapaFotos[inc.id] = false;
    }
  });
  await Promise.all(promesas);
}

/**
 * @section LÓGICA DE FILTRADO
 */

/**
 * @brief Filtra y ordena el listado de incidencias según los controles de la UI.
 * @details Tras el filtrado, actualiza el estado global 'incidenciasFiltradas' y dispara el render.
 */
function aplicarFiltrosYRender() {
  let filtradas = incidencias.filter((inc) => {
    const texto = buscador.value.toLowerCase();
    const coincideTexto = !texto ||
        inc.titulo.toLowerCase().includes(texto) ||
        inc.descripcion.toLowerCase().includes(texto) ||
        inc.estado.toLowerCase().includes(texto);

    const coincideTecnico = !selTecnico.value || inc.id_tecnico == selTecnico.value;
    const coincideEstado = !selEstado.value || inc.estado === selEstado.value;

    const fot = selFotos.value;
    const coincideFotos = !fot || (fot === "con" && mapaFotos[inc.id]) || (fot === "sin" && !mapaFotos[inc.id]);

    return coincideTexto && coincideTecnico && coincideEstado && coincideFotos;
  });

  /**
   * @brief Ordenación por fecha
   */
  const orden = selOrden.value;
  filtradas.sort((a, b) => {
    const dA = new Date(a.fecha_creacion);
    const dB = new Date(b.fecha_creacion);
    return orden === "asc" ? dA - dB : dB - dA;
  });

  incidenciasFiltradas = filtradas;
  renderIncidencias(incidenciasFiltradas);
}

/**
 * @section RENDERIZADO
 */

/**
 * @brief Genera e inyecta el HTML de las tarjetas de incidencia en el DOM.
 * @param {Object[]} datos Array de objetos de incidencia a dibujar.
 */
/**
 * @section RENDERIZADO
 */
function renderIncidencias(datos) {
  if (!datos.length) {
    lista.innerHTML = '<p style="text-align:center;">No se encontraron incidencias.</p>';
    return;
  }

  lista.innerHTML = datos.map(inc => {
    // Verificamos si ya tiene técnico para decidir si mostrar el botón
    const tieneTecnico = inc.id_tecnico && inc.id_tecnico != 0;
    
    return `
    <div class="incidencia">
      <h2><a href="incidencia_detalle.html?id=${inc.id}" class="titulo-incidencia">${inc.titulo}</a></h2>
      <p><strong>Descripción:</strong> ${inc.descripcion}</p>
      <p class="meta"><strong>Usuario:</strong> <a href="usuario_detalle.html?id=${inc.id_user}&perfil=usuario">${inc.usuario || "Anónimo"}</a></p>
      ${inc.id_sensor ? `<p class="meta"><strong>Sensor:</strong> ${inc.nombre_sensor}</p>` : ""}
      <p class="meta"><strong>Técnico:</strong> ${tieneTecnico ? `<a href="usuario_detalle.html?id=${inc.id_tecnico}&perfil=tecnico">${inc.tecnico}</a>` : "<em>Sin asignar</em>"}</p>
      <p class="meta"><strong>Estado:</strong> ${inc.estado}</p>
      <p class="meta"><strong>Fecha:</strong> ${new Date(inc.fecha_creacion).toLocaleString()}</p>
      
      <div class="acciones-incidencia" style="margin-top:10px;">
        ${!tieneTecnico ? `<button class="btn-asignar" onclick="asignarIncidencia(${inc.id})">Asignarme a mí</button>` : ""}
      </div>

      <div class="fotos" id="fotos-${inc.id}"></div>
    </div>
  `}).join("");

  cargarFotosVisibles();
}

/**
 * @brief Carga las imágenes de las incidencias que están actualmente en el render.
 * @details Este método actúa como un Lazy Load: solo pide las fotos de las incidencias que
 * han pasado el filtro actual para ahorrar ancho de banda.
 * @async
 */
async function cargarFotosVisibles() {
  for (const inc of incidenciasFiltradas) {
    const cont = document.getElementById(`fotos-${inc.id}`);
    if (mapaFotos[inc.id]) {
      try {
        const res = await fetch(`${API_URL}?accion=getFotosIncidencia&incidencia_id=${inc.id}`);
        const data = await res.json();
        if (cont && data.status === "ok") {
          cont.innerHTML = data.fotos.map(f => `<img src="data:image/jpeg;base64,${f.foto}" alt="Foto"/>`).join("");
        }
      } catch (e) { console.warn("Error en fotos:", inc.id); }
    } else if (cont) {
      cont.innerHTML = "<em>Sin fotos</em>";
    }
  }
}

/**
 * @section LISTENERS DE CONTROL
 */

/** @brief Listeners para actualizar la vista ante cualquier cambio en los filtros. */
buscador.addEventListener("input", aplicarFiltrosYRender);
selTecnico.addEventListener("change", aplicarFiltrosYRender);
selEstado.addEventListener("change", aplicarFiltrosYRender);
selFotos.addEventListener("change", aplicarFiltrosYRender);
selOrden.addEventListener("change", aplicarFiltrosYRender);

/**
 * @section ACCIONES DE TÉCNICO
 */

/**
 * @brief Envía una petición a la API para asignar el usuario actual a una incidencia.
 * @param {number} incidenciaId ID de la incidencia a reclamar.
 * @async
 */
async function asignarIncidencia(incidenciaId) {
  if (idUsuarioActivo === 0) {
    mostrarMensaje("Error: No se detecta sesión de usuario.");
    return;
  }

  if (!confirm("¿Deseas asignarte esta incidencia?")) return;

  try {
    const response = await fetch(API_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        accion: "asignarTecnicoIncidencia", 
        incidencia_id: incidenciaId,
        tecnico_id: idUsuarioActivo
      })
    });

    const resultado = await response.json();

    if (resultado.status === "ok") {
      mostrarMensaje("Incidencia asignada correctamente.");
      // Recargamos los datos de la API para refrescar la lista
      location.reload(); 
    } else {
      mostrarMensaje("Error: " + resultado.mensaje);
    }
  } catch (error) {
    console.error("Error en la asignación:", error);
    mostrarMensaje("Error de conexión con el servidor.");
  }
}