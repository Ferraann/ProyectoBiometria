/**
 * @file map-logic-index.js
 * @brief Lógica de visualización simplificada para el mapa de calor (Heatmap) de AITHER.
 * @details Este script inicializa un mapa de Leaflet sin controles de zoom manual,
 * fusiona datos de estaciones de la península con sensores locales y calcula
 * una capa de riesgo máximo combinado (MAX_GAS) de forma automática.
 * @author Ferran
 * @date 9/12/2025
 */

'use strict';

/**
 * @section 1. CONFIGURACIÓN DE GASES Y DATOS GLOBALES
 */

/**
 * @brief Configuración técnica de los contaminantes.
 * @details Contiene umbrales, propiedades de datos y valores máximos para la normalización.
 * @constant
 */
const GAS_CONFIG = {
    MAX_GAS: { property: 'MAX_INTENSITY', name: 'Máximo Riesgo (Combinado)', units: 'N/A', thresholds: [0.5, 0.8], maxVal: 1.0 },
    NO2: { property: 'valor_NO2', name: 'Dióxido de Nitrógeno (NO₂)', units: 'µg/m³', thresholds: [20, 40], maxVal: 50 },
    O3: { property: 'valor_O3', name: 'Ozono (O₃)', units: 'µg/m³', thresholds: [100, 120], maxVal: 130 },
    CO: { property: 'valor_CO', name: 'Monóxido de Carbono (CO)', units: 'mg/m³', thresholds: [5, 10], maxVal: 12 },
    PM10: { property: 'valor_PM10', name: 'Partículas <10µm (PM₁₀)', units: 'µg/m³', thresholds: [20, 40], maxVal: 50 },
    SO2: { property: 'valor_SO2', name: 'Dióxido de Azufre (SO₂)', units: 'µg/m³', thresholds: [5, 20], maxVal: 30 }
};

/** @brief Clave del gas actual (fijada en MAX_GAS para esta vista). */
let currentGas = 'MAX_GAS';
/** @brief Almacén unificado de datos (Península + Local). @type {Array<Object>|null} */
let gasData = null;
/** @brief Instancia de la capa de calor de Leaflet. */
let heatLayer = null;
/** @brief Control de leyenda de Leaflet. */
let legend = L.control({position: 'bottomright'});
/** @brief Grupo de capas para gestionar el heatmap de forma aislada. */
let heatmapLayerGroup = L.layerGroup();

/**
 * @section 2. INICIALIZACIÓN DEL MAPA
 */

/**
 * @brief Instancia principal del mapa de Leaflet.
 * @note Se desactiva `zoomControl` para una experiencia puramente visual/estática.
 */
const map = L.map('map', {
    zoomControl: false
}).setView([39.228493, -0.529656], 9);

/** @brief Capa base de OpenStreetMap con compatibilidad para mapas de calor. */
L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors',
    crossOrigin: true
}).addTo(map);

/**
 * @section 3. LÓGICA DEL HEATMAP Y VISUALIZACIÓN
 */

/**
 * @brief Devuelve un color hexadecimal según el nivel de riesgo.
 * @param {number} valor Intensidad calculada.
 * @param {number[]} thresholds Umbrales de riesgo.
 * @returns {string} Código hexadecimal de color.
 */
function getColor(valor, thresholds) {
    if (valor === 0) return '#CCC';
    if (valor > thresholds[1]) return '#FF0000';
    if (valor > thresholds[0]) return '#FFFF00';
    return '#00FF00';
}

/**
 * @brief Calcula el contaminante más dominante en una estación para determinar el riesgo total.
 * @param {Object} station Datos de la estación o sensor.
 * @returns {Object} Objeto con la intensidad (0 a 1) y el nombre del gas dominante.
 */
function getMaxGasIntensity(station) {
    let maxIntensity = 0;
    let maxGas = 'N/A';

    for (const key in GAS_CONFIG) {
        if (key !== 'MAX_GAS' && GAS_CONFIG[key].property) {
            const config = GAS_CONFIG[key];
            const value = station[config.property] || 0;

            if (value > 0) {
                const intensity = Math.min(value / config.maxVal, 1.0);
                if (intensity > maxIntensity) {
                    maxIntensity = intensity;
                    maxGas = config.name;
                }
            }
        }
    }
    return { intensity: maxIntensity, gas: maxGas };
}

/**
 * @brief Genera el array de coordenadas e intensidades para la capa de calor.
 * @param {string} gasKey Identificador del gas en GAS_CONFIG.
 * @returns {Array} Puntos de calor filtrados.
 */
function getHeatmapPoints(gasKey) {
    if (gasKey === 'MAX_GAS') {
        return gasData.map(station => {
            const { intensity } = getMaxGasIntensity(station);
            return intensity > 0 ? [station.lat, station.lng, intensity] : null;
        }).filter(point => point !== null);
    } else {
        return [];
    }
}

/**
 * @brief Dibuja y actualiza la capa de calor y la leyenda en el mapa.
 * @param {string} gasKey Gas seleccionado para la visualización.
 */
function drawHeatmap(gasKey) {
    if (!gasData) return;

    const heatPoints = getHeatmapPoints(gasKey);

    if (heatLayer) {
        heatmapLayerGroup.removeLayer(heatLayer);
    }

    /** @brief Opciones de renderizado del Heatmap (Radio, desenfoque y gradiente). */
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

    // Actualización de la leyenda
    if (legend.getContainer() != null) {
        map.removeControl(legend);
    }
    legend.addTo(map);
}

/**
 * @brief Define la estructura y el contenido visual de la leyenda.
 * @details Muestra los niveles de riesgo (Bajo, Medio, Alto) con sus respectivos colores.
 * @returns {HTMLElement} Div con la leyenda renderizada.
 */
legend.onAdd = function (map) {
    const config = GAS_CONFIG['MAX_GAS'];
    var div = L.DomUtil.create('div', 'info legend'),
        thresholds = config.thresholds,
        labels = [];

    div.innerHTML += `<h4>${config.name} (${config.units})</h4>`;

    labels.push('<i style="background:' + getColor(thresholds[0] - 0.1, thresholds) + '"></i> Bajo Riesgo (< 50%)');
    labels.push('<i style="background:' + getColor(thresholds[0] + 0.1, thresholds) + '"></i> Riesgo Medio (50% - 80%)');
    labels.push('<i style="background:' + getColor(thresholds[1] + 0.1, thresholds) + '"></i> Alto Riesgo (> 80%)');

    div.innerHTML += labels.join('<br>');
    return div;
};

/**
 * @section 4. CARGA DE DATOS Y STARTUP
 */

/**
 * @brief Carga concurrente de archivos JSON y GeoJSON.
 * @details Fusiona los datos de estaciones regionales y locales en un único array unificado.
 */
Promise.all([
    fetch('data/datos_config_gases.json').then(r => r.json()),
    fetch('data/datos_sensores.geojson').then(r => r.json())
])
    .then(([dataConfig, dataLocal]) => {
        let peninsulaData = dataConfig.gas_station_data;

        /** @brief Mapeo de GeoJSON a estructura plana compatible con el motor de cálculo. */
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

        gasData = peninsulaData.concat(localData);

        currentGas = 'MAX_GAS';
        drawHeatmap(currentGas);
        heatmapLayerGroup.addTo(map);

    })
    .catch(error => {
        console.error('Error durante la carga de JSON:', error);
        alert('Error al cargar los datos.');
    });