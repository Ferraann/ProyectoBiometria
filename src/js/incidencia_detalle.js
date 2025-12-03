const cont = document.getElementById('contenido-detalle');

// Intentamos obtener la incidencia desde sessionStorage
let data = sessionStorage.getItem('incidenciaSeleccionada');
if (data) {
  data = JSON.parse(data);
} else {
  // Si no estaba, podemos intentar cargar desde API por id
  const params = new URLSearchParams(window.location.search);
  const id = params.get('id');
  if (!id) {
    cont.innerHTML = "<p>Error: ID no especificado.</p>";
    throw new Error("ID no encontrado en la URL");
  }

  // fetch() para cargar desde la API si no hay sessionStorage
  const res = await fetch(`../api/index.php?accion=getIncidencia&id=${id}`);
  data = await res.json();
}

// Renderizamos todos los campos
cont.innerHTML = `
  <h2>Editar Incidencia #${data.id}</h2>
  <label>Título:</label>
  <input type="text" id="titulo" value="${data.titulo}" />

  <label>Descripción:</label>
  <textarea id="descripcion">${data.descripcion}</textarea>

  <label>Usuario:</label>
  <input type="text" value="${data.usuario || 'Anónimo'}" disabled />

  <label>Técnico:</label>
  <input type="text" id="tecnico" value="${data.tecnico}" />

  <label>Estado:</label>
  <input type="text" id="estado" value="${data.estado}" />

  <label>Fecha de creación:</label>
  <input type="datetime-local" value="${data.fecha_creacion.slice(0,16)}" disabled />

  <button id="guardar-btn">Guardar cambios</button>
`;
