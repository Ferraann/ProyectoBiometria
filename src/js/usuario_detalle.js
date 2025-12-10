// ------------------------------------------------------------------
// Fichero: usuario_detalle.js
// Autor: Manuel
// Fecha: 11/12/2025
// ------------------------------------------------------------------
// Descripción:
//  Script modular que carga la ficha completa de un usuario
//  (datos, foto, roles y sensores actuales) y permite modificar
//  permisos de administrador/técnico en tiempo real.
// ------------------------------------------------------------------
const params = new URLSearchParams(location.search);
const idUsuario = params.get('id');

if (!idUsuario) {
  alert('No se indicó usuario');
  location.href = 'dashboard.php';
}

const chkAdmin  = document.getElementById('chk-admin');
const chkTec    = document.getElementById('chk-tecnico');
const btnSave   = document.getElementById('btn-guardar');

let esAdmin = false;
let esTec   = false;

/* 1. Cargar datos básicos + foto ----------------------------- */
(async () => {
  try {
    const [resUser, resFoto] = await Promise.all([
      fetch(`../api/index.php?accion=getUsuarioXId&id=${idUsuario}`),
      fetch(`../api/index.php?accion=getFotoPerfil&id=${idUsuario}`)
    ]);
    const user = await resUser.json();
    if (!user || user.status === 'error') throw new Error('Usuario no encontrado');

    document.getElementById('titulo-perfil').textContent = `Perfil de ${user.nombre}`;
    document.getElementById('user-apellidos').textContent = user.apellidos || '-';
    document.getElementById('user-gmail').textContent     = user.gmail;

    if (resFoto.ok) {
      const blob = await resFoto.blob();
      document.getElementById('foto-perfil').src = URL.createObjectURL(blob);
    }

    /* 2. Cargar roles actuales ------------------------------ */
    const [resAdm, resTec] = await Promise.all([
      fetch(`../api/index.php?accion=esAdministrador&id=${idUsuario}`),
      fetch(`../api/index.php?accion=esTecnico&id=${idUsuario}`)
    ]);
    esAdmin = (await resAdm.json()).es === true;
    esTec   = (await resTec.json()).es === true;
    chkAdmin.checked = esAdmin;
    chkTec.checked   = esTec;

    /* 3. Cargar sensores actuales -------------------------- */
    const resSens = await fetch(`../api/index.php?accion=getSensoresDeUsuario&id=${idUsuario}`);
    const sensores = await resSens.json();
    const lista = document.getElementById('lista-sensores');
    if (!sensores || sensores.length === 0) {
      lista.innerHTML = '<li class="empty">Sin sensores asignados</li>';
    } else {
      lista.innerHTML = sensores.map(s =>
        `<li><strong>${s.nombre || s.mac}</strong> – ${s.modelo || 'Sin modelo'}</li>`
      ).join('');
    }
  } catch (e) {
    alert(e.message);
    location.href = 'dashboard.php';
  }
})();

/* 4. Guardar cambios de roles ------------------------------- */
btnSave.addEventListener('click', async () => {
  const nuevoAdmin = chkAdmin.checked;
  const nuevoTec   = chkTec.checked;

  if (nuevoAdmin !== esAdmin) {
    const acc = nuevoAdmin ? 'asignarAdministrador' : 'quitarAdministrador';
    await fetch('../api/index.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ accion: acc, usuario_id: idUsuario })
    });
  }
  if (nuevoTec !== esTec) {
    const acc = nuevoTec ? 'asignarTecnico' : 'quitarTecnico';
    await fetch('../api/index.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ accion: acc, usuario_id: idUsuario })
    });
  }
  alert('Cambios guardados');
  location.reload();
});