/* ======================================================
   Manejo de editar campo y envío: toggle edición
   ====================================================== */

// Selecciona todos los iconos de edición
const editIcons = document.querySelectorAll('.edit-icon');
const form = document.querySelector('.perfil-form');

// Recorrer todos los iconos de edicion para saber cual es editable
editIcons.forEach(icon => {
    // Cuando se hace clic en uno...
    icon.addEventListener('click', (e) => {
        e.preventDefault();

        // 1. Identificar el campo de entrada (input) asociado
        const row = icon.closest('.input-row'); // Encuentra la fila contenedora
        if (!row) return;// Si no hay fila, salir
        const input = row.querySelector('input'); // Encuentra el input dentro de la fila
        if (!input) return; // Si no hay input, salir

        // 2. Comprobar el estado actual: Toggle (activar/desactivar)

        // Toggle: si ya estaba activo -> desactivar edición
        if (input.dataset.editing === "1") {
            // --- DESACTIVAR EDICIÓN ---

            // Marcar como no editable
            input.dataset.editing = "0";
            // Deshabilitar el campo (para que no se pueda escribir ni enviar si no se ha modificado)
            input.disabled = true;
            // Limpiar el color de fondo
            input.style.backgroundColor = "";
            // Eliminar la clase de estilo 'editing' de la fila
            row.classList.remove('editing');

            // Desactivar campos de confirmación si aplica (gmail o contraseña)
            if (input.id === "gmail") {
                const rep = document.getElementById('repetir-correo');
                if (rep) {
                    rep.disabled = true;
                    rep.style.backgroundColor = "";
                    rep.value = ""; // Limpiar el valor de confirmación
                }
            }

            if (input.id === "contrasena") {
                const corr = document.getElementById('contrasena');
                const rep = document.getElementById('repetir-contrasena');
                const ant = document.getElementById('contrasena-antigua');

                // Ocultar los caracteres escritos (mostrar como 'password')
                corr.type = "password";
                rep.type = "password";
                ant.type = "password";

                if (rep && ant) {
                    // Deshabilitar y limpiar 'repetir-contrasena'
                    rep.disabled = true;
                    rep.style.backgroundColor = "";
                    rep.value = "";

                    // Deshabilitar y limpiar 'contrasena-antigua'
                    ant.disabled = true;
                    ant.style.backgroundColor = "";
                    ant.value = "";
                }
            }

            return; // salir porque se desactivó
        }

        // 3. Activar edición

        // Marcar como editable
        input.dataset.editing = "1";
        // Habilitar el campo para edición (se enviará en el formulario)
        input.disabled = false;
        // Poner el foco en el campo
        input.focus();
        // Aplicar el color de fondo para indicar que está activo
        input.style.backgroundColor = "#f0f0f0";
        // Añadir la clase de estilo 'editing' a la fila
        row.classList.add('editing');

        // Activar campos de confirmación si aplica
        if (input.id === "gmail") {
            const rep = document.getElementById('repetir-correo');
            if (rep) {
                // Habilitar y aplicar color de fondo a 'repetir-correo'
                rep.disabled = false;
                rep.style.backgroundColor = "#f0f0f0";
            }
        }

        if (input.id === "contrasena") {
            const corr = document.getElementById('contrasena');
            const rep = document.getElementById('repetir-contrasena');
            const ant = document.getElementById('contrasena-antigua');
            if (corr && rep && ant) {
                // Habilitar y aplicar color de fondo a 'repetir-contrasena'
                rep.disabled = false;
                rep.style.backgroundColor = "#f0f0f0";

                // Mostrar los caracteres escritos (mostrar como 'text')
                corr.type = "text";
                rep.type = "text";
                ant.type = "text";

                // Habilitar y aplicar color de fondo a 'contrasena-antigua'
                ant.disabled = false;
                ant.style.backgroundColor = "#f0f0f0";
            }
        }

        // Marcar como editado al cambiar el valor
        // Esto es un listener que se activa si el usuario escribe algo en el campo
        input.addEventListener('input', () => {
            input.dataset.edited = "1";
        });
    });
});


// 1. Obtener referencias de los nuevos elementos
const editPhotoLink = document.getElementById('edit-photo-link');
const fileInput = document.getElementById('profile-image-upload');
const base64Input = document.getElementById('profile-image-base64');
const profileImageDisplay = document.getElementById('profile-image-display');

if (editPhotoLink && fileInput && profileImageDisplay) {

    // 2. Al hacer clic en el enlace "Editar", simular el clic en el input de archivo oculto
    editPhotoLink.addEventListener('click', (e) => {
        e.preventDefault();
        fileInput.click(); // Esto abre el administrador de archivos del sistema
    });

    // 3. Escuchar cuando se selecciona un archivo (evento 'change' en el input)
    fileInput.addEventListener('change', function() {
        // fileInput.files es una lista de archivos seleccionados
        if (this.files && this.files[0]) {
            const file = this.files[0];
            // Marcamos el formulario como editado
            const form = document.querySelector('.perfil-form');

            // Verificar si el archivo es una imagen
            if (file.type.startsWith('image/')) {

                // Crea un objeto FileReader para leer el contenido del archivo
                const reader = new FileReader();

                // Cuando el lector termina, guardamos el Base64 en el campo oculto
                reader.onload = function(e) {
                    // El resultado (e.target.result) es la cadena Base64
                    base64Input.value = e.target.result;
                    profileImageDisplay.src = e.target.result;
                };

                // Inicia la lectura del archivo como una URL de datos (Base64)
                reader.readAsDataURL(file);
                // Marca como editado
                form.dataset.editedByFile = "1";

            } else {
                alert("Por favor, selecciona un archivo de imagen válido.");
                fileInput.value = ''; // Limpiar el input para permitir una nueva selección
            }
        }
    });
}


// --- LÓGICA DE OCULTACIÓN AUTOMÁTICA DE ALERTAS ---
function ocultarAlertasAutomaticamente() {
    const alertaError = document.getElementById("js-alerta-error");
    const alertaExito = document.getElementById("js-alerta-exito");

    // Verificar si existe el mensaje de error y ocultarlo
    if (alertaError) {
        setTimeout(() => {
            alertaError.style.display = "none";
        }, 5000); // 5000 milisegundos = 5 segundos
    }

    // Verificar si existe el mensaje de éxito y ocultarlo
    if (alertaExito) {
        setTimeout(() => {
            alertaExito.style.display = "none";
        }, 5000); // 5 segundos
    }
}

ocultarAlertasAutomaticamente();

// ----------------------------------------------------------------------------------------
// ----------------------------------------------------------------------------------------
// ----------------------------------------------------------------------------------------
// ----------------------------------------------------------------------------------------
