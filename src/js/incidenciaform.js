/* ------------------------------------------------------- */
/* VARIABLE GLOBAL PARA ACUMULAR IMÁGENES                  */
/* ------------------------------------------------------- */
let archivosAcumulados = [];

/* 1. Rellenar id_user desde localStorage */
const user = JSON.parse(localStorage.getItem("user"));
if (!user || !user.id) {
    alert("No se ha encontrado sesión. Redirigiendo al login.");
    location.href = "login.html";
} else {
    document.getElementById("id_user").value = user.id;
}

/* 2. Enviar formulario */
document.getElementById("incidenciaForm").addEventListener("submit", async e => {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);

    const box = document.getElementById("msg");
    box.textContent = "";
    box.className = "";

    /* --- 1. Crear la incidencia ------------------------------------ */
    const payloadInc = {
        accion: "crearIncidencia",
        id_user: formData.get("id_user"),
        titulo: formData.get("titulo"),
        descripcion: formData.get("descripcion"),
        sensor_id: formData.get("sensor_id") || null
    };

    try {
        const resInc = await fetch("../api/index.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify(payloadInc)
        }).then(r => r.json());

        if (resInc.status !== "ok") {
            box.className = "text-danger";
            box.textContent = resInc.message || "Error al crear incidencia";
            return;
        }

        /* --- 2. Subir fotos UNA A UNA ------------------------------ */
        if (archivosAcumulados.length) {
            const fotosBase64 = await Promise.all(
                archivosAcumulados.map(f => toBase64(f))
            );

            for (const base64 of fotosBase64) {
                const payloadImg = {
                    accion: "guardarFotoIncidencia",
                    incidencia_id: resInc.id_incidencia,
                    fotos: [base64]   // array de 1 única imagen
                };

                const resImg = await fetch("../api/index.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/json" },
                    body: JSON.stringify(payloadImg)
                }).then(r => r.json());

                if (resImg.status !== "ok") {
                    console.warn("Falló foto:", resImg.mensaje);
                }
            }
        }

        /* --- 3. Feedback y limpieza -------------------------------- */
        Swal.fire({
            title: '¡Enviado!',
            text: 'Tu incidencia se ha registrado correctamente.',
            icon: 'success',
            confirmButtonColor: '#152D9A',
            confirmButtonText: 'Genial'
        });

        limpiarFormularioCompleto();

    } catch (error) {
        console.error(error);
        box.className = "text-danger";
        box.textContent = "Error de conexión con el servidor.";
    }
});

/* 3. Lógica para ACUMULAR fotos (con filtro anti-duplicados) */
const imagenInput = document.getElementById('imagenInput');
if (imagenInput) {
    imagenInput.addEventListener('change', function () {
        const nuevosArchivos = Array.from(this.files);
        let hayDuplicados = false;

        nuevosArchivos.forEach(nuevoArchivo => {
            const existe = archivosAcumulados.some(
                g => g.name === nuevoArchivo.name && g.size === nuevoArchivo.size
            );
            if (!existe) {
                archivosAcumulados.push(nuevoArchivo);
            } else {
                hayDuplicados = true;
            }
        });

        actualizarTextoVisual();

        if (hayDuplicados) {
            Swal.fire({
                toast: true,
                position: 'top',
                icon: 'warning',
                title: 'Se han ignorado imágenes repetidas',
                showConfirmButton: false,
                timer: 3000
            });
        }

        this.value = ""; // reset input
    });
}

/* 4. Actualizar cajitas visuales */
function actualizarTextoVisual() {
    const container = document.getElementById('file-count');
    if (!container) return;
    container.innerHTML = "";
    archivosAcumulados.forEach(file => {
        const div = document.createElement('div');
        div.className = 'file-item-row';
        div.innerHTML = `<i class="fa-solid fa-paperclip" style="margin-right:8px; color:#666;"></i> ${file.name}`;
        container.appendChild(div);
    });
}

/* 5. Limpiar todo (form + array + vista) */
function limpiarFormularioCompleto() {
    document.getElementById('incidenciaForm').reset();
    archivosAcumulados = [];
    actualizarTextoVisual();
}

/* 6. Botón reset manual */
const btnReset = document.querySelector('button[type="reset"]');
if (btnReset) {
    btnReset.addEventListener('click', () => {
        setTimeout(() => {
            archivosAcumulados = [];
            actualizarTextoVisual();
        }, 10);
    });
}

/* 7. Helper base64 */
const toBase64 = file =>
    new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = () => resolve(reader.result);
        reader.onerror = err => reject(err);
    });