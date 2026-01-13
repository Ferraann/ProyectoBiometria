/**
 * @file dashboard_cliente.js
 * @brief Gestión de componentes de interfaz de usuario del Dashboard.
 * @details Controla la lógica de calendarios dinámicos, menús desplegables de navegación,
 * el sistema de pestañas (Tabs), paneles modales informativos y gráficas estadísticas.
 * @author Greysy
 * @date 11/11/2025
 */

/**
 * @section 1. LÓGICA DEL CALENDARIO (REUTILIZABLE)
 */

/**
 * @name Calendario
 * @{
 * @brief Inicialización de selectores de fecha.
 */

const allDatePickers = document.querySelectorAll('.date-picker');

allDatePickers.forEach(picker => {
    flatpickr(picker, {
        dateFormat: "d/m/Y",
        defaultDate: "12/11/2025",

        /**
         * @brief Callback ejecutado al cambiar la fecha.
         */
        onChange: function(selectedDates, dateStr, instance) {
            instance.element.querySelector('span').textContent = 'Fecha: ' + dateStr;
            
            // Si estamos en la pestaña de estadísticas, actualizar la gráfica al cambiar la fecha
            if (instance.element.id === 'date-picker-stats') {
                if (typeof actualizarGrafica === 'function') {
                    actualizarGrafica();
                }
            }
        }
    });
});
/** @} */

/**
 * @section 2. LÓGICA DEL DROPDOWN (REUTILIZABLE)
 */

/**
 * @name DropdownNavegacion
 * @{
 */

const allDropdownButtons = document.querySelectorAll('.dropdown-mapa');

allDropdownButtons.forEach(button => {
    const menu = button.nextElementSibling;
    if (!menu || !menu.classList.contains('dropdown-menu')) return;

    const dropdownItems = menu.querySelectorAll('.dropdown-item');
    const span = button.querySelector('span');

    button.addEventListener('click', function(event) {
        event.stopPropagation();
        menu.classList.toggle('show');
    });

    dropdownItems.forEach(item => {
        item.addEventListener('click', function(event) {
            event.preventDefault();
            span.textContent = item.textContent.trim();

            /**
             * @brief Integración con la lógica del mapa
             */
            if (typeof switchMapView === 'function') {
                if (item.textContent.includes("Mis sensores personales")) {
                    switchMapView('personal');
                } else {
                    switchMapView('general');
                }
            }
            menu.classList.remove('show');
        });
    });
});

document.addEventListener('click', function(event) {
    if (!event.target.closest('.dropdown-container')) {
        document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
            menu.classList.remove('show');
        });
    }
});
/** @} */

/**
 * @section 3. LÓGICA DE PESTAÑAS (TABS)
 * @details Modificado para inicializar mapas O gráficas según la pestaña.
 */

/**
 * @name SistemaPestanas
 * @{
 */

const tabLinks = document.querySelectorAll('.sensores-nav a');

tabLinks.forEach(link => {
    link.addEventListener('click', function(event) {
        event.preventDefault();
        const tabId = this.dataset.tab;

        // Gestión de clases visuales
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

        // Lógica específica por pestaña
        if (tabId === 'mapas') {
            setTimeout(() => {
                if (typeof initializeDashboardMap === 'function') {
                    initializeDashboardMap();
                }
            }, 150);
        } else if (tabId === 'estadisticas') {
            // Inicializar y cargar gráfica
            setTimeout(() => {
                if (typeof initStatisticsChart === 'function') {
                    initStatisticsChart();
                    actualizarGrafica(); // Cargar datos iniciales
                }
            }, 150);
        }
    });
});

document.addEventListener('DOMContentLoaded', () => {
    // Verificar pestaña activa al inicio
    const activeTab = document.querySelector('.sensores-nav a.active');
    
    if (activeTab && activeTab.dataset.tab === 'mapas') {
        setTimeout(() => {
            if (typeof initializeDashboardMap === 'function') initializeDashboardMap();
        }, 400);
    } else if (activeTab && activeTab.dataset.tab === 'estadisticas') {
        setTimeout(() => {
            if (typeof initStatisticsChart === 'function') {
                initStatisticsChart();
                actualizarGrafica();
            }
        }, 400);
    }

    // Listeners para los selectores de la gráfica (Sensor y Gas)
    const sensorSelect = document.getElementById('sensor-select');
    const gasSelect = document.getElementById('gas-select');

    if(sensorSelect) {
        sensorSelect.addEventListener('change', actualizarGrafica);
    }
    if(gasSelect) {
        gasSelect.addEventListener('change', actualizarGrafica);
    }
});
/** @} */

/**
 * @section 4. LÓGICA DEL MODAL
 */

/**
 * @name PanelInfoModal
 * @{
 */
