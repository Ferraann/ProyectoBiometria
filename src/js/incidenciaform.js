/**
 * @file incidenciaform.js
 * @brief Script para la creación de incidencias y carga múltiple de imágenes.
 * @details Gestiona el ciclo de vida de una incidencia: validación de sesión, acumulación de archivos
 * en memoria, conversión a Base64 y envío secuencial al servidor mediante fetch.
 * @author Manuel
 * @date 12/11/2025
 */

/**
 * @var {File[]} archivosAcumulados
 * @brief Array global que almacena los objetos de archivo (imágenes) seleccionados por el usuario.
 */
let archivosAcumulados = [];

/**
 * @section Sesion
 * @brief Recuperación de datos del usuario desde el almacenamiento local.
 */
const user = JSON.parse(localStorage.getItem("user"));
if (!user || !user.id) {
    alert("No se ha encontrado sesión. Redirigiendo al login.");
    location.href = "login.html";
} else {
    document.getElementById("id_user").value = user.id;
}

/**
 * @brief Manejador del evento de envío del formulario de incidencias.
 * @details Realiza un proceso asíncrono en dos etapas:
 * 1. Envía los datos de texto (título, descripción) para crear la incidencia en la DB.
 * 2. Si tiene éxito, procesa y envía la lista de imágenes acumuladas una por una.
 * @param {Event} e Evento de submit del formulario.
 * @async
 */
document.getElementById("incidenciaForm").addEventListener("submit", async e => {
    e.preventDefault();
    const form = e.target;
    const formData = new FormData(form);

    const box = document.getElementById("msg");
    box.textContent = "";
    box.className = "";

    /**
     * @name RegistroIncidencia
     * @brief Primera etapa: Creación del registro base.
     */
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

        /**
         * @name SubidaImagenes
         * @brief Segunda etapa: Procesamiento y subida de archivos adjuntos.
         * @details Convierte cada archivo a Base64 y realiza peticiones POST individuales.
         */
        if (archivosAcumulados.length) {
            /** @brief Convierte todos los archivos del array a Base64 de forma concurrente. */
            const fotosBase64 = await Promise.all(
                archivosAcumulados.map(f => toBase64(f))
            );

            for (const base64 of fotosBase64) {
                const payloadImg = {
                    accion: "guardarFotoIncidencia",
                    incidencia_id: resInc.id_incidencia,
                    fotos: [base64]
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

        /** @brief Notificación de éxito mediante la librería SweetAlert2. */
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

/**
 * @brief Escucha cambios en el input de archivos para acumularlos en el array global.
 * @details Implementa un filtro para evitar que el usuario suba archivos duplicados
 * comparando nombre y tamaño.
 * @listens change
 */
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

        this.value = "";
    });
}

/**
 * @brief Actualiza la lista visual de archivos adjuntos en el DOM.
 * @returns {void}
 */
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

/**
 * @brief Limpia el formulario, el array de archivos y la interfaz visual.
 * @returns {void}
 */
function limpiarFormularioCompleto() {
    const formElement = document.getElementById('incidenciaForm');
    if(formElement) formElement.reset();
    archivosAcumulados = [];
    actualizarTextoVisual();
}

/**
 * @brief Listener para el botón de reset del formulario.
 */
const btnReset = document.querySelector('button[type="reset"]');
if (btnReset) {
    btnReset.addEventListener('click', () => {
        setTimeout(() => {
            archivosAcumulados = [];
            actualizarTextoVisual();
        }, 10);
    });
}

/**
 * @brief Convierte un objeto File en una cadena Base64.
 * @param {File} file El archivo a convertir.
 * @returns {Promise<string>} Promesa que resuelve con la cadena Base64 del archivo.
 */
const toBase64 = file =>
    new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = () => resolve(reader.result);
        reader.onerror = err => reject(err);
    });