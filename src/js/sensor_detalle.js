// ------------------------------------------------------------------
// Fichero: sensor_detalle.js
// Autor: Manuel
// Fecha: 11/12/2025
// ------------------------------------------------------------------
// Descripción:
//  Script modular que carga la ficha completa de un sensor,
//  muestra su estado y permite modificar el campo 'problema' vía API.
// ------------------------------------------------------------------
document.addEventListener("DOMContentLoaded", () => {
    
    const API_URL = '../api/index.php';
    const params = new URLSearchParams(location.search);
    const idSensor = params.get('id');

    if (!idSensor) {
        alert('No se indicó ID de sensor');
        location.href = 'dashboard.php'; 
        return;
    }

    const chkProblema = document.getElementById('chk-problema');
    const labelProblema = document.getElementById('label-problema');
    const notaProblema = document.getElementById('nota-problema');

    /* Función auxiliar para actualizar la vista (colores, textos) */
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
    (async () => {
        try {
            const resSensor = await fetch(`${API_URL}?accion=getSensorXId&id=${idSensor}`);
            
            if (!resSensor.ok) {
                throw new Error(`Error HTTP: ${resSensor.status}`);
            }

            const sensor = await resSensor.json();
            
            if (!sensor || sensor.status === 'error') {
                throw new Error(sensor.mensaje || 'Sensor no encontrado');
            }

            // A. Cargar datos en el HTML
            document.getElementById('titulo-sensor').textContent = `Sensor: ${sensor.nombre || sensor.mac}`;
            document.getElementById('sensor-id').textContent = sensor.id;
            document.getElementById('sensor-mac').textContent = sensor.mac;
            document.getElementById('sensor-modelo').textContent = sensor.modelo || 'N/A';
            document.getElementById('sensor-nombre').textContent = sensor.nombre || 'Sin nombre asignado';

            // B. Cargar estado de problema (Estado inicial)
            const conProblema = sensor.problema == 1; 

            chkProblema.checked = conProblema;
            actualizarVistaProblema(conProblema); // Inicializar la UI

        } catch (e) {
            alert('Error al cargar la ficha del sensor: ' + e.message);
            location.href = 'dashboard.php';
        }
    })(); // Se ejecuta automáticamente al cargar el DOM
    
    /* 2. Guardar cambios del switch (Problema/Reparado) ---------------- */
    chkProblema.addEventListener('change', async () => {
        console.log("¡SWITCH CLICKEADO! Estado actual:", chkProblema.checked);
        const nuevoEstado = chkProblema.checked;
        
        // Define la acción de la API y el mensaje de confirmación
        const API_ACTION = nuevoEstado ? 'marcarSensorConProblemas' : 'marcarSensorSinProblemas';
        const accionTexto = nuevoEstado ? 'marcarlo como defectuoso' : 'marcarlo como reparado';

        if (!confirm(`¿Estás seguro de que quieres ${accionTexto}?`)) {
            // Revertir el estado del switch si el usuario cancela
            chkProblema.checked = !nuevoEstado; 
            return;
        }

        // Deshabilitar el switch para evitar clics dobles mientras espera la API
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
            const res = JSON.parse(await r.text());

            if (res.status !== 'ok') {
                throw new Error(res.mensaje || 'Error al actualizar el estado del sensor');
            }

            // Si tiene éxito, actualizar la UI
            actualizarVistaProblema(nuevoEstado);
            alert(`Sensor actualizado correctamente: ${accionTexto}.`);

        } catch (e) {
            alert('Error al guardar: ' + e.message);
            // Revertir el estado del switch en caso de error de la API/Red
            chkProblema.checked = !nuevoEstado; 
            actualizarVistaProblema(!nuevoEstado);
        } finally {
            // Rehabilitar el switch
            chkProblema.disabled = false;
        }
    });
});