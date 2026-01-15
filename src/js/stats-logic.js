// js/stats-logic.js

let charts = {}; // Para guardar las instancias de los gráficos y poder actualizarlos

document.addEventListener('DOMContentLoaded', () => {
    // Carga inicial
    loadStatistics();

    // Listener para cambio de gas en estadísticas
    const statsSelect = document.getElementById('statsGasSelect');
    if (statsSelect) {
        statsSelect.addEventListener('change', () => loadStatistics());
    }
});

async function loadStatistics() {
    const gas = document.getElementById('statsGasSelect').value;

    // 1. Cargar Evolución Temporal
    updateEvolucionChart(gas);

    // 2. Cargar Min/Max (Global, no depende solo del gas seleccionado, muestra todos)
    updateMinMaxChart();

    // 3. Cargar Top Sensores
    updateTopSensoresChart(gas);
}

// --- GRÁFICO 1: EVOLUCIÓN ---
async function updateEvolucionChart(gas) {
    const response = await fetch(`../php/api_estadisticas.php?modo=evolucion&gas=${gas}`);
    const data = await response.json();

    const horas = data.map(d => d.hora + ":00");
    const valores = data.map(d => d.media);

    const ctx = document.getElementById('chartEvolucion').getContext('2d');

    if (charts['evolucion']) charts['evolucion'].destroy(); // Destruir anterior

    charts['evolucion'] = new Chart(ctx, {
        type: 'line',
        data: {
            labels: horas,
            datasets: [{
                label: `Nivel promedio de ${gas}`,
                data: valores,
                borderColor: '#ffae00',
                backgroundColor: 'rgba(255, 174, 0, 0.2)',
                tension: 0.4,
                fill: true
            }]
        },
        options: getCommonOptions()
    });
}

// --- GRÁFICO 2: MIN/MAX ---
async function updateMinMaxChart() {
    const response = await fetch(`../php/api_estadisticas.php?modo=minmax`);
    const data = await response.json();

    const labels = data.map(d => d.gas_tipo);
    const minData = data.map(d => d.minimo);
    const maxData = data.map(d => d.maximo);

    const ctx = document.getElementById('chartMinMax').getContext('2d');

    if (charts['minmax']) charts['minmax'].destroy();

    charts['minmax'] = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Mínimo',
                    data: minData,
                    backgroundColor: '#00b894'
                },
                {
                    label: 'Máximo',
                    data: maxData,
                    backgroundColor: '#d63031'
                }
            ]
        },
        options: getCommonOptions()
    });
}

// --- GRÁFICO 3: TOP SENSORES ---
async function updateTopSensoresChart(gas) {
    const response = await fetch(`../php/api_estadisticas.php?modo=comparativa&gas=${gas}`);
    const data = await response.json();

    const labels = data.map(d => d.nombre);
    const values = data.map(d => d.media);

    const ctx = document.getElementById('chartTopSensores').getContext('2d');

    if (charts['top']) charts['top'].destroy();

    charts['top'] = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: `Contaminación media (${gas})`,
                data: values,
                backgroundColor: '#0984e3',
                borderRadius: 5
            }]
        },
        options: {
            ...getCommonOptions(),
            indexAxis: 'y', // Gráfico horizontal
        }
    });
}

// Configuración común para estilo oscuro
function getCommonOptions() {
    return {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { labels: { color: '#fff' } }
        },
        scales: {
            y: {
                ticks: { color: '#aaa' },
                grid: { color: '#444' }
            },
            x: {
                ticks: { color: '#aaa' },
                grid: { color: '#444' }
            }
        }
    };
}