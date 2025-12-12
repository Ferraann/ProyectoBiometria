'use strict';

// ==============================================
// 1. CONFIGURACIÓN DE GASES Y DATOS GLOBALES
// ==============================================
const GAS_CONFIG = {
    MAX_GAS: { property: 'MAX_INTENSITY', name: 'Máximo Riesgo (Combinado)', units: 'N/A', thresholds: [0.5, 0.8], maxVal: 1.0 },
    NO2: { property: 'valor_NO2', name: 'Dióxido de Nitrógeno (NO₂)', units: 'µg/m³', thresholds: [20, 40], maxVal: 50 },
    O3: { property: 'valor_O3', name: 'Ozono (O₃)', units: 'µg/m³', thresholds: [100, 120], maxVal: 130 },
    CO: { property: 'valor_CO', name: 'Monóxido de Carbono (CO)', units: 'mg/m³', thresholds: [5, 10], maxVal: 12 },
    PM10: { property: 'valor_PM10', name: 'Partículas <10µm (PM₁₀)', units: 'µg/m³', thresholds: [20, 40], maxVal: 50 },
    SO2: { property: 'valor_SO2', name: 'Dióxido de Azufre (SO₂)', units: 'µg/m³', thresholds: [5, 20], maxVal: 30 }
};

let currentGas = 'MAX_GAS';
let currentView = 'general'; // 'general' o 'personal'
let peninsulaDataGlobal = [];
let localDataGlobal = [];

let mapaInstance = null;
let heatmapLayerGroup = L.layerGroup();
let heatLayer = null;
let legend = L.control({position: 'bottomright'});

// ==============================================
// 2. LÓGICA DE CÁLCULO Y FILTRADO
// ==============================================

function getColor(valor, thresholds) {
    if (valor === 0) return '#CCC';
    if (valor > thresholds[1]) return '#FF0000';
    if (valor > thresholds[0]) return '#FFFF00';
    return '#00FF00';
}

function getMaxGasIntensity(station) {
    let maxIntensity = 0;
    for (const key in GAS_CONFIG) {
        if (key !== 'MAX_GAS' && GAS_CONFIG[key].property) {
            const config = GAS_CONFIG[key];
            const value = station[config.property] || 0;
            if (value > 0) {
                const intensity = Math.min(value / config.maxVal, 1.0);
                if (intensity > maxIntensity) maxIntensity = intensity;
            }
        }
    }
    return { intensity: maxIntensity };
}

function getHeatmapPoints(gasKey) {
    // FILTRADO VITAL: Selecciona qué datos mostrar según la vista actual
    let dataToUse = (currentView === 'personal') ? localDataGlobal : peninsulaDataGlobal.concat(localDataGlobal);

    if (gasKey === 'MAX_GAS') {
        return dataToUse.map(station => {
            const { intensity } = getMaxGasIntensity(station);
            return intensity > 0 ? [station.lat, station.lng, intensity] : null;
        }).filter(point => point !== null);
    } else {
        const config = GAS_CONFIG[gasKey];
        return dataToUse
            .filter(station => station[config.property] > 0)
            .map(station => {
                const intensity = Math.min(station[config.property] / config.maxVal, 1.0);
                return [station.lat, station.lng, intensity];
            });
    }
}

// ==============================================
// 3. RENDERIZADO Y CONTROL DE VISTA
// ==============================================

function drawHeatmap(gasKey) {
    if (!mapaInstance) return;

    const heatPoints = getHeatmapPoints(gasKey);

    if (heatLayer) {
        heatmapLayerGroup.removeLayer(heatLayer);
    }

    const heatOptions = {
        minOpacity: 0.15,
        maxZoom: (currentView === 'personal') ? 15 : 10,
        radius: (currentView === 'personal') ? 45 : 30, // Puntos más grandes en vista personal
        blur: 30,
        max: 1.0,
        gradient: { 0.0: 'green', 0.25: 'lime', 0.5: 'yellow', 1.0: 'red' }
    };

    heatLayer = L.heatLayer(heatPoints, heatOptions);
    heatmapLayerGroup.addLayer(heatLayer);

    if (legend.getContainer() != null) {
        legend.remove();
    }
    legend.addTo(mapaInstance);
}

/**
 * Función para cambiar la vista con efecto de desaparición de puntos
 */
