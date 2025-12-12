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
let gasDataDashoard = null;
let heatLayer = null;
let mapaInstance = null;
let heatmapLayerGroup = L.layerGroup();
let legend = L.control({position: 'bottomright'});

// ==============================================
// 2. LÓGICA DEL HEATMAP Y VISUALIZACIÓN
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
    if (!gasDataDashoard) return [];

    if (gasKey === 'MAX_GAS') {
        return gasDataDashoard.map(station => {
            const { intensity } = getMaxGasIntensity(station);
            return intensity > 0 ? [station.lat, station.lng, intensity] : null;
        }).filter(point => point !== null);
    } else {
        const config = GAS_CONFIG[gasKey];
        const property = config.property;
        const maxVal = config.maxVal;

        return gasDataDashoard
            .filter(station => station[property] > 0)
            .map(station => {
                const intensity = Math.min(station[property] / maxVal, 1.0);
                return [station.lat, station.lng, intensity];
            });
    }
}

function drawHeatmap(gasKey) {
    if (!gasDataDashoard || !mapaInstance) return;

    const heatPoints = getHeatmapPoints(gasKey);

    if (heatLayer) {
        heatmapLayerGroup.removeLayer(heatLayer);
    }

    const heatOptions = {
        minOpacity: 0.15,
        maxZoom: 10,
        radius: 30,
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

// Definición de la leyenda dinámica
legend.onAdd = function () {
    const config = GAS_CONFIG[currentGas];
    var div = L.DomUtil.create('div', 'info legend'),
        thresholds = config.thresholds,
        labels = [];

    div.innerHTML += `<h4>${config.name} (${config.units})</h4>`;

    if (currentGas === 'MAX_GAS') {
        labels.push('<i style="background:' + getColor(thresholds[0] - 0.1, thresholds) + '"></i> Bajo Riesgo (< 50%)');
        labels.push('<i style="background:' + getColor(thresholds[0] + 0.1, thresholds) + '"></i> Riesgo Medio (50% - 80%)');
        labels.push('<i style="background:' + getColor(thresholds[1] + 0.1, thresholds) + '"></i> Alto Riesgo (> 80%)');
    } else {
        labels.push('<i style="background:' + getColor(thresholds[0] - 1, thresholds) + '"></i> 0 &ndash; ' + thresholds[0] + ' (Bueno)');
        labels.push('<i style="background:' + getColor(thresholds[0] + 1, thresholds) + '"></i> ' + (thresholds[0] + 1) + ' &ndash; ' + thresholds[1] + ' (Medio)');
        labels.push('<i style="background:' + getColor(thresholds[1] + 1, thresholds) + '"></i> ' + (thresholds[1] + 1) + '+ (Alto)');
    }

    div.innerHTML += labels.join('<br>');
    return div;
};

// ==============================================
// 3. FUNCIÓN DE INICIALIZACIÓN (Adaptada a IDs)
// ==============================================

function initializeDashboardMap() {
    // Evitar duplicados
    if (mapaInstance) {
        setTimeout(() => mapaInstance.invalidateSize(), 100);
        return;
    }

    // Buscar el contenedor 'mapa' (ID del HTML)
    const container = document.getElementById('mapa');
    if (!container) {
        console.error("Contenedor 'mapa' no encontrado en el DOM.");
        return;
    }

    // Inicialización del mapa (Botones + y - activados por defecto)
    mapaInstance = L.map('mapa').setView([39.228493, -0.529656], 9);

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        crossOrigin: true
    }).addTo(mapaInstance);

    // Carga de datos
    Promise.all([
        fetch('../data/datos_config_gases.json').then(r => r.json()),
        fetch('../data/datos_sensores.geojson').then(r => r.json())
    ])
        .then(([dataConfig, dataLocal]) => {
            let peninsulaData = dataConfig.gas_station_data;
            const localData = dataLocal.features.map(feature => ({
                id_sensor: feature.properties.id_sensor,
                lat: feature.geometry.coordinates[1],
                lng: feature.geometry.coordinates[0],
                valor_NO2: feature.properties.valor_NO2,
                valor_O3: feature.properties.valor_O3,
                valor_CO: feature.properties.valor_CO,
                valor_SO2: feature.properties.valor_SO2,
                valor_PM10: feature.properties.valor_PM10,
            }));

            gasDataDashoard = peninsulaData.concat(localData);

            // Control de Capas (Layouts de Gases)
            var gasLayersControl = {};
            for (const key in GAS_CONFIG) {
                gasLayersControl[GAS_CONFIG[key].name] = L.layerGroup();
            }

            heatmapLayerGroup.addTo(mapaInstance);

            var overlayLayersControl = {
                "Mapa de Calor (Gases)": heatmapLayerGroup
            };

            // Crear el control de capas (collapsed: false para que se vea la tabla)
            L.control.layers(gasLayersControl, overlayLayersControl, { collapsed: false }).addTo(mapaInstance);

            // Iniciar con MAX_GAS
            currentGas = 'MAX_GAS';
            mapaInstance.addLayer(gasLayersControl[GAS_CONFIG['MAX_GAS'].name]);
            drawHeatmap(currentGas);

            // Manejar el cambio de "Layout" de gas
            mapaInstance.on('baselayerchange', function (e) {
                for (const key in GAS_CONFIG) {
                    if (GAS_CONFIG[key].name === e.name) {
                        currentGas = key;
                        drawHeatmap(currentGas);
                        break;
                    }
                }
            });

            setTimeout(() => mapaInstance.invalidateSize(), 200);
        })
        .catch(error => console.error('Error al cargar datos del mapa:', error));
}