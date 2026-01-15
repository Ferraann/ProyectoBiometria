/**
 * @file dashboard_cliente.js
 * @brief Gestión de la interfaz: Pestañas, Modales, Calendarios y Gráficas.
 */

document.addEventListener('DOMContentLoaded', () => {

    // =========================================================
    // 1. LÓGICA DE PESTAÑAS (TABS) - CONEXIÓN CON MAPA
    // =========================================================
    const tabLinks = document.querySelectorAll('.sensores-nav a');

    tabLinks.forEach(link => {
        link.addEventListener('click', function(event) {
            event.preventDefault();
            const tabId = this.dataset.tab;

            // Gestión visual de clases (Active)
            tabLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');

            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(content => {
                if (content.getAttribute('data-tab-content') === tabId) {
                    content.classList.add('active-tab-content');
                } else {
                    content.classList.remove('active-tab-content');
                }
            });

            // Lógica específica al cambiar de pestaña
            if (tabId === 'mapas') {
                setTimeout(() => {
                    // Si existe el mapa, lo redimensionamos para evitar errores de renderizado
                    if (typeof map !== 'undefined') {
                        map.invalidateSize();
                    }
                    // Llamamos a la función de carga de map-logic.js
                    if (typeof loadData === 'function') {
                        loadData();
                    }
                }, 150);

            } else if (tabId === 'estadisticas') {
                setTimeout(() => {
                    if (typeof initStatisticsChart === 'function') {
                        initStatisticsChart();
                        actualizarGrafica();
                    }
                }, 150);
            }
        });
    });

    // =========================================================
    // 2. CALENDARIOS Y MODALES (Tus funciones originales)
    // =========================================================

    const allDatePickers = document.querySelectorAll('.date-picker');

    allDatePickers.forEach(picker => {
        flatpickr(picker, {
            dateFormat: "d/m/Y", // Formato visual para el usuario (Español)
            defaultDate: "today",
            disableMobile: "true",
            locale: {
                firstDayOfWeek: 1 // Lunes
            },
            onChange: function(selectedDates, dateStr, instance) {
                // 1. Actualizamos el texto visual
                instance.element.querySelector('span').textContent = 'Fecha: ' + dateStr;

                // 2. Convertimos la fecha a formato YYYY-MM-DD para la API (MySQL)
                const fechaParaAPI = instance.formatDate(selectedDates[0], "Y-m-d");

                // 3. Detectamos en qué pestaña estamos
                if (instance.element.closest('#mapas-content')) {
                    // Si estamos en el mapa, llamamos a la función global de map-logic.js
                    if (typeof updateMapByDate === 'function') {
                        updateMapByDate(fechaParaAPI);
                    }
                } else if (instance.element.closest('#estadisticas-content')) {
                    // Si estamos en estadísticas, llamamos a su función de actualización
                    if (typeof actualizarTodasLasGraficas === 'function') {
                        actualizarTodasLasGraficas(); // La función tomará la fecha del input automáticamente
                    }
                }
            }
        });
    });

    // DROPDOWNS
    const allDropdownButtons = document.querySelectorAll('.dropdown-mapa');
    allDropdownButtons.forEach(button => {
        button.addEventListener('click', function(event) {
            event.stopPropagation();
            const menu = this.nextElementSibling;
            if (menu && menu.classList.contains('dropdown-menu')) {
                menu.classList.toggle('show');
            }
        });
    });
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.dropdown-container')) {
            document.querySelectorAll('.dropdown-menu.show').forEach(m => m.classList.remove('show'));
        }
    });

    // MODAL INFO
    const infoModal = document.getElementById('gas-info-panel');
    const openInfoBtn = document.getElementById('open-info-btn');
    const closeInfoBtn = document.getElementById('close-info-btn');

    if (openInfoBtn && infoModal) openInfoBtn.addEventListener('click', () => infoModal.style.display = 'block');
    if (closeInfoBtn && infoModal) closeInfoBtn.addEventListener('click', () => infoModal.style.display = 'none');
    window.addEventListener('click', (e) => { if (e.target === infoModal) infoModal.style.display = 'none'; });

});


// =========================================================
// 3. LÓGICA DE ESTADÍSTICAS (CHART.JS)
// =========================================================

// Variables globales para las instancias de las gráficas
let chartEvolucionInstance = null;
let chartMinMaxInstance = null;
let chartTopSensoresInstance = null;

// Mapa de IDs de gases (Debe coincidir con BBDD)
const GAS_IDS_STATS = { "NO2": 1, "O3": 2, "SO2": 3, "CO": 4, "PM10": 5 };

document.addEventListener('DOMContentLoaded', () => {
    // Inicializamos las gráficas vacías al cargar la página
    initCharts();

    // Listener para el selector de GAS en Estadísticas
    const statsGasSelect = document.getElementById('statsGasSelect');
    if (statsGasSelect) {
        statsGasSelect.addEventListener('change', () => {
            actualizarTodasLasGraficas();
        });
    }

    // Listener para el calendario (ya lo tenías configurado, solo asegúrate de llamar a actualizarTodasLasGraficas)
    // NOTA: Asegúrate de que tu flatpickr en estadísticas llama a actualizarTodasLasGraficas() en el onChange.
});


