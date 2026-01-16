/**
 * @file dashboard_cliente.js
 * @brief Gestión sincronizada de gráficas y datos del mapa.
 */

let chartEvolucionInstance = null;
let chartMinMaxInstance = null;
let chartTopSensoresInstance = null;
const GAS_IDS_STATS = { "NO2": 1, "O3": 2, "SO2": 3, "CO": 4, "PM10": 5 };

document.addEventListener('DOMContentLoaded', () => {

    // --- PESTAÑAS ---
    const tabLinks = document.querySelectorAll('.sensores-nav a');
    tabLinks.forEach(link => {
        link.addEventListener('click', function(event) {
            event.preventDefault();
            const tabId = this.dataset.tab;

            // Gestión de clases active
            tabLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active-tab-content'));
            document.querySelector(`[data-tab-content="${tabId}"]`).classList.add('active-tab-content');

            if (tabId === 'mapas') {
                setTimeout(() => {
                    if (typeof map !== 'undefined') map.invalidateSize();
                    if (typeof loadData === 'function') loadData();
                }, 150);
            } else if (tabId === 'estadisticas') {
                setTimeout(() => {
                    initCharts();
                    // Al entrar, forzamos actualización con la fecha actual del selector
                    window.actualizarTodasLasGraficas();
                }, 150);
            }
        });
    });

    // --- CALENDARIOS (FLATPICKR) ---
    const allDatePickers = document.querySelectorAll('.date-picker');
    allDatePickers.forEach(picker => {
        flatpickr(picker, {
            dateFormat: "d/m/Y",
            defaultDate: "today",
            locale: { firstDayOfWeek: 1 },
            onChange: async function(selectedDates, dateStr, instance) {
                // 1. Convertir fecha visual (16/01/2026) a formato SQL (2026-01-16)
                const fechaParaAPI = instance.formatDate(selectedDates[0], "Y-m-d");

                // 2. Actualizar texto visual
                instance.element.querySelector('span').textContent = 'Fecha: ' + dateStr;

                // 3. LLAMAR A LA API PARA ACTUALIZAR EL MAPA
                // Esto va a api/index.php?accion=getMedicionesXTipo&fecha=2026-01-16
                if (typeof window.updateMapByDate === 'function') {
                    await window.updateMapByDate(fechaParaAPI);
                }

                // 4. Actualizar gráficas también
                if (typeof window.actualizarTodasLasGraficas === 'function') {
                    window.actualizarTodasLasGraficas(fechaParaAPI);
                }
            }
        });
    });

    // --- SELECTOR DE GAS (ESTADÍSTICAS) ---
    const statsGasSelect = document.getElementById('statsGasSelect');
    if (statsGasSelect) {
        statsGasSelect.addEventListener('change', () => {
            window.actualizarTodasLasGraficas();
        });
    }

    // --- OTROS (Dropdowns, Modales) ---
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

    // Modal Info
    const infoModal = document.getElementById('gas-info-panel');
    const openInfoBtn = document.getElementById('open-info-btn');
    const closeInfoBtn = document.getElementById('close-info-btn');
    if(openInfoBtn) openInfoBtn.addEventListener('click', () => infoModal.style.display = 'block');
    if(closeInfoBtn) closeInfoBtn.addEventListener('click', () => infoModal.style.display = 'none');
    window.addEventListener('click', (e) => { if(e.target === infoModal) infoModal.style.display = 'none'; });

});


// =========================================================
// LÓGICA DE GRÁFICAS
// =========================================================

function initCharts() {
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
    // 2. Min/Max
    const ctxMinMax = document.getElementById('chartMinMax');
    if (ctxMinMax) {
        chartMinMaxInstance = new Chart(ctxMinMax, {
            type: 'bar',
            data: { labels: ['Mínimo', 'Promedio', 'Máximo'], datasets: [{ label: 'Valores', data: [], backgroundColor: ['#00d2ff', '#3a7bd5', '#ff4b1f'], minBarLength: 5 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: '#444' } } } }
        });
    }
    // 3. Top 5
    const ctxTop = document.getElementById('chartTopSensores');
    if (ctxTop) {
        chartTopSensoresInstance = new Chart(ctxTop, {
            type: 'bar',
            data: { labels: [], datasets: [{ label: 'Top 5 Estaciones Oficiales', data: [], backgroundColor: '#FFD700', barThickness: 20 }] },
            options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { color: '#444' } } } }
        });
    }
}

window.actualizarTodasLasGraficas = function(fechaForzada = null) {
    const gasSelect = document.getElementById('statsGasSelect');
    const gasKey = gasSelect ? gasSelect.value : 'NO2';
    const tipoId = GAS_IDS_STATS[gasKey];

    // Obtener fecha del calendario si no viene forzada
    let fecha = fechaForzada;
    if (!fecha) {
        const picker = document.querySelector('#estadisticas-content .date-picker');
        if (picker && picker._flatpickr && picker._flatpickr.selectedDates[0]) {
            fecha = picker._flatpickr.formatDate(picker._flatpickr.selectedDates[0], "Y-m-d");
        } else {
            fecha = new Date().toISOString().split('T')[0];
        }
    }

    // Llamamos a las APIs (PHP) para Evolución y MinMax
    cargarEvolucion(tipoId, fecha);
    cargarMinMax(tipoId, fecha);
};

function cargarEvolucion(tipoId, fecha) {
    if(!chartEvolucionInstance) return;
    fetch(`../api/index.php?accion=getEvolucionDiaria&tipo_id=${tipoId}&fecha=${fecha}`)
        .then(res => res.json())
        .then(data => {
            const labels = [], valores = [];
            for(let i=0; i<24; i++) {
                labels.push(`${i}:00`);
                const d = data.find(x => parseInt(x.hora) === i);
                valores.push(d ? parseFloat(d.media) : 0);
            }
            chartEvolucionInstance.data.labels = labels;
            chartEvolucionInstance.data.datasets[0].data = valores;
            chartEvolucionInstance.update();
        }).catch(e => console.error(e));
}

function cargarMinMax(tipoId, fecha) {
    if(!chartMinMaxInstance) return;
    fetch(`../api/index.php?accion=getMinMaxGlobal&tipo_id=${tipoId}&fecha=${fecha}`)
        .then(res => res.json())
        .then(data => {
            const v = [parseFloat(data.minimo||0), parseFloat(data.media||0), parseFloat(data.maximo||0)];
            chartMinMaxInstance.data.datasets[0].data = v;
            chartMinMaxInstance.update();
        }).catch(e => console.error(e));
}