document.addEventListener('DOMContentLoaded', () => {
    const infoModal = document.getElementById('gas-info-panel');
    const openInfoBtn = document.getElementById('open-info-btn');
    const closeInfoBtn = document.getElementById('close-info-btn');

    if (openInfoBtn) {
        openInfoBtn.addEventListener('click', () => {
            infoModal.style.display = 'block';
        });
    }

    if (closeInfoBtn) {
        closeInfoBtn.addEventListener('click', () => {
            infoModal.style.display = 'none';
        });
    }

    window.addEventListener('click', (event) => {
        if (event.target === infoModal) {
            infoModal.style.display = 'none';
        }
    });

    window.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && infoModal.style.display === 'block') {
            infoModal.style.display = 'none';
        }
    });
});
/** @} */


/**
 * @section 5. LÓGICA DE ESTADÍSTICAS (CHART.JS + AJAX)
 * @details Manejo de la gráfica con datos reales desde PHP.
 */

let myChartInstance = null; // Variable global para la instancia del gráfico

/**
 * @brief Inicializa el canvas de la gráfica (esqueleto vacío).
 */
function initStatisticsChart() {
    const ctx = document.getElementById('myAirChart');
    if (!ctx) return;

    // Destruir gráfica anterior si existe para evitar superposiciones
    if (myChartInstance) {
        myChartInstance.destroy();
    }

    myChartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [], // Se llenará con AJAX
            datasets: [
                {
                    label: 'Calidad del Aire',
                    data: [], // Se llenará con AJAX
                    borderColor: '#152D9A', // --Principal_1
                    backgroundColor: 'rgba(21, 45, 154, 0.1)',
                    borderWidth: 4,
                    tension: 0.4,
                    pointBackgroundColor: '#152D9A',
                    pointRadius: 4
                },
                // Línea de límite peligroso (estática o dinámica según prefieras)
                {
                    label: 'Límite Peligroso',
                    data: [], 
                    borderColor: '#dc3545',
                    borderWidth: 2,
                    borderDash: [5, 5],
                    pointRadius: 0,
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#f0f0f0' }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
}

/**
 * @brief Solicita datos al servidor PHP y actualiza la gráfica.
 */
async function actualizarGrafica() {
    // 1. Obtener elementos del DOM
    const sensorSelect = document.getElementById('sensor-select');
    const gasSelect = document.getElementById('gas-select');
    const datePickerSpan = document.querySelector('#date-picker-stats span');

    // Validación simple
    if (!sensorSelect || !gasSelect || !datePickerSpan) return;

    const sensorId = sensorSelect.value;
    const gasTipo = gasSelect.value;
    
    // Obtener fecha limpia (quitando el texto "Fecha: ")
    // Convertir de DD/MM/YYYY a YYYY-MM-DD para MySQL
    let fechaTexto = datePickerSpan.textContent.replace('Fecha: ', '').trim();
    let partesFecha = fechaTexto.split('/');
    let fechaSQL = `${partesFecha[2]}-${partesFecha[1]}-${partesFecha[0]}`; 

    try {
        // 2. Fetch a PHP
        const url = `../php/obtener_datos_grafica.php?sensor=${sensorId}&gas=${gasTipo}&fecha=${fechaSQL}`;
        const respuesta = await fetch(url);
        const datos = await respuesta.json();

        if (datos.error) {
            console.error("Error del servidor:", datos.error);
            return;
        }

        // 3. Procesar datos para Chart.js
        const etiquetas = datos.map(item => item.hora);
        const valores = datos.map(item => parseFloat(item.valor));
        
        // Crear una línea recta para el límite (ejemplo: valor 50 fijo)
        const limites = new Array(valores.length).fill(50); 

        // 4. Actualizar gráfica
        if (myChartInstance) {
            myChartInstance.data.labels = etiquetas;
            myChartInstance.data.datasets[0].data = valores;
            myChartInstance.data.datasets[1].data = limites; // Actualizar línea de límite
            myChartInstance.update();
        }

        // 5. Actualizar carita y mensaje (Lógica simple de ejemplo)
        actualizarEstadoCarita(valores);

    } catch (error) {
        console.error("Error cargando datos de la gráfica:", error);
    }
}

/**
 * @brief Cambia el icono y mensaje según el promedio de valores.
 */
function actualizarEstadoCarita(valores) {
    if (valores.length === 0) return;

    // Calcular promedio
    const suma = valores.reduce((a, b) => a + b, 0);
    const promedio = suma / valores.length;
    
    const statusDiv = document.querySelector('.status-message');
    const icon = statusDiv.querySelector('i');
    const text = statusDiv.querySelector('p');

    // Reseteamos clases
    statusDiv.classList.remove('good', 'warning', 'bad');
    icon.className = ''; // Limpiar iconos anteriores

    if (promedio > 50) {
        statusDiv.classList.add('bad');
        icon.classList.add('fa-solid', 'fa-face-frown-open');
        text.textContent = "El día de hoy la calidad de aire es muy mala.";
    } else if (promedio > 20) {
        statusDiv.classList.add('warning');
        icon.classList.add('fa-solid', 'fa-face-meh');
        text.textContent = "Calidad del aire moderada. Precaución.";
    } else {
        statusDiv.classList.add('good');
        icon.classList.add('fa-solid', 'fa-face-smile');
        text.textContent = "¡Aire limpio! Disfruta de tu día.";
    }
}