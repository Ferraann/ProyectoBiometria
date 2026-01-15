/**
 * @file dashboard_cliente.js
 * @brief Gestión completa de pestañas, fechas y gráficas.
 */

// =========================================================
// 1. VARIABLES GLOBALES DE GRÁFICAS
// =========================================================
let chartEvolucionInstance = null;
let chartMinMaxInstance = null;
let chartTopSensoresInstance = null;
const GAS_IDS_STATS = { "NO2": 1, "O3": 2, "SO2": 3, "CO": 4, "PM10": 5 };

document.addEventListener('DOMContentLoaded', () => {

    // --- PESTAÑAS (TABS) ---
    const tabLinks = document.querySelectorAll('.sensores-nav a');
    tabLinks.forEach(link => {
        link.addEventListener('click', function(event) {
            event.preventDefault();
            const tabId = this.dataset.tab;

            tabLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');

            document.querySelectorAll('.tab-content').forEach(content => {
                if (content.getAttribute('data-tab-content') === tabId) {
                    content.classList.add('active-tab-content');
                } else {
                    content.classList.remove('active-tab-content');
                }
            });

            // Recargas específicas por pestaña
            if (tabId === 'mapas') {
                setTimeout(() => {
                    if (typeof map !== 'undefined') map.invalidateSize();
                    if (typeof loadData === 'function') loadData();
                }, 150);
            } else if (tabId === 'estadisticas') {
                setTimeout(() => {
                    initCharts(); // Asegura que el canvas existe
                    window.actualizarTodasLasGraficas(); // Carga inicial
                }, 150);
            }
        });
    });

    // --- DROPDOWNS ---
    document.querySelectorAll('.dropdown-mapa').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            const menu = this.nextElementSibling;
            if (menu) menu.classList.toggle('show');
        });
    });
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.dropdown-container')) {
            document.querySelectorAll('.dropdown-menu.show').forEach(m => m.classList.remove('show'));
        }
    });

    // --- MODAL INFO ---
    const infoModal = document.getElementById('gas-info-panel');
    const openInfoBtn = document.getElementById('open-info-btn');
    const closeInfoBtn = document.getElementById('close-info-btn');
    if(openInfoBtn) openInfoBtn.addEventListener('click', () => infoModal.style.display = 'block');
    if(closeInfoBtn) closeInfoBtn.addEventListener('click', () => infoModal.style.display = 'none');
    window.addEventListener('click', (e) => { if(e.target === infoModal) infoModal.style.display = 'none'; });

    // --- SELECTOR DE GAS (ESTADÍSTICAS) ---
    const statsGasSelect = document.getElementById('statsGasSelect');
    if (statsGasSelect) {
        statsGasSelect.addEventListener('change', () => {
            window.actualizarTodasLasGraficas();
        });
    }

    // =========================================================
    // 2. CONFIGURACIÓN DEL CALENDARIO (CORREGIDO)
    // =========================================================
    const allDatePickers = document.querySelectorAll('.date-picker');
    allDatePickers.forEach(picker => {
        flatpickr(picker, {
            dateFormat: "d/m/Y",
            defaultDate: "today",
            locale: { firstDayOfWeek: 1 },
            onChange: function(selectedDates, dateStr, instance) {
                // Actualizar texto visual
                instance.element.querySelector('span').textContent = 'Fecha: ' + dateStr;

                // Formato SQL
                const fechaParaAPI = instance.formatDate(selectedDates[0], "Y-m-d");

                // Detectar pestaña activa y actualizar lo que corresponda
                if (instance.element.closest('#mapas-content')) {
                    if (typeof updateMapByDate === 'function') {
                        updateMapByDate(fechaParaAPI);
                    }
                } else if (instance.element.closest('#estadisticas-content')) {
                    // AQUÍ ESTÁ EL ARREGLO: Pasamos la fecha explícitamente
                    window.actualizarTodasLasGraficas(fechaParaAPI);
                }
            }
        });
    });

}); // Fin DOMContentLoaded


// =========================================================
// 3. LÓGICA DE GRÁFICAS (Funciones Globales)
// =========================================================

