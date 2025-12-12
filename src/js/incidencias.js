// ------------------------------------------------------------------
// Fichero: incidencias.js
// Autor: Manuel
// Fecha: 11/12/2025
// ------------------------------------------------------------------
// Descripción:
//  Script principal para la visualización y gestión del listado de 
//  incidencias.
//  
// Funcionalidad:
//  - Carga todas las incidencias desde la API.
//  - Aplica filtros dinámicos (texto, estado, técnico, fotos) y ordenación.
//  - Implementa la lógica de permisos para restringir la vista (ej: Técnico solo ve sus asignadas).
//  - Renderiza las tarjetas de incidencia con enlaces a los detalles.
// // ------------------------------------------------------------------
// //Permisos
// //roles = await obtenerRoles(userId); //añadir esta linea en una carga inicial await
// import { obtenerRoles } from "./permisos.js";
// const idUsuarioActivo = parseInt(window.sessionStorage.getItem("idUsuario") || "0");
// let roles = null;

/* ---------- DOM ---------- */
const lista = document.getElementById("lista-incidencias");
const buscador = document.getElementById("buscador");
const selTecnico = document.getElementById("filtroTecnico");
const selEstado = document.getElementById("filtroEstado");
const selFotos = document.getElementById("filtroFotos");
const selOrden = document.getElementById("ordenFecha");

/* ---------- Mensaje feedback ---------- */
const msg = document.createElement("p");
msg.id = "message-incidencias";
msg.style.cssText =
  "margin-top:10px;font-weight:600;font-size:14px;text-align:center;color:white;";
document.querySelector("header").after(msg);

/* ---------- VARIOS ---------- */
const API_URL = "../api/index.php";
let incidencias = []; // todas las incidencias (raw)
let incidenciasFiltradas = []; // visibles tras filtros
let mapaFotos = {}; // incidencia_id -> tieneFotos (bool)

/* ---------- UTIL ---------- */
const mostrarMensaje = (texto, dur = 3000) => {
  msg.textContent = texto;
  msg.classList.remove("fade-out");
  setTimeout(() => msg.classList.add("fade-out"), dur);
};

