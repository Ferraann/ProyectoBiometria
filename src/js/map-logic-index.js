// Contenido para js/map-logic.js

'use strict';

// ==============================================
// 1. CONFIGURACIÓN DE GASES Y DATOS GLOBALES
// ==============================================

const GAS_CONFIG = {
    // Mantenemos los gases individuales internamente para el cálculo de MAX_GAS
    MAX_GAS: { property: 'MAX_INTENSITY', name: 'Máximo Riesgo (Combinado)', units: 'N/A', thresholds: [0.5, 0.8], maxVal: 1.0 },
    NO2: { property: 'valor_NO2', name: 'Dióxido de Nitrógeno (NO₂)', units: 'µg/m³', thresholds: [20, 40], maxVal: 50 },
    O3: { property: 'valor_O3', name: 'Ozono (O₃)', units: 'µg/m³', thresholds: [100, 120], maxVal: 130 },
    CO: { property: 'valor_CO', name: 'Monóxido de Carbono (CO)', units: 'mg/m³', thresholds: [5, 10], maxVal: 12 },
    PM10: { property: 'valor_PM10', name: 'Partículas <10µm (PM₁₀)', units: 'µg/m³', thresholds: [20, 40], maxVal: 50 },
    SO2: { property: 'valor_SO2', name: 'Dióxido de Azufre (SO₂)', units: 'µg/m³', thresholds: [5, 20], maxVal: 30 }
};

let currentGas = 'MAX_GAS'; // Fijo en MAX_GAS
let gasData = null;
let heatLayer = null;
let legend = L.control({position: 'bottomright'});
let heatmapLayerGroup = L.layerGroup();

// ==============================================
// 2. INICIALIZACIÓN DEL MAPA
// ==============================================

// INICIALIZACIÓN SIN BOTONES DE ZOOM: Se usa zoomControl: false
const map = L.map('map', {
    zoomControl: false // <--- ELIMINA LOS BOTONES + y -
}).setView([39.228493, -0.529656], 9);

// Aseguramos crossOrigin: true para la compatibilidad con Heatmap
L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors',
    crossOrigin: true
}).addTo(map);


// ==============================================
// 3. LÓGICA DEL HEATMAP Y VISUALIZACIÓN
// ==============================================

function getColor(valor, thresholds) {
    if (valor === 0) return '#CCC';
    if (valor > thresholds[1]) return '#FF0000';
    if (valor > thresholds[0]) return '#FFFF00';
    return '#00FF00';
}

/**
 * Función clave para calcular el contaminante más dominante en una estación.
 * Se mantiene para calcular la intensidad de la capa MAX_GAS.
 */
function getMaxGasIntensity(station) {
    let maxIntensity = 0;
    let maxGas = 'N/A';

    // Iteramos sobre todos los gases individuales definidos en GAS_CONFIG
    for (const key in GAS_CONFIG) {
        if (key !== 'MAX_GAS' && GAS_CONFIG[key].property) {
            const config = GAS_CONFIG[key];
            const property = config.property;
            const maxVal = config.maxVal;

            const value = station[property] || 0;

            if (value > 0) {
                const intensity = Math.min(value / maxVal, 1.0);

                if (intensity > maxIntensity) {
                    maxIntensity = intensity;
                    maxGas = config.name;
                }
            }
        }
    }
    return { intensity: maxIntensity, gas: maxGas };
}


function getHeatmapPoints(gasKey) {
    // Como solo estamos interesados en MAX_GAS, simplificamos la condición.
    if (gasKey === 'MAX_GAS') {
        return gasData.map(station => {
            const { intensity } = getMaxGasIntensity(station);

            if (intensity > 0) {
                return [station.lat, station.lng, intensity];
            }
            return null;
        }).filter(point => point !== null);
    } else {
        return [];
    }
}


function drawHeatmap(gasKey) {
    if (!gasData) return;

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
        map.removeControl(legend);
    }
    legend.addTo(map);
}


// Definición de la leyenda (Fija en MAX_GAS)
legend.onAdd = function (map) {
    // Apuntamos directamente a la configuración MAX_GAS
    const config = GAS_CONFIG['MAX_GAS'];
    var div = L.DomUtil.create('div', 'info legend'),
        thresholds = config.thresholds,
        labels = [];

    div.innerHTML += `<h4>${config.name} (${config.units})</h4>`;

    // Leyenda fija para Riesgo Máximo
    labels.push('<i style="background:' + getColor(thresholds[0] - 0.1, thresholds) + '"></i> Bajo Riesgo (< 50%)');
    labels.push('<i style="background:' + getColor(thresholds[0] + 0.1, thresholds) + '"></i> Riesgo Medio (50% - 80%)');
    labels.push('<i style="background:' + getColor(thresholds[1] + 0.1, thresholds) + '"></i> Alto Riesgo (> 80%)');

    div.innerHTML += labels.join('<br>');
    return div;
};

// ==============================================
// 4. CARGA DE DATOS Y STARTUP
// ==============================================

// Cargar JSON de configuración y GeoJSON de sensores locales
Promise.all([
    fetch('data/datos_config_gases.json').then(r => r.json()),
    fetch('data/datos_sensores.geojson').then(r => r.json())
])
    .then(([dataConfig, dataLocal]) => {

        let peninsulaData = dataConfig.gas_station_data;

        // Conversión de datos locales (GeoJSON) a formato plano para fusionar
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

        // ALMACENAR LA LISTA FINAL DE ESTACIONES
        gasData = peninsulaData.concat(localData);

        // 1. Dibuja la capa inicial (MAX_GAS)
        currentGas = 'MAX_GAS';
        drawHeatmap(currentGas);

        // 2. CONTROL DE CAPAS: ELIMINADO
        heatmapLayerGroup.addTo(map);

        // 3. Manejar el cambio de gas (Evento): ELIMINADO

    })
    .catch(error => {
        console.error('Error durante la carga de JSON:', error);
        alert('Error al cargar los datos. Verifique la consola (F12).');
    });