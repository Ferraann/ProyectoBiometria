// incidencia_detalle.js

// Leer id desde URL
const params = new URLSearchParams(location.search);
const idIncidencia = params.get('id');

if (!idIncidencia) {
  alert('No se indicó una incidencia.');
  location.href = 'incidencias.html';
}

// Rellenar campos con datos obtenidos
async function cargarIncidencia() {
  try {
    const res = await fetch(`../api/index.php?accion=getIncidenciaXId&id=${idIncidencia}`);
    const inc = await res.json();

    if (!inc || inc.status === 'error') throw new Error('Incidencia no encontrada');

    document.getElementById('titulo-incidencia').textContent = `Incidencia: ${inc.titulo}`;
    document.getElementById('descripcion').value = inc.descripcion || '';
    document.getElementById('usuario').value = inc.usuario || 'Anónimo';
    document.getElementById('id_usuario').value = inc.id_user || '';
    document.getElementById('tecnico').value = inc.tecnico || '';
    document.getElementById('id_tecnico').value = inc.id_tecnico || '';
    document.getElementById('estado').value = inc.estado || '';
    document.getElementById('id_estado').value = inc.estado_id || '';
    document.getElementById('fecha_creacion').value = (inc.fecha_creacion || '').slice(0, 16);
    document.getElementById('fecha_finalizacion').value = (inc.fecha_finalizacion || '').slice(0, 16);

  } catch (e) {
    alert(e.message);
    location.href = 'incidencias.html';
  }
}

// Cargar al arrancar
cargarIncidencia();

// (Opcional) guardado
document.getElementById('guardar-btn')?.addEventListener('click', () => {
  alert('Función de guardado no implementada aún');
});