/* ---------- CARGA INICIAL ---------- */
window.addEventListener("DOMContentLoaded", async () => {
  try {
    /* 1. Incidencias */
    //roles = await obtenerRoles(idUsuarioActivo);
    const res = await fetch(`${API_URL}?accion=getTodasIncidencias`);
    const textoCrudo = await res.text();

    // Limpia cualquier texto antes del JSON real
    const textoLimpio = textoCrudo.replace(/^[^\[\{]*/, "");

    const data = JSON.parse(textoLimpio);
    if (!Array.isArray(data)) throw new Error("Respuesta no es array");
    incidencias = data;

    /* 2. Rellenar selects dinámicamente */
    rellenarSelectTecnicos();
    rellenarSelectEstado();

    /* 3. Detectar cuáles tienen fotos (peticiones paralelas) */
    await detectarFotos();

    /* 4. Primer render */
    aplicarFiltrosYRender();
  } catch (e) {
    console.error(e);
    mostrarMensaje("Error al cargar incidencias.", 4000);
  }
});

/* ---------- RELLENADO SELECTS ---------- */
function rellenarSelectTecnicos() {
  const tecnicos = [
    ...new Set(incidencias.map((i) => i.id_tecnico).filter(Boolean)),
  ];
  tecnicos.forEach((id) => {
    const opt = document.createElement("option");
    opt.value = id;
    opt.textContent = `Técnico ID ${id}`;
    selTecnico.appendChild(opt);
  });
}
function rellenarSelectEstado() {
  const estados = [...new Set(incidencias.map((i) => i.estado))];
  estados.forEach((e) => {
    const opt = document.createElement("option");
    opt.value = e;
    opt.textContent = e;
    selEstado.appendChild(opt);
  });
}

/* ---------- DETECTAR FOTOS (una sola petición por incidencia) ---------- */
async function detectarFotos() {
  const promesas = incidencias.map(async (inc) => {
    try {
      const res = await fetch(
        `${API_URL}?accion=getFotosIncidencia&incidencia_id=${inc.id}`
      );
      const data = await res.json();
      mapaFotos[inc.id] =
        data.status === "ok" &&
        Array.isArray(data.fotos) &&
        data.fotos.length > 0;
    } catch {
      mapaFotos[inc.id] = false;
    }
  });
  await Promise.all(promesas);
}

/* ---------- FILTROS + ORDEN ---------- */
function aplicarFiltrosYRender() {
  let filtradas = incidencias.filter((inc) => {
    /* Buscador texto libre */
    const texto = buscador.value.toLowerCase();
    const coincideTexto =
      !texto ||
      inc.titulo.toLowerCase().includes(texto) ||
      inc.descripcion.toLowerCase().includes(texto) ||
      (inc.usuario && inc.usuario.toLowerCase().includes(texto)) ||
      inc.estado.toLowerCase().includes(texto);

    /* Técnico */
    const idTec = selTecnico.value;
    const coincideTecnico = !idTec || inc.id_tecnico == idTec;

    /* Estado */
    const est = selEstado.value;
    const coincideEstado = !est || inc.estado === est;

    /* Fotos */
    const fot = selFotos.value;
    const coincideFotos =
      !fot ||
      (fot === "con" && mapaFotos[inc.id]) ||
      (fot === "sin" && !mapaFotos[inc.id]);

    return coincideTexto && coincideTecnico && coincideEstado && coincideFotos;
  });

  /* Orden fecha */
  const orden = selOrden.value; // 'asc' | 'desc'
  filtradas.sort((a, b) => {
    const dA = new Date(a.fecha_creacion);
    const dB = new Date(b.fecha_creacion);
    return orden === "asc" ? dA - dB : dB - dA;
  });

  incidenciasFiltradas = filtradas;
  renderIncidencias(incidenciasFiltradas);
}

/* ---------- RENDER ---------- */
function renderIncidencias(datos) {
  if (!datos.length) {
    lista.innerHTML =
      '<p style="text-align:center;">No se encontraron incidencias.</p>';
    return;
  }

  lista.innerHTML = datos
    .map(
      (inc) => `
  <div class="incidencia" data-id="${inc.id}">
    <!-- TÍTULO → detalle de la incidencia -->
    <h2><a href="incidencia_detalle.html?id=${
      inc.id
    }" class="titulo-incidencia">${inc.titulo}</a></h2>
    
    <p><strong>Descripción:</strong> ${inc.descripcion}</p>

    <!-- USUARIO → perfil del usuario creador -->
    <p class="meta">
      <strong>Usuario:</strong>
      <a href="usuario_detalle.html?id=${
        inc.id_user
      }&perfil=usuario" class="enlace-usuario">${inc.usuario || "Anónimo"}</a>
    </p>

    <!-- SENSOR opcional -->
    ${
      inc.id_sensor
        ? `<p class="meta"><strong>Sensor:</strong> <a href="sensor_detalle.html?id=${inc.id_sensor}">${inc.nombre_sensor}</a> (ID ${inc.id_sensor})</p>`
        : ""
    }

    <!-- TÉCNICO → perfil del técnico asignado -->
    <p class="meta">
      <strong>Técnico:</strong>
      <a href="usuario_detalle.html?id=${
        inc.id_tecnico
      }&perfil=tecnico" class="enlace-tecnico">${inc.tecnico}</a>
    </p>

    <p class="meta"><strong>Estado:</strong> ${inc.estado}</p>
    <p class="meta"><strong>Fecha:</strong> ${new Date(
      inc.fecha_creacion
    ).toLocaleString()}</p>
    <div class="fotos" id="fotos-${inc.id}"></div>
  </div>
`
    )
    .join("");

  // Eliminamos el event listener del botón detalle
  cargarFotosVisibles();
}

function abrirDetalle(id) {
  window.location.href = `incidencia_detalle.html?id=${id}`;
}

/* ---------- CARGAR FOTOS SOLO DE LO VISUALIZADO ---------- */
async function cargarFotosVisibles() {
  for (const inc of incidenciasFiltradas) {
    if (mapaFotos[inc.id]) {
      try {
        const res = await fetch(
          `${API_URL}?accion=getFotosIncidencia&incidencia_id=${inc.id}`
        );
        const data = await res.json();
        const cont = document.getElementById(`fotos-${inc.id}`);
        if (cont && data.status === "ok" && data.fotos.length) {
          cont.innerHTML = data.fotos
            .map(
              (f) =>
                `<img src="data:image/jpeg;base64,${f.foto}" alt="Foto ${inc.id}"/>`
            )
            .join("");
        }
      } catch (e) {
        console.warn("Error cargando fotos incidencia", inc.id, e);
      }
    } else {
      const cont = document.getElementById(`fotos-${inc.id}`);
      if (cont) cont.innerHTML = "<em>Sin fotos</em>";
    }
  }
}

/* ---------- LISTENERS ---------- */
buscador.addEventListener("input", aplicarFiltrosYRender);
selTecnico.addEventListener("change", aplicarFiltrosYRender);
selEstado.addEventListener("change", aplicarFiltrosYRender);
selFotos.addEventListener("change", aplicarFiltrosYRender);
selOrden.addEventListener("change", aplicarFiltrosYRender);