function initCharts() {
    // Si ya existen, no las recreamos para evitar errores de superposición
    if (chartEvolucionInstance) return;

    // 1. Evolución
    const ctxEvol = document.getElementById('chartEvolucion');
    if (ctxEvol) {
        chartEvolucionInstance = new Chart(ctxEvol, {
            type: 'line',
            data: { labels: [], datasets: [{ label: 'Media', data: [], borderColor: '#ffae00', backgroundColor: 'rgba(255, 174, 0, 0.1)', fill: true, tension: 0.4 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { grid: { color: '#444' } }, x: { grid: { display: false } } } }
        });
    }

    // 2. Min/Max (Con corrección de visibilidad)
    const ctxMinMax = document.getElementById('chartMinMax');
    if (ctxMinMax) {
        chartMinMaxInstance = new Chart(ctxMinMax, {
            type: 'bar',
            data: {
                labels: ['Mínimo', 'Promedio', 'Máximo'],
                datasets: [{
                    label: 'Valores',
                    data: [],
                    backgroundColor: ['#00d2ff', '#3a7bd5', '#ff4b1f'],
                    // ESTO AYUDA: Si el valor es muy bajo pero no 0, se ve un poco
                    minBarLength: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, grid: { color: '#444' } } }
            }
        });
    }

    // 3. Top 5
    const ctxTop = document.getElementById('chartTopSensores');
    if (ctxTop) {
        chartTopSensoresInstance = new Chart(ctxTop, {
            type: 'bar',
            data: { labels: [], datasets: [{ label: 'Contaminación', data: [], backgroundColor: '#e53935', barThickness: 20 }] },
            options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { color: '#444' } } } }
        });
    }
}

// Hacemos la función GLOBAL (window.) para que el calendario pueda llamarla sin problemas
window.actualizarTodasLasGraficas = function(fechaForzada = null) {

    // Obtener Gas
    const gasSelect = document.getElementById('statsGasSelect');
    const gasKey = gasSelect ? gasSelect.value : 'NO2';
    const tipoId = GAS_IDS_STATS[gasKey];

    // Obtener Fecha: Si nos la pasan (desde el calendario), la usamos.
    // Si no, intentamos buscarla en el input, y si no, usamos HOY.
    let fecha = fechaForzada;

    if (!fecha) {
        // Buscamos el input de fecha de estadísticas
        const datePickerInput = document.querySelector('#estadisticas-content .date-picker');
        if (datePickerInput && datePickerInput._flatpickr && datePickerInput._flatpickr.selectedDates.length > 0) {
            fecha = datePickerInput._flatpickr.formatDate(datePickerInput._flatpickr.selectedDates[0], "Y-m-d");
        } else {
            fecha = new Date().toISOString().split('T')[0]; // Hoy
        }
    }

    console.log(`📊 Actualizando gráficas > Gas: ${gasKey}, Fecha: ${fecha}`);

    cargarEvolucion(tipoId, fecha);
    cargarMinMax(tipoId, fecha);
    cargarTopSensores(tipoId, fecha);
};

// --- FETCHERS ---

function cargarEvolucion(tipoId, fecha) {
    if(!chartEvolucionInstance) return;
    fetch(`../api/index.php?accion=getEvolucionDiaria&tipo_id=${tipoId}&fecha=${fecha}`)
        .then(res => res.json())
        .then(data => {
            const labels = [];
            const valores = [];
            for (let i = 0; i < 24; i++) {
                labels.push(`${i}:00`);
                // Buscamos coincidencia numérica estricta
                const dato = data.find(d => parseInt(d.hora) === i);
                valores.push(dato ? parseFloat(dato.media) : 0);
            }
            chartEvolucionInstance.data.labels = labels;
            chartEvolucionInstance.data.datasets[0].data = valores;
            chartEvolucionInstance.update();
        })
        .catch(e => console.error(e));
}

function cargarMinMax(tipoId, fecha) {
    if(!chartMinMaxInstance) return;
    fetch(`../api/index.php?accion=getMinMaxGlobal&tipo_id=${tipoId}&fecha=${fecha}`)
        .then(res => res.json())
        .then(data => {
            // Si todo es 0, Chart.js a veces no pinta nada.
            // minBarLength ayuda, pero los datos deben llegar bien.
            const valores = [
                parseFloat(data.minimo || 0),
                parseFloat(data.media || 0),
                parseFloat(data.maximo || 0)
            ];
            chartMinMaxInstance.data.datasets[0].data = valores;
            chartMinMaxInstance.update();
        })
        .catch(e => console.error(e));
}

function cargarTopSensores(tipoId, fecha) {
    if(!chartTopSensoresInstance) return;
    fetch(`../api/index.php?accion=getTopSensores&tipo_id=${tipoId}&fecha=${fecha}`)
        .then(res => res.json())
        .then(data => {
            // Data debe ser un array. Si está vacío, limpiamos la gráfica.
            const labels = data.map(d => d.nombre);
            const valores = data.map(d => d.valor);

            chartTopSensoresInstance.data.labels = labels;
            chartTopSensoresInstance.data.datasets[0].data = valores;
            chartTopSensoresInstance.update();
        })
        .catch(e => console.error(e));
}