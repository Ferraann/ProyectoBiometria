// incidencia_detalle.js

const params = new URLSearchParams(location.search);
const idIncidencia = params.get('id');
const idUsuarioActivo = parseInt(sessionStorage.getItem('idUsuario') || '0');

if (!idIncidencia) {
  alert('No se indicó una incidencia.');
  location.href = 'incidencias.html';
}

// Cargar todo
(async () => {
  try {
    // 1. Cargar incidencia
    const resInc = await fetch(`../api/index.php?accion=getIncidenciaXId&id=${idIncidencia}`);
    const inc = await resInc.json();
    if (!inc || inc.status === 'error') throw new Error('Incidencia no encontrada');

    // 2. Cargar nombres de usuario y técnico
    const [resUser, resTec] = await Promise.all([
      fetch(`../api/index.php?accion=getUsuarioXId&id=${inc.id_user}`),
      fetch(`../api/index.php?accion=getUsuarioXId&id=${inc.id_tecnico}`)
    ]);
    const user = await resUser.json();
    const tec = await resTec.json();

    // 3. Cargar estados disponibles
    const resEst = await fetch('../api/index.php?accion=getEstadosIncidencia');
    const estados = await resEst.json();

    // 4. Rellenar campos
    document.getElementById('incidencia-titulo-id').textContent = `${inc.titulo} (${inc.id})`;
    document.getElementById('descripcion-texto').textContent = inc.descripcion || '-';

    const nombreUser = user.status !== 'error' ? `${user.nombre} ${user.apellidos ?? ''}`.trim() : 'Anónimo';
    document.getElementById('link-usuario').textContent = nombreUser;
    document.getElementById('link-usuario').href = `usuario_detalle.html?id=${inc.id_user}&perfil=usuario`;

    const nombreTec = tec.status !== 'error' ? `${tec.nombre} ${tec.apellidos ?? ''}`.trim() : 'Sin asignar';
    document.getElementById('link-tecnico').textContent = nombreTec;
    document.getElementById('link-tecnico').href = `usuario_detalle.html?id=${inc.id_tecnico}&perfil=tecnico`;

    document.getElementById('fecha-creacion').textContent = new Date(inc.fecha_creacion).toLocaleString();
    // Mostrar/ocultar fecha de finalización
    const filaFin = document.getElementById('fila-finalizacion');
    if (filaFin) {
      if (inc.fecha_finalizacion) {
        filaFin.style.display = 'flex';
        document.getElementById('fecha-finalizacion').textContent = new Date(inc.fecha_finalizacion).toLocaleString();
      } else {
        filaFin.style.display = 'none';
      }
    }

    // 5. Rellenar select de estados
    const select = document.getElementById('select-estados');
    estados.forEach(e => {
      const opt = document.createElement('option');
      opt.value = e.id;
      opt.textContent = e.nombre;
      if (e.id === inc.estado_id) opt.selected = true;
      select.appendChild(opt);
    });

    // 6. Guardar estado
    document.getElementById('btn-guardar-estado').addEventListener('click', async () => {
      const nuevoEstadoId = select.value;
      const res = await fetch('../api/index.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          accion: 'actualizarEstadoIncidencia',
          incidencia_id: idIncidencia,
          estado_id: nuevoEstadoId
        })
      });
      const data = await res.json();
      if (data.status === 'ok') {
        alert('Estado actualizado.');
        location.reload();
      } else {
        alert('Error: ' + data.mensaje);
      }
    });

    // 7. Asignarme como técnico
    document.getElementById('btn-asignarme').addEventListener('click', async () => {
      if (!idUsuarioActivo) return alert('No hay usuario activo.');
      const ok = confirm('¿Quieres asignarte esta incidencia?');
      if (!ok) return;

      const res = await fetch('../api/index.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          accion: 'asignarmeTecnicoIncidencia',
          incidencia_id: idIncidencia,
          tecnico_id: idUsuarioActivo
        })
      });
      const data = await res.json();
      if (data.status === 'ok') {
        alert('Incidencia asignada.');
        location.reload();
      } else {
        alert('Error: ' + data.mensaje);
      }
    });

  } catch (e) {
    alert(e.message);
    location.href = 'incidencias.html';
  }
})();