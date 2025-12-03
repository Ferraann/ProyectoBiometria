const cont = document.getElementById('contenido-detalle');

// Recuperamos la incidencia desde sessionStorage
let data = sessionStorage.getItem('incidenciaSeleccionada');
if (!data) {
  cont.innerHTML = "<p>Error: no hay incidencia seleccionada.</p>";
  throw new Error("No se encontró la incidencia en sessionStorage");
}

data = JSON.parse(data);

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