function initCharts() {
    // 1. Gráfica Evolución (Línea)
    const ctxEvol = document.getElementById('chartEvolucion');
    if (ctxEvol) {
        chartEvolucionInstance = new Chart(ctxEvol, {
            type: 'line',
            data: { labels: [], datasets: [{ label: 'Media Horaria', data: [], borderColor: '#ffae00', backgroundColor: 'rgba(255, 174, 0, 0.1)', fill: true, tension: 0.4 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false } }, y: { grid: { color: '#444' } } } }
        });
    }

    // 2. Gráfica Min/Max (Barra Vertical)
    const ctxMinMax = document.getElementById('chartMinMax');
    if (ctxMinMax) {
        chartMinMaxInstance = new Chart(ctxMinMax, {
            type: 'bar',
            data: { labels: ['Mínimo', 'Promedio', 'Máximo'], datasets: [{ label: 'Valores', data: [], backgroundColor: ['#00d2ff', '#3a7bd5', '#ff4b1f'] }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: '#444' } } } }
        });
    }

    // 3. Gráfica Top 5 Sensores (Barra Horizontal)
    const ctxTop = document.getElementById('chartTopSensores');
    if (ctxTop) {
        chartTopSensoresInstance = new Chart(ctxTop, {
            type: 'bar', // Chart.js v3+ usa 'bar' con indexAxis:'y' para horizontal
            data: { labels: [], datasets: [{ label: 'Contaminación Media', data: [], backgroundColor: '#e53935' }] },
            options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { color: '#444' } } } }
        });
    }
}

// Función maestra que orquesta la actualización
function actualizarTodasLasGraficas() {
    // 1. Obtener valores de los selectores (Gas y Fecha)
    const gasSelect = document.getElementById('statsGasSelect');
    const datePickerInput = document.querySelector('#estadisticas-content .date-picker input'); // Ojo: flatpickr usa un input oculto o el div

    // Obtenemos la fecha del picker de la pestaña estadísticas.
    // Si usas la clase .date-picker, flatpickr suele guardar la fecha en el atributo value del input asociado.
    // Asumiremos que el picker guarda la fecha en formato Y-m-d o usamos la fecha de hoy si falla.
    // TRUCO: Flatpickr instance se puede recuperar. Pero para simplificar, usaremos la fecha actual si no detectamos una.

    let fecha = new Date().toISOString().split('T')[0]; // Por defecto HOY
    // Intentamos recuperar la fecha seleccionada del input flatpickr si existe
    if (datePickerInput && datePickerInput._flatpickr) {
        fecha = datePickerInput._flatpickr.formatDate(datePickerInput._flatpickr.selectedDates[0], "Y-m-d");
    }

    const gasKey = gasSelect ? gasSelect.value : 'NO2';
    const tipoId = GAS_IDS_STATS[gasKey];

    console.log(`📊 Cargando estadísticas para Gas: ${gasKey} (${tipoId}), Fecha: ${fecha}`);

    // Llamamos a las 3 APIs
    cargarEvolucion(tipoId, fecha);
    cargarMinMax(tipoId, fecha);
    cargarTopSensores(tipoId, fecha);
}

// --- API FETCHERS ---

function cargarEvolucion(tipoId, fecha) {
    fetch(`../api/index.php?accion=getEvolucionDiaria&tipo_id=${tipoId}&fecha=${fecha}`)
        .then(res => res.json())
        .then(data => {
            // Data viene como [{hora: 0, media: 20.5}, {hora: 1, media: 22}...]
            // Necesitamos rellenar las 24 horas aunque no haya datos
            const labels = [];
            const valores = [];

            for (let i = 0; i < 24; i++) {
                labels.push(`${i}:00`);
                // Buscamos si hay dato para esa hora
                const dato = data.find(d => parseInt(d.hora) === i);
                valores.push(dato ? parseFloat(dato.media) : 0); // O null si prefieres huecos
            }

            if (chartEvolucionInstance) {
                chartEvolucionInstance.data.labels = labels;
                chartEvolucionInstance.data.datasets[0].data = valores;
                chartEvolucionInstance.update();
            }
        })
        .catch(err => console.error("Error Evolución:", err));
}

function cargarMinMax(tipoId, fecha) {
    fetch(`../api/index.php?accion=getMinMaxGlobal&tipo_id=${tipoId}&fecha=${fecha}`)
        .then(res => res.json())
        .then(data => {
            // Data: {minimo: 10, maximo: 50, media: 30}
            const valores = [
                parseFloat(data.minimo || 0),
                parseFloat(data.media || 0),
                parseFloat(data.maximo || 0)
            ];

            if (chartMinMaxInstance) {
                chartMinMaxInstance.data.datasets[0].data = valores;
                chartMinMaxInstance.update();
            }
        })
        .catch(err => console.error("Error MinMax:", err));
}

function cargarTopSensores(tipoId, fecha) {
    fetch(`../api/index.php?accion=getTopSensores&tipo_id=${tipoId}&fecha=${fecha}`)
        .then(res => res.json())
        .then(data => {
            // Data: [{nombre: 'Madrid', valor: 55}, ...]
            const labels = data.map(d => d.nombre);
            const valores = data.map(d => d.valor);

            if (chartTopSensoresInstance) {
                chartTopSensoresInstance.data.labels = labels;
                chartTopSensoresInstance.data.datasets[0].data = valores;
                chartTopSensoresInstance.update();
            }
        })
        .catch(err => console.error("Error TopSensores:", err));
}