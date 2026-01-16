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
                // 1. Actualizar texto visual
                instance.element.querySelector('span').textContent = 'Fecha: ' + dateStr;

                // 2. Formato SQL
                const fechaParaAPI = instance.formatDate(selectedDates[0], "Y-m-d");

                // 3. Sincronizar todos los calendarios (para que tengan la misma fecha)
                document.querySelectorAll('.date-picker').forEach(p => {
                    if (p._flatpickr && p !== instance.element) {
                        p._flatpickr.setDate(selectedDates[0], false);
                        p.querySelector('span').textContent = 'Fecha: ' + dateStr;
                    }
                });

                console.log("📅 Cambio de fecha detectado:", fechaParaAPI);

                // 4. LÓGICA CENTRALIZADA: Siempre actualizamos los datos primero
                if (typeof window.updateMapByDate === 'function') {
                    // Esperamos a que se bajen los datos nuevos
                    await window.updateMapByDate(fechaParaAPI);
                }

                // 5. Si estamos en estadísticas, repintamos las gráficas con los datos NUEVOS
                if (document.getElementById('estadisticas-content').classList.contains('active-tab-content')) {
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

    console.log(`📊 Actualizando Gráficas para: ${gasKey} en fecha ${fecha}`);

    // Llamamos a las APIs (PHP) para Evolución y MinMax
    cargarEvolucion(tipoId, fecha);
    cargarMinMax(tipoId, fecha);

    // Llamamos a la función FRONTEND para el Top 5 (usando los datos recién bajados)
    cargarTopSensoresFrontend(gasKey);
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

// =========================================================================
// FUNCIÓN CORREGIDA: Asignación exclusiva (1 dato -> 1 estación)
// =========================================================================
function cargarTopSensoresFrontend(gasKey) {
    if (!chartTopSensoresInstance) return;

    // 1. Obtener datos crudos (descargados de la BBDD)
    const datosContaminacion = (window.SERVER_DATA && window.SERVER_DATA[gasKey]) ? window.SERVER_DATA[gasKey] : [];

    // Si no hay datos para ese día/gas, limpiamos la gráfica
    if (datosContaminacion.length === 0) {
        chartTopSensoresInstance.data.labels = [];
        chartTopSensoresInstance.data.datasets[0].data = [];
        chartTopSensoresInstance.update();
        return;
    }

    const stations = window.stations || [];
    const config = window.gasConfig || {};
    // Obtenemos el factor de conversión (ej: CO se multiplica por 500)
    const conversion = (config[gasKey] ? config[gasKey].conversion : 1);

    // 2. LÓGICA: BUSCAR EL PICO MÁXIMO CERCANO A CADA ESTACIÓN
    const ranking = stations.map(st => {
        let maxValorEncontrado = 0; // Empezamos en 0
        let hayDatosCerca = false;

        const latSt = st.lat;
        const lonSt = st.lng || st.lon;

        datosContaminacion.forEach(dato => {
            // Calcular distancia (Pitágoras)
            const dist = Math.sqrt(Math.pow(latSt - dato.lat, 2) + Math.pow(lonSt - dato.lon, 2));

            // RADIO DE BÚSQUEDA: 0.1 grados son aprox 11km.
            // Si el dato está cerca de la estación...
            if (dist < 0.1) {
                hayDatosCerca = true;
                const val = parseFloat(dato.value);

                // ...y es MAYOR que lo que ya teníamos, nos quedamos con él.
                if (val > maxValorEncontrado) {
                    maxValorEncontrado = val;
                }
            }
        });

        return {
            nombre: st.name,
            // Aplicamos la conversión al valor máximo encontrado
            valor: hayDatosCerca ? parseFloat((maxValorEncontrado * conversion).toFixed(2)) : 0
        };
    });

    // 3. ORDENAR: De más contaminado a menos
    ranking.sort((a, b) => b.valor - a.valor);

    // 4. TOP 5
    const top5 = ranking.slice(0, 5);

    // 5. PINTAR
    chartTopSensoresInstance.data.labels = top5.map(x => x.nombre);
    chartTopSensoresInstance.data.datasets[0].data = top5.map(x => x.valor);
    chartTopSensoresInstance.data.datasets[0].label = `Top 5 Estaciones (${gasKey})`;

    // Asignar color (Dorado para indicar Oficiales)
    chartTopSensoresInstance.data.datasets[0].backgroundColor = '#FFD700';

    chartTopSensoresInstance.update();
}

function cargarTopSensoresFrontend(gasKey) {
    // Si la gráfica no está inicializada, no hacemos nada
    if (!chartTopSensoresInstance) return;

    // 1. OBTENER DATOS SELECCIONADOS
    const tipoId = GAS_IDS_STATS[gasKey]; // Convertimos "NO2" -> 1

    // Buscar la fecha del selector (flatpickr)
    let fecha = new Date().toISOString().split('T')[0]; // Por defecto hoy
    const picker = document.querySelector('#estadisticas-content .date-picker');
    if (picker && picker._flatpickr && picker._flatpickr.selectedDates[0]) {
        fecha = picker._flatpickr.formatDate(picker._flatpickr.selectedDates[0], "Y-m-d");
    }

    // Factor de conversión (ej: para CO a veces se usa, si no, es 1)
    const config = window.gasConfig || {};
    const conversion = (config[gasKey] ? config[gasKey].conversion : 1);

    console.log(`Buscando Top 5 para Gas: ${gasKey} (${tipoId}) en Fecha: ${fecha}`);

    // 2. PETICIÓN AL SERVIDOR (Aquí ocurre la magia)
    fetch(`../api/index.php?accion=getTopSensores&tipo_id=${tipoId}&fecha=${fecha}`)
        .then(res => res.json())
        .then(data => {
            // data es un array de 5 objetos: [{nombre: "Madrid", valor: 500}, ...]

            // Preparamos arrays para Chart.js
            const etiquetas = [];
            const valores = [];
            const colores = [];

            data.forEach(item => {
                etiquetas.push(item.nombre);

                // Aplicamos conversión si hace falta
                const valFinal = (item.valor * conversion).toFixed(2);
                valores.push(valFinal);

                // COLOR: Si el valor es muy alto (>100), Rojo Alerta. Si no, Dorado.
                if (valFinal > 100) {
                    colores.push('#ff4b1f'); // Rojo
                } else {
                    colores.push('#FFD700'); // Dorado
                }
            });

            // 3. PINTAR LA GRÁFICA
            chartTopSensoresInstance.data.labels = etiquetas;
            chartTopSensoresInstance.data.datasets[0].data = valores;
            chartTopSensoresInstance.data.datasets[0].backgroundColor = colores;
            chartTopSensoresInstance.data.datasets[0].label = `Máximos ${gasKey} (µg/m³)`;

            chartTopSensoresInstance.update();
        })
        .catch(err => console.error("Error cargando Top 5:", err));
}