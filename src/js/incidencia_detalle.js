const cont = document.getElementById('contenido-detalle');

let data = sessionStorage.getItem('incidenciaSeleccionada');
if (!data) {
  cont.innerHTML = "<p>Error: no hay incidencia seleccionada.</p>";
  throw new Error("No se encontró la incidencia en sessionStorage");
}

data = JSON.parse(data);

// Renderizamos todos los campos en columnas usando un grid
cont.innerHTML = `
  <h2>Editar Incidencia #${data.id}</h2>
  <div class="detalle-grid">
    <div class="campo">
      <label>ID:</label>
      <input type="text" value="${data.id}" disabled />
    </div>
    <div class="campo">
      <label>Título:</label>
      <input type="text" id="titulo" value="${data.titulo}" />
    </div>
    <div class="campo">
      <label>Descripción:</label>
      <textarea id="descripcion">${data.descripcion}</textarea>
    </div>
    <div class="campo">
      <label>Usuario:</label>
      <input type="text" value="${data.usuario || 'Anónimo'}" disabled />
    </div>
    <div class="campo">
      <label>ID Usuario:</label>
      <input type="text" value="${data.id_user || ''}" disabled />
    </div>
    <div class="campo">
      <label>Técnico:</label>
      <input type="text" id="tecnico" value="${data.tecnico}" />
    </div>
    <div class="campo">
      <label>ID Técnico:</label>
      <input type="text" value="${data.id_tecnico || ''}" disabled />
    </div>
    <div class="campo">
      <label>Estado:</label>
      <input type="text" id="estado" value="${data.estado}" />
    </div>
    <div class="campo">
      <label>ID Estado:</label>
      <input type="text" value="${data.estado_id || ''}" disabled />
    </div>
    <div class="campo">
      <label>Fecha de creación:</label>
      <input type="datetime-local" value="${data.fecha_creacion.slice(0,16)}" disabled />
    </div>
    <div class="campo">
      <label>Fecha finalización:</label>
      <input type="datetime-local" value="${data.fecha_finalizacion ? data.fecha_finalizacion.slice(0,16) : ''}" disabled />
    </div>
  </div>
  <button id="guardar-btn">Guardar cambios</button>
`;

// Estilos CSS para el grid
const style = document.createElement('style');
style.textContent = `
.detalle-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 20px;
  margin-top: 20px;
}

.campo {
  display: flex;
  flex-direction: column;
}

.campo label {
  font-weight: 700;
  margin-bottom: 5px;
}

.campo input,
.campo textarea {
  padding: 8px;
  font-family: inherit;
  font-size: 1rem;
  border: 2px solid var(--Principal_1);
  border-radius: 6px;
}

.campo textarea {
  resize: vertical;
  min-height: 80px;
}
`;
document.head.appendChild(style);
