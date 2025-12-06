/* ======================================================
   Manejo de editar campo y envío: toggle edición
   ====================================================== */

// Selecciona todos los iconos de edición
const editIcons = document.querySelectorAll('.edit-icon');

editIcons.forEach(icon => {
    icon.addEventListener('click', (e) => {
        e.preventDefault();

        const row = icon.closest('.input-row');
        if (!row) return;
        const input = row.querySelector('input');
        if (!input) return;

        // Toggle: si ya estaba activo -> desactivar edición
        if (input.dataset.editing === "1") {

            input.dataset.editing = "0";
            input.disabled = true;
            input.style.backgroundColor = "";
            row.classList.remove('editing');

            // Desactivar confirmación si aplica
            if (input.id === "gmail") {
                const rep = document.getElementById('repetir-correo');
                if (rep) {
                    rep.disabled = true;
                    rep.style.backgroundColor = "";
                    rep.value = "";
                }
            }

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

            return; // salir porque se desactivó
        }

        // Activar edición
        input.dataset.editing = "1";
        input.disabled = false;
        input.focus();
        input.style.backgroundColor = "#f0f0f0";
        row.classList.add('editing');

        // Activar confirmación si aplica
        if (input.id === "gmail") {
            const rep = document.getElementById('repetir-correo');
            if (rep) {
                rep.disabled = false;
                rep.style.backgroundColor = "#f0f0f0";
            }
        }

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

        // Marcar como editado al cambiar el valor
        input.addEventListener('input', () => {
            input.dataset.edited = "1";
        });
    });
});


// Interceptar submit para validar SOLO campos activamente en edición
const form = document.querySelector('.perfil-form');

form.addEventListener('submit', function(e) {
    e.preventDefault();

    // Recolectar los campos activamente en edición
    const editedInputs = Array.from(form.querySelectorAll('input[data-editing="1"]'));

    if (editedInputs.length === 0) {
        alert('No has modificado nada.');
        return;
    }

    // Validaciones por cada tipo editado
    for (const input of editedInputs) {
        const id = input.id;
        const val = (input.value || "").trim();


        // Contraseña
        if (id === 'contrasena') {
            const rep = form.querySelector('input[id="repetir-contrasena"]');
            const ant = form.querySelector('input[id="contrasena-antigua"]');

            // 1. Validación de la Contraseña Antigua
            if (!ant || ant.disabled || ant.value.trim() === '') {
                // no funcionaaa//  alert('Debe introducir la Contraseña Antigua para confirmar el cambio.');
                ant && ant.focus();
                return;
            }

            // 2. Validación de longitud (existente)
            if (val.length < 8) {
                alert('La contraseña debe tener al menos 8 caracteres.');
                input.focus();
                return;
            }

            // 3. Validación de complejidad (existente)
            const tieneNum = /\d/.test(val);
            const tieneMay = /[A-Z]/.test(val);
            const tieneEsp = /[!@#$%^&*(),.?":{}|<>]/.test(val);

            if (!tieneNum || !tieneMay || !tieneEsp) {
                alert('Debe contener número, mayúscula y carácter especial.');
                input.focus();
                return;
            }

            // 4. Validación de coincidencia (existente)
            if (!rep || rep.disabled || val !== rep.value) {
                // alert('Las contraseñas no coinciden o no están rellenadas.');
                rep && rep.focus();
                return;
            }
        }
    }

    const mensaje_error = document.getElementById("mensaje_error");
    const mensaje_exito = document.getElementById("mensaje_exito");

    setTimeout(() => {
        mensaje_error.style.display = "none";
        mensaje_exito.style.display = "none";
    }, 5000)

    // Enviar formulario normalmente
    form.submit();
});

/*

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

*/