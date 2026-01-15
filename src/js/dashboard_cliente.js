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
    // (Mantén tu código de dropdowns y modales aquí si lo tenías)
    // ...
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

// ESTA ES LA FUNCIÓN QUE CALCULA EL TOP 5 EN FRONTEND
function cargarTopSensoresFrontend(gasKey) {
    if (!chartTopSensoresInstance) return;

    // 1. Verificar datos
    const datosContaminacion = (window.SERVER_DATA && window.SERVER_DATA[gasKey]) ? window.SERVER_DATA[gasKey] : [];

    if (datosContaminacion.length === 0) {
        chartTopSensoresInstance.data.labels = [];
        chartTopSensoresInstance.data.datasets[0].data = [];
        chartTopSensoresInstance.update();
        return;
    }

    const stations = window.stations || [];
    const config = window.gasConfig || {};
    const conversion = (config[gasKey] ? config[gasKey].conversion : 1);

    // 2. PREPARAR ACUMULADORES
    // Creamos un mapa para guardar los valores asignados a cada estación
    // Estructura: { 'NombreEstacion': { suma: 0, cuenta: 0, maximo: 0 } }
    let mapaEstaciones = {};
    stations.forEach(st => {
        mapaEstaciones[st.name] = {
            lat: st.lat,
            lng: st.lng || st.lon,
            maximo: 0 // Guardaremos el pico más alto detectado cerca de ella
        };
    });

    // 3. BARRIDO DE DATOS (Cada dato busca su dueño)
    datosContaminacion.forEach(dato => {
        let estacionMasCercana = null;
        let distMinima = Infinity;

        // Buscamos a qué estación oficial pertenece este dato
        stations.forEach(st => {
            const dist = Math.sqrt(Math.pow(st.lat - dato.lat, 2) + Math.pow((st.lng||st.lon) - dato.lon, 2));
            if (dist < distMinima) {
                distMinima = dist;
                estacionMasCercana = st.name;
            }
        });

        // FILTRO DE DISTANCIA MÁXIMA (Opcional pero recomendado)
        // Si el dato está a más de 0.1 grados (~11km) de la estación, lo ignoramos
        // para que un dato en Galicia no afecte a Madrid si no hay estaciones cerca.
        if (distMinima < 0.1 && estacionMasCercana) {
            const valorReal = parseFloat(dato.value);
            // Nos quedamos con el valor MÁXIMO detectado en su zona de influencia
            if (valorReal > mapaEstaciones[estacionMasCercana].maximo) {
                mapaEstaciones[estacionMasCercana].maximo = valorReal;
            }
        }
    });

    // 4. GENERAR RANKING
    let ranking = Object.keys(mapaEstaciones).map(nombre => {
        return {
            nombre: nombre,
            // Aplicamos conversión y redondeo
            valor: parseFloat((mapaEstaciones[nombre].maximo * conversion).toFixed(2))
        };
    });

    // 5. ORDENAR Y CORTAR
    ranking.sort((a, b) => b.valor - a.valor);
    const top5 = ranking.slice(0, 5);

    // 6. PINTAR
    chartTopSensoresInstance.data.labels = top5.map(x => x.nombre);
    chartTopSensoresInstance.data.datasets[0].data = top5.map(x => x.valor);
    chartTopSensoresInstance.data.datasets[0].label = `Top 5 Estaciones (${gasKey})`;

    // Si todos son 0 (no hay datos cerca de estaciones), limpiamos para que no salga feo
    if (top5.length > 0 && top5[0].valor === 0) {
        chartTopSensoresInstance.data.datasets[0].data = [];
    }

    chartTopSensoresInstance.update();
}