// ------------------------------------------------------------------
// Fichero: sensor_detalle.js
// Autor: Manuel
// Fecha: 11/12/2025
// ------------------------------------------------------------------
// Descripción:
//  Script modular que carga la ficha completa de un sensor
//  y muestra su estado.
// ------------------------------------------------------------------
document.addEventListener("DOMContentLoaded", () => {
    
    const API_URL = '../api/index.php';
    const params = new URLSearchParams(location.search);
    const idSensor = params.get('id');

    if (!idSensor) {
        alert('No se indicó ID de sensor');
        location.href = 'dashboard.php'; // Redirige a una página principal
        return;
    }

    const chkProblema = document.getElementById('chk-problema');
    const labelProblema = document.getElementById('label-problema');
    const notaProblema = document.getElementById('nota-problema');
    const btnReparado = document.getElementById('btn-marcar-reparado');

    /* 1. Cargar datos básicos del sensor ----------------------------- */
    (async () => {
        try {
            const resSensor = await fetch(`${API_URL}?accion=getSensorXId&id=${idSensor}`);
            
            if (!resSensor.ok) {
                // Si el servidor devuelve un error HTTP (ej. 404), lanzamos error
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

            // B. Cargar estado de problema
            const conProblema = sensor.problema == 1; // La base de datos guarda 0 o 1

            chkProblema.checked = conProblema;
            chkProblema.disabled = !conProblema; // Desactivamos el switch si no hay problema

            if (conProblema) {
                labelProblema.textContent = 'Estado: ¡Requiere Revisión!';
                labelProblema.style.color = 'red';
                notaProblema.textContent = 'Este sensor está marcado como defectuoso y necesita atención técnica.';
                btnReparado.style.display = 'block'; // Mostrar botón para marcar como reparado
            } else {
                labelProblema.textContent = 'Estado: Funcionando correctamente';
                labelProblema.style.color = '#333';
                notaProblema.textContent = '';
                btnReparado.style.display = 'none';
            }

        } catch (e) {
            alert('Error al cargar la ficha del sensor: ' + e.message);
            location.href = 'dashboard.php';
        }
    })();

    /* 2. Manejar la acción de 'Marcar como Reparado' ---------------- */
    btnReparado.addEventListener('click', async () => {
        if (!confirm('¿Estás seguro de que quieres marcar este sensor como reparado?')) {
            return;
        }

        try {
            // Asumimos que tienes una acción 'reactivarSensor' en tu API (index.php)
            const r = await fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    accion: 'reactivarSensor', 
                    sensor_id: idSensor 
                })
            });
            const res = JSON.parse(await r.text());

            if (res.status !== 'ok') {
                throw new Error(res.mensaje || 'Error al reactivar el sensor');
            }

            alert('Sensor marcado como reparado con éxito.');
            location.reload(); // Recargar la página para ver el nuevo estado
        } catch (e) {
            alert('Error al reactivar: ' + e.message);
        }
    });
});