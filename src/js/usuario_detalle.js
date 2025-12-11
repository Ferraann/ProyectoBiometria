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

const API_URL = '../api/index.php';
const params = new URLSearchParams(location.search);
const idUsuario = params.get('id');

if (!idUsuario) {
    alert('No se indicó usuario');
    location.href = 'dashboard.php';
}

const chkAdmin = document.getElementById('chk-admin');
const chkTec = document.getElementById('chk-tecnico');
const btnSave = document.getElementById('btn-guardar');

let esAdmin;
let esTec;

/* 1. Cargar datos básicos + foto ----------------------------- */
(async () => {
    try {
        const [resUser, resFoto] = await Promise.all([
            fetch(`${API_URL}?accion=getUsuarioXId&id=${idUsuario}`),
            fetch(`${API_URL}?accion=getFotoPerfil&id=${idUsuario}`)
        ]);
        const user = await resUser.json();
        if (!user || user.status === 'error') throw new Error('Usuario no encontrado');

        document.getElementById('titulo-perfil').textContent = `Perfil de ${user.nombre}`;
        document.getElementById('user-nombre').textContent = user.nombre;
        document.getElementById('user-apellidos').textContent = user.apellidos || '-';
        document.getElementById('user-gmail').textContent = user.gmail;

        if (!resFoto.ok) {
            document.querySelector('.avatar').style.display = 'none';
        } else {
            const blob = await resFoto.blob();
            document.getElementById('foto-perfil').src = URL.createObjectURL(blob);
        }

        /* 2. Cargar roles actuales ---------------------------------- */
        const [resAdm, resTec] = await Promise.all([
            fetch(`${API_URL}?accion=esAdministrador&id=${idUsuario}`),
            fetch(`${API_URL}?accion=esTecnico&id=${idUsuario}`)
        ]);
        const { es: admin } = await resAdm.json();
        const { es: tec } = await resTec.json();
        
        chkAdmin.checked = admin;
        chkTec.checked = tec;

        esAdmin = admin;
        esTec = tec;

        /* 3. Cargar sensores actuales -------------------------- */
        const resSens = await fetch(`${API_URL}?accion=getSensoresDeUsuario&id=${idUsuario}`);
        const sensores = await resSens.json();
        const lista = document.getElementById('lista-sensores');
        lista.innerHTML = (!sensores || sensores.length === 0)
            ? '<li class="empty">Sin sensores asignados</li>'
            : sensores.map(s => `<li><strong>${s.nombre || s.mac}</strong> – ${s.modelo || 'Sin modelo'}</li>`).join('');
    } catch (e) {
        alert(e.message);
        location.href = 'dashboard.php';
    }
})();

/* 4. Guardar cambios de roles ------------------------------- */
btnSave.addEventListener('click', async () => {
    const nuevoAdmin = chkAdmin.checked;
    const nuevoTec = chkTec.checked;

    try {
        if (nuevoAdmin !== esAdmin) {
            const acc = nuevoAdmin ? 'asignarAdministrador' : 'quitarAdministrador';
            const r = await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ accion: acc, usuario_id: idUsuario })
            });
            const res = JSON.parse(await r.text());
            if (res.status !== 'ok') throw new Error(res.mensaje || 'Error admin');
        }

        if (nuevoTec !== esTec) {
            const acc = nuevoTec ? 'asignarTecnico' : 'quitarTecnico';
            const r = await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ accion: acc, usuario_id: idUsuario })
            });
            const res = JSON.parse(await r.text());
            if (res.status !== 'ok') throw new Error(res.mensaje || 'Error técnico');
        }

        alert('Cambios guardados');
        location.reload();
    } catch (e) {
        alert('Error al guardar: ' + e.message);
    }
});