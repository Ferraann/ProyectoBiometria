'use strict';

// ==============================================
// 1. CONFIGURACIÓN DE GASES Y DATOS GLOBALES
// ==============================================

/**
 * @file map-logic.js
 * @brief Script para la visualización de datos de calidad del aire mediante con Leaflet.
 * @author Ferran Sansaloni Prats
 */

/**
 * @typedef {Object} GasConfigItem
 * @property {string} property Nombre de la propiedad en el objeto de datos de la estación.
 * @property {string} name Nombre descriptivo del gas o indicador.
 * @property {string} units Unidades de medida.
 * @property {number[]} thresholds Umbrales de riesgo [Bajo, Alto] utilizados para determinar el color.
 * @property {number} maxVal Valor máximo esperado para normalizar la intensidad del mapa de calor.
 */

/**
 * @brief Objeto de configuración global para los gases y el indicador de riesgo máximo combinado.
 * @constant
 * @type {Object.<string, GasConfigItem>}
 */
const GAS_CONFIG = {
    /** @brief Indicador de Máximo Riesgo Combinado. */
    MAX_GAS: { property: 'MAX_INTENSITY', name: 'Máximo Riesgo (Combinado)', units: 'N/A', thresholds: [0.5, 0.8], maxVal: 1.0 },
    /** @brief Dióxido de Nitrógeno (NO₂). */
    NO2: { property: 'valor_NO2', name: 'Dióxido de Nitrógeno (NO₂)', units: 'µg/m³', thresholds: [20, 40], maxVal: 50 },
    /** @brief Ozono (O₃). */
    O3: { property: 'valor_O3', name: 'Ozono (O₃)', units: 'µg/m³', thresholds: [100, 120], maxVal: 130 },
    /** @brief Monóxido de Carbono (CO). */
    CO: { property: 'valor_CO', name: 'Monóxido de Carbono (CO)', units: 'mg/m³', thresholds: [5, 10], maxVal: 12 },
    /** @brief Partículas <10µm (PM₁₀). */
    PM10: { property: 'valor_PM10', name: 'Partículas <10µm (PM₁₀)', units: 'µg/m³', thresholds: [20, 40], maxVal: 50 },
    /** @brief Dióxido de Azufre (SO₂). */
    SO2: { property: 'valor_SO2', name: 'Dióxido de Azufre (SO₂)', units: 'µg/m³', thresholds: [5, 20], maxVal: 30 }
};

/** @brief Clave del gas actualmente seleccionado para la visualización. */
let currentGas = 'MAX_GAS';
/** @brief Vista actual del mapa ('general' para Península + Local, 'personal' solo para Local). */
let currentView = 'general'; // 'general' o 'personal'
/** @brief Array global de datos de estaciones de la península cargados desde un JSON. */
let peninsulaDataGlobal = [];
/** @brief Array global de datos de sensores locales cargados desde un GeoJSON. */
let localDataGlobal = [];

/** @brief Instancia del mapa de Leaflet. */
let mapaInstance = null;
/** @brief Grupo de capas de Leaflet para gestionar el mapa de calor. */
let heatmapLayerGroup = L.layerGroup();
/** @brief Instancia de la capa de calor (HeatLayer). */
let heatLayer = null;
/** @brief Instancia del control de leyenda de Leaflet. */
let legend = L.control({position: 'bottomright'});

// ==============================================
// 2. LÓGICA DE CÁLCULO Y FILTRADO
// ==============================================

/**
 * @brief Determina un color basado en un valor y unos umbrales predefinidos.
 *
 * Se utiliza para definir el color en la leyenda.
 *
 * @param {number} valor El valor a evaluar.
 * @param {number[]} thresholds Array de umbrales [umbral_bajo, umbral_alto].
 * @returns {string} Código de color (HEX) correspondiente al nivel de riesgo.
 */
function getColor(valor, thresholds) {
    if (valor === 0) return '#CCC';
    if (valor > thresholds[1]) return '#FF0000'; // Alto
    if (valor > thresholds[0]) return '#FFFF00'; // Medio
    return '#00FF00'; // Bajo
}

/**
 * @brief Calcula la intensidad máxima de riesgo entre todos los gases para una estación dada.
 *
 * La intensidad se calcula como el valor normalizado del gas (valor / maxVal) y se toma el máximo.
 *
 * @param {Object} station Objeto que contiene los valores de los gases para una estación.
 * @returns {{intensity: number}} Objeto con la intensidad máxima calculada (entre 0.0 y 1.0).
 */
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

/**
 * @brief Genera el array de puntos para el mapa de calor de Leaflet.
 *
 * Cada punto tiene el formato [latitud, longitud, intensidad]. La intensidad se normaliza a 0.0 - 1.0.
 *
 * @param {string} gasKey Clave del gas a mostrar (ej. 'NO2', 'MAX_GAS').
 * @returns {Array.<number[]>} Array de puntos del mapa de calor.
 */
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

/**
 * @brief Dibuja o actualiza el mapa de calor en el mapa de Leaflet.
 *
 * @param {string} gasKey Clave del gas a dibujar.
 * @returns {void}
 */
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

    // Actualiza la leyenda
    if (legend.getContainer() != null) {
        legend.remove();
    }
    legend.addTo(mapaInstance);
}

/**
 * @brief Función para cambiar la vista del mapa con efecto de transición (flyTo).
 *
 * Al cambiar de vista, la capa de calor se oculta durante la transición y se vuelve a dibujar al finalizar.
 *
 * @param {string} viewType El tipo de vista a cambiar ('general' o 'personal').
 * @returns {void}
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

/**
 * @brief Implementación del método onAdd para el control de leyenda de Leaflet.
 *
 * Define el contenido HTML de la leyenda basado en el gas actualmente seleccionado.
 *
 * @returns {HTMLElement} El contenedor DOM de la leyenda.
 */
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

/**
 * @brief Inicializa el mapa de Leaflet y carga los datos de calidad del aire.
 *
 * Si el mapa ya está inicializado, solo invalida el tamaño para asegurar la correcta visualización.
 * Carga datos de la península y datos locales, y configura el control de capas para los gases.
 *
 * @returns {void}
 */
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
                    // NOTA: No es necesario llamar a drawHeatmap desde aquí
                    // si se asume que al cambiar de base layer (gas) solo se quiere
                    // actualizar el mapa de calor, que es una capa de superposición
                    // que siempre se muestra si la opción "Mapa Calor" está activa.
                }
            });

            setTimeout(() => mapaInstance.invalidateSize(), 300);
        })
        .catch(error => console.error('Error al cargar datos:', error));
}