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
                    if (typeof actualizarGrafica === 'function') {
                        // actualizarGrafica(fechaParaAPI); // Descomenta cuando implementes filtro en gráficas
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

let myChartInstance = null;

function initStatisticsChart() {
    // IMPORTANTE: Asegúrate de que en tu HTML el ID sea 'chartEvolucion' o cambia esto a 'myAirChart'
    // En el código que pasaste antes tenías 'chartEvolucion' en el HTML.
    const ctx = document.getElementById('chartEvolucion');
    if (!ctx) return;

    if (myChartInstance) myChartInstance.destroy();

    myChartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [
                {
                    label: 'Calidad del Aire',
                    data: [],
                    borderColor: '#152D9A',
                    backgroundColor: 'rgba(21, 45, 154, 0.1)',
                    borderWidth: 3,
                    tension: 0.4,
                    pointRadius: 3,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#444' } },
                x: { grid: { display: false } }
            }
        }
    });
}

// Función temporal de simulación para que veas la gráfica moverse
// (Descomenta el fetch cuando tengas el PHP de estadísticas listo)
function actualizarGrafica() {
    if (!myChartInstance) return;

    // Simulación de datos
    console.log("Cargando gráfica simulada...");
    const labels = [];
    const data = [];
    for(let i=0; i<24; i++) {
        labels.push(i + ":00");
        data.push(Math.floor(Math.random() * 50) + 10);
    }

    myChartInstance.data.labels = labels;
    myChartInstance.data.datasets[0].data = data;
    myChartInstance.update();
}