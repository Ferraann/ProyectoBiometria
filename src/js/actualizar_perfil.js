/**
 * @file actualizar_perfil.js
 * @brief Gestión de la interfaz de usuario para la edición del perfil de cliente.
 * @details Proporciona funcionalidades para habilitar/deshabilitar campos de entrada,
 * previsualización de imágenes de perfil mediante FileReader y gestión de alertas temporales.
 * @author Ferran
 * @date 3/12/2025
 */

/**
 * @name ManejoEdicionCampos
 * @{
 */

/** * @brief Lista de iconos que activan la edición de los campos.
 * @type {NodeListOf<Element>}
 */
const editIcons = document.querySelectorAll('.edit-icon');

/** * @brief Referencia al formulario de perfil.
 * @type {Element}
 */
const form = document.querySelector('.perfil-form');

/**
 * @brief Asigna eventos de clic a cada icono de edición para alternar el estado del campo.
 * @details Si el campo está bloqueado, lo habilita, cambia el fondo y pone el foco.
 * Si ya estaba en edición, lo bloquea y limpia los campos de confirmación asociados.
 */
editIcons.forEach(icon => {
    icon.addEventListener('click', (e) => {
        e.preventDefault();

        /** @brief Fila contenedora del input actual. */
        const row = icon.closest('.input-row');
        if (!row) return;

        /** @brief El elemento input asociado al icono pulsado. */
        const input = row.querySelector('input');
        if (!input) return;

        /** @brief Comprueba el estado actual: Toogle (activar/desactivar)  */
        if (input.dataset.editing === "1") {
            /**
             * @section DesactivarEdicion
             * @brief Restablece el campo a su estado de solo lectura.
             */
            input.dataset.editing = "0";
            input.disabled = true;
            input.style.backgroundColor = "";
            row.classList.remove('editing');

            /** @brief Lógica específica para Gmail */
            if (input.id === "gmail") {
                const rep = document.getElementById('repetir-correo');
                if (rep) {
                    rep.disabled = true;
                    rep.style.backgroundColor = "";
                    rep.value = "";
                }
            }

            /** @brief Lógica específica para Contraseña */
            if (input.id === "contrasena") {
                const corr = document.getElementById('contrasena');
                const rep = document.getElementById('repetir-contrasena');
                const ant = document.getElementById('contrasena-antigua');

                corr.type = "password";
                rep.type = "password";
                ant.type = "password";

                if (rep && ant) {
                    rep.disabled = true;
                    rep.style.backgroundColor = "";
                    rep.value = "";
                    ant.disabled = true;
                    ant.style.backgroundColor = "";
                    ant.value = "";
                }
            }
            return;
        }

        /**
         * @section ActivarEdicion
         * @brief Habilita el campo para la entrada del usuario.
         */
        input.dataset.editing = "1";
        input.disabled = false;
        input.focus();
        input.style.backgroundColor = "#f0f0f0";
        row.classList.add('editing');

        /** @brief Activación de campos auxiliares para Gmail */
        if (input.id === "gmail") {
            const rep = document.getElementById('repetir-correo');
            if (rep) {
                rep.disabled = false;
                rep.style.backgroundColor = "#f0f0f0";
            }
        }

        /** @brief Activación de campos auxiliares para Contraseña */
        if (input.id === "contrasena") {
            const corr = document.getElementById('contrasena');
            const rep = document.getElementById('repetir-contrasena');
            const ant = document.getElementById('contrasena-antigua');
            if (corr && rep && ant) {
                rep.disabled = false;
                rep.style.backgroundColor = "#f0f0f0";

                corr.type = "text";
                rep.type = "text";
                ant.type = "text";

                ant.disabled = false;
                ant.style.backgroundColor = "#f0f0f0";
            }
        }

        /** @brief Listener para detectar cambios manuales en el valor del input. */
        input.addEventListener('input', () => {
            input.dataset.edited = "1";
        });
    });
});
/** @} */

/**
 * @name GestionFotoPerfil
 * @{
 * @brief Funcionalidades para la carga y previsualización de la imagen de perfil.
 */

const editPhotoLink = document.getElementById('edit-photo-link');
const fileInput = document.getElementById('profile-image-upload');
const base64Input = document.getElementById('profile-image-base64');
const profileImageDisplay = document.getElementById('profile-image-display');

if (editPhotoLink && fileInput && profileImageDisplay) {

    /**
     * @brief Simula un clic en el input de archivo (oculto) al pulsar el enlace de "Editar".
     */
    editPhotoLink.addEventListener('click', (e) => {
        e.preventDefault();
        fileInput.click();
    });

    /**
     * @brief Maneja la selección de un archivo de imagen.
     * @details Valida que sea una imagen, la lee como DataURL (Base64) y actualiza la previsualización.
     */
    fileInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const file = this.files[0];
            const form = document.querySelector('.perfil-form');

            if (file.type.startsWith('image/')) {
                /** @brief Objeto para leer el archivo del cliente. */
                const reader = new FileReader();

                reader.onload = function(e) {
                    base64Input.value = e.target.result;
                    profileImageDisplay.src = e.target.result;
                };

                reader.readAsDataURL(file);
                form.dataset.editedByFile = "1";
            } else {
                alert("Por favor, selecciona un archivo de imagen válido.");
                fileInput.value = '';
            }
        }
    });
}
/** @} */

/**
 * @brief Oculta automáticamente los mensajes de alerta después de un tiempo definido.
 * @details Busca elementos de éxito o error por ID y los oculta tras 5 segundos usando setTimeout.
 * @returns {void}
 */
function ocultarAlertasAutomaticamente() {
    /** @type {HTMLElement|null} */
    const alertaError = document.getElementById("js-alerta-error");
    /** @type {HTMLElement|null} */
    const alertaExito = document.getElementById("js-alerta-exito");

    /** @brief Tras 5 segundos los mensajes de error desaparecen */
    if (alertaError) {
        setTimeout(() => {
            alertaError.style.display = "none";
        }, 5000);
    }

    /** @brief Tras 5 segundos los mensajes de éxito desaparecen */
    if (alertaExito) {
        setTimeout(() => {
            alertaExito.style.display = "none";
        }, 5000);
    }
}

/** @brief Inicialización de la ocultación de alertas */
ocultarAlertasAutomaticamente();