function switchMapView(viewType) {
    currentView = viewType;

    // 1. DESACTIVAR VISUALIZACIÓN: Limpiamos la capa de calor antes de que empiece el movimiento
    if (heatLayer) {
        heatmapLayerGroup.removeLayer(heatLayer);
    }

    // Opcional: También podemos ocultar la leyenda durante el viaje
    if (legend.getContainer()) {
        legend.remove();
    }

    // 2. EJECUTAR EL VUELO (Transición)
    if (viewType === 'personal') {
        // Enfoque en Gandía
        mapaInstance.flyTo([38.968, -0.186], 13, {
            duration: 1.5 // Duración en segundos
        });
    } else {
        // Enfoque general
        mapaInstance.flyTo([39.228493, -0.529656], 9, {
            duration: 1.5
        });
    }

    // 3. REACTIVAR VISUALIZACIÓN: Escuchamos cuando el mapa termina de moverse
    // Usamos .once para que el evento se dispare solo una vez al terminar el flyTo
    mapaInstance.once('moveend', function() {
        drawHeatmap(currentGas); // Esto vuelve a calcular los puntos y los dibuja
    });
}

legend.onAdd = function () {
    const config = GAS_CONFIG[currentGas];
    var div = L.DomUtil.create('div', 'info legend'),
        thresholds = config.thresholds,
        labels = [];

    div.innerHTML += `<h4>${config.name}</h4>`;
    if (currentGas === 'MAX_GAS') {
        labels.push('<i style="background:' + getColor(thresholds[0] - 0.1, thresholds) + '"></i> Bajo (< 50%)');
        labels.push('<i style="background:' + getColor(thresholds[0] + 0.1, thresholds) + '"></i> Medio (50%-80%)');
        labels.push('<i style="background:' + getColor(thresholds[1] + 0.1, thresholds) + '"></i> Alto (> 80%)');
    } else {
        labels.push('<i style="background:' + getColor(thresholds[0] - 1, thresholds) + '"></i> Bueno');
        labels.push('<i style="background:' + getColor(thresholds[0] + 1, thresholds) + '"></i> Medio');
        labels.push('<i style="background:' + getColor(thresholds[1] + 1, thresholds) + '"></i> Alto');
    }
    div.innerHTML += labels.join('<br>');
    return div;
};

// ==============================================
// 4. INICIALIZACIÓN PRINCIPAL
// ==============================================

function initializeDashboardMap() {
    if (mapaInstance) {
        setTimeout(() => mapaInstance.invalidateSize(), 100);
        return;
    }

    const container = document.getElementById('mapa');
    if (!container) return;

    mapaInstance = L.map('mapa').setView([39.228493, -0.529656], 9);

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        crossOrigin: true
    }).addTo(mapaInstance);

    Promise.all([
        fetch('../data/datos_config_gases.json').then(r => r.json()),
        fetch('../data/datos_sensores.geojson').then(r => r.json())
    ])
        .then(([dataConfig, dataLocal]) => {
            // Guardamos los datos globalmente para poder filtrar sin volver a hacer fetch
            peninsulaDataGlobal = dataConfig.gas_station_data;
            localDataGlobal = dataLocal.features.map(feature => ({
                id_sensor: feature.properties.id_sensor,
                lat: feature.geometry.coordinates[1],
                lng: feature.geometry.coordinates[0],
                valor_NO2: feature.properties.valor_NO2,
                valor_O3: feature.properties.valor_O3,
                valor_CO: feature.properties.valor_CO,
                valor_SO2: feature.properties.valor_SO2,
                valor_PM10: feature.properties.valor_PM10,
            }));

            // Crear controles de "Layouts" (Capas Base de gases)
            var gasLayersControl = {};
            for (const key in GAS_CONFIG) {
                gasLayersControl[GAS_CONFIG[key].name] = L.layerGroup();
            }

            heatmapLayerGroup.addTo(mapaInstance);

            L.control.layers(gasLayersControl, {"Mapa Calor": heatmapLayerGroup}, { collapsed: false }).addTo(mapaInstance);

            // Estado inicial
            currentGas = 'MAX_GAS';
            mapaInstance.addLayer(gasLayersControl[GAS_CONFIG['MAX_GAS'].name]);
            drawHeatmap(currentGas);

            // Evento cambio de gas en el selector de Leaflet
            mapaInstance.on('baselayerchange', function (e) {
                for (const key in GAS_CONFIG) {
                    if (GAS_CONFIG[key].name === e.name) {
                        currentGas = key;
                        drawHeatmap(currentGas);
                        break;
                    }
                }
            });

            setTimeout(() => mapaInstance.invalidateSize(), 300);
        })
        .catch(error => console.error('Error al cargar datos:', error));
}