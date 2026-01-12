/**
 * @file sensor_detalle.js
 * @brief Script para la visualización detallada y gestión operativa de un sensor.
 * @details Este script permite consultar los datos técnicos de un sensor específico,
 * visualizar su estado de funcionamiento y permitir a los técnicos alternar
 * el estado operativo (Correcto/Defectuoso) mediante una petición POST a la API.
 * @author Manuel
 * @date 1/12/2025
 */

document.addEventListener("DOMContentLoaded", () => {

    /** @name Configuración y Parámetros
     * @{ */
    /** @brief URL del punto de entrada de la API. */
    const API_URL = '../api/index.php';
    /** @brief Analizador de parámetros de la URL actual. */
    const params = new URLSearchParams(location.search);
    /** @brief Identificador del sensor obtenido de la query string 'id'. */
    const idSensor = params.get('id');
    /** @} */

    /**
     * @brief Redirección de seguridad si no se proporciona un ID válido.
     */
    if (!idSensor) {
        alert('No se indicó ID de sensor');
        location.href = 'incidencias.html';
        return;
    }

    /** @name Elementos de la Interfaz
     * @{ */
    /** @brief Switch (Checkbox) para alternar el estado de problema. @type {HTMLInputElement} */
    const chkProblema = document.getElementById('chk-problema');
    /** @brief Etiqueta de texto que describe el estado actual. @type {HTMLElement} */
    const labelProblema = document.getElementById('label-problema');
    /** @brief Nota informativa adicional sobre el fallo. @type {HTMLElement} */
    const notaProblema = document.getElementById('nota-problema');
    /** @} */

    /**
     * @brief Actualiza dinámicamente los estilos y textos de la interfaz según el estado del sensor.
     * @param {boolean} conProblema True si el sensor tiene un fallo, False si funciona correctamente.
     * @returns {void}
     */
    const actualizarVistaProblema = (conProblema) => {
        if (conProblema) {
            labelProblema.textContent = 'Estado: ¡Requiere Revisión!';
            labelProblema.style.color = 'red';
            notaProblema.textContent = 'Este sensor está marcado como defectuoso.';
        } else {
            labelProblema.textContent = 'Estado: Funcionando correctamente';
            labelProblema.style.color = '#333';
            notaProblema.textContent = '';
        }
    }

    /* 1. Cargar datos básicos del sensor (IIFE Asíncrono) --------- */

    /**
     * @brief Función autoejecutable que recupera la información del sensor desde el servidor.
     * @details Realiza una petición GET a la API, parsea el JSON y rellena los campos del DOM.
     * @async
     * @returns {Promise<void>}
     */
    (async () => {
        try {
            const resSensor = await fetch(`${API_URL}?accion=getSensorXId&id=${idSensor}`);

            if (!resSensor.ok) {
                throw new Error(`Error HTTP: ${resSensor.status}`);
            }

            /** @var {Object} sensor Datos del sensor devueltos por la API. */
            const sensor = await resSensor.json();

            if (!sensor || sensor.status === 'error') {
                throw new Error(sensor.mensaje || 'Sensor no encontrado');
            }

            // A. Inyección de datos técnicos en el HTML
            document.getElementById('titulo-sensor').textContent = `Sensor: ${sensor.nombre || sensor.mac}`;
            document.getElementById('sensor-id').textContent = sensor.id;
            document.getElementById('sensor-mac').textContent = sensor.mac;
            document.getElementById('sensor-modelo').textContent = sensor.modelo || 'N/A';
            document.getElementById('sensor-nombre').textContent = sensor.nombre || 'Sin nombre asignado';

            // B. Sincronización del estado inicial del Switch
            const conProblema = sensor.problema == 1;

            chkProblema.checked = conProblema;
            actualizarVistaProblema(conProblema);

        } catch (e) {
            alert('Error al cargar la ficha del sensor: ' + e.message);
            location.href = 'incidencias.html';
        }
    })();

    /* 2. Guardar cambios del switch (Problema/Reparado) ---------------- */

    /**
     * @brief Evento que gestiona el cambio de estado operativo del sensor.
     * @details Muestra una confirmación, deshabilita el control temporalmente para evitar
     * colisiones de red y envía una petición POST para actualizar la base de datos.
     * @listens change
     * @async
     */
    chkProblema.addEventListener('change', async () => {
        const nuevoEstado = chkProblema.checked;

        /** @brief Determina la acción de la API según la posición del switch. */
        const API_ACTION = nuevoEstado ? 'marcarSensorConProblemas' : 'marcarSensorSinProblemas';
        const accionTexto = nuevoEstado ? 'marcarlo como defectuoso' : 'marcarlo como reparado';

        if (!confirm(`¿Estás seguro de que quieres ${accionTexto}?`)) {
            // Reversión visual si se cancela la confirmación
            chkProblema.checked = !nuevoEstado;
            return;
        }

        // Bloqueo de UI
        chkProblema.disabled = true;



        try {
            const r = await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    accion: API_ACTION,
                    sensor_id: idSensor
                })
            });

            /** @brief Parseo manual para asegurar compatibilidad con respuestas de texto plano. */
            const res = JSON.parse(await r.text());

            if (res.status !== 'ok') {
                throw new Error(res.mensaje || 'Error al actualizar el estado del sensor');
            }

            actualizarVistaProblema(nuevoEstado);
            alert(`Sensor actualizado correctamente: ${accionTexto}.`);

        } catch (e) {
            alert('Error al guardar: ' + e.message);
            // Reversión del estado ante fallo de comunicación
            chkProblema.checked = !nuevoEstado;
            actualizarVistaProblema(!nuevoEstado);
        } finally {
            // Liberación de UI
            chkProblema.disabled = false;
        }
    });
});