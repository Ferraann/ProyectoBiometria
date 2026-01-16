/**
 * @file map-logic-index.js
 * @brief Lógica del mapa para la Landing Page (index.html).
 * @details Conecta con la API para mostrar el riesgo máximo de HOY en tiempo real.
 */

'use strict';

// --- 1. CONFIGURACIÓN VISUAL (Igual que el Dashboard) ---
const config = {
    NO2: {
        unit: "µg/m³", conversion: 1,
        stops: [ { val: 0, c: [0, 100, 150] }, { val: 5, c: [0, 100, 150] }, { val: 15, c: [194, 195, 126] }, { val: 50, c: [222, 117, 53] }, { val: 100, c: [74, 12, 0] } ]
    },
    PM10: {
        unit: "µg/m³", conversion: 1,
        stops: [ { val: 0, c: [1, 101, 150] }, { val: 10, c: [1, 101, 150] }, { val: 25, c: [172, 186, 189] }, { val: 100, c: [212, 151, 79] }, { val: 200, c: [74, 12, 0] } ]
    },
    O3: {
        unit: "µg/m³", conversion: 1,
        stops: [ { val: 0, c: [1, 101, 150] }, { val: 10, c: [1, 101, 150] }, { val: 20, c: [1, 101, 150] }, { val: 100, c: [176, 178, 132] }, { val: 1000, c: [74, 12, 0] } ]
    },
    CO: {
        unit: "ppbv", conversion: 500,
        stops: [ { val: 0, c: [124, 124, 124] }, { val: 50, c: [124, 124, 116] }, { val: 100, c: [124, 113, 60] }, { val: 500, c: [46, 29, 29] }, { val: 1200, c: [130, 26, 25] } ]
    },
    SO2: {
        unit: "mg/m²", conversion: 1,
        stops: [ { val: 0, c: [0, 102, 151] }, { val: 1, c: [50, 135, 175] }, { val: 10, c: [195, 195, 125] }, { val: 25, c: [217, 113, 51] }, { val: 100, c: [74, 12, 0] } ]
    }
};

const maxRiskConfig = {
    unit: "Dominante",
    stops: [
        { val: 0,   c: [0, 100, 150] },
        { val: 25,  c: [0, 220, 220] },
        { val: 50,  c: [194, 195, 126] },
        { val: 75,  c: [222, 117, 53] },
        { val: 100, c: [74, 12, 0] }
    ]
};

const GAS_IDS = { "NO2": 1, "O3": 2, "SO2": 3, "CO": 4, "PM10": 5 };
let multiGasData = {};
let canvasLayer = null;

// --- 2. INICIALIZACIÓN DEL MAPA ---
// Desactivamos zoomControl como pedía el diseño original de la landing
const map = L.map('map', {
    zoomControl: false,
    scrollWheelZoom: false, // Evita hacer zoom sin querer al hacer scroll en la landing
    attributionControl: false
}).setView([40.416, -3.703], 6); // Centrado en España

L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
    opacity: 0.9
}).addTo(map);

// --- 3. FUNCIONES DE CÁLCULO (IDW) ---
function interpolateColor(value) {
    const stops = maxRiskConfig.stops;
    if (value <= stops[0].val) return `rgba(${stops[0].c.join(',')}, 0.75)`;
    if (value >= stops[stops.length-1].val) return `rgba(${stops[stops.length-1].c.join(',')}, 0.75)`;
    for (let i = 0; i < stops.length - 1; i++) {
        if (value >= stops[i].val && value <= stops[i+1].val) {
            const min = stops[i], max = stops[i+1];
            const pct = (value - min.val) / (max.val - min.val);
            const r = Math.round(min.c[0] + (max.c[0] - min.c[0]) * pct);
            const g = Math.round(min.c[1] + (max.c[1] - min.c[1]) * pct);
            const b = Math.round(min.c[2] + (max.c[2] - min.c[2]) * pct);
            return `rgba(${r}, ${g}, ${b}, 0.9)`;
        }
    }
}

function getSeverityScore(val, gas) {
    if (!config[gas]) return 0;
    const maxLimit = config[gas].stops[config[gas].stops.length - 1].val;
    let score = (val / maxLimit) * 100;
    return score > 100 ? 100 : score;
}

function calculateIDW(latlng, points) {
    let num = 0, den = 0;
    for (let p of points) {
        if(p.value < 0) continue;
        const d2 = Math.pow(latlng.lat - p.lat, 2) + Math.pow(latlng.lng - p.lon, 2);
        if (d2 < 0.00001) return p.value;
        const w = 1 / (d2 * d2);
        num += w * p.value;
        den += w;
    }
    return den < 0.0001 ? null : num / den;
}

// --- 4. RENDERIZADO DEL MAPA ---
function renderMaxRiskMap() {
    if (canvasLayer) map.removeLayer(canvasLayer);

    const HeatGrid = L.GridLayer.extend({
        createTile: function(coords) {
            const tile = L.DomUtil.create('canvas', 'leaflet-tile');
            const size = this.getTileSize();
            tile.width = size.x; tile.height = size.y;
            const ctx = tile.getContext('2d');
            const step = 8; // Pixelación suave

            for (let x = 0; x < size.x; x += step) {
                for (let y = 0; y < size.y; y += step) {
                    const latlng = map.unproject(coords.scaleBy(size).add([x, y]), coords.z);

                    // Cálculo de riesgo máximo combinado
                    let maxRisk = -1;
                    let dominantGas = null;

                    ['NO2', 'O3', 'PM10', 'SO2', 'CO'].forEach(g => {
                        if (multiGasData[g] && multiGasData[g].length > 0) {
                            const v = calculateIDW(latlng, multiGasData[g]);
                            if (v !== null) {
                                const risk = getSeverityScore(v, g);
                                if (risk > maxRisk) { maxRisk = risk; dominantGas = g; }
                            }
                        }
                    });

                    if (dominantGas && maxRisk > 5) { // Solo pintar si hay algo de riesgo mínimo
                        ctx.fillStyle = interpolateColor(maxRisk);
                        ctx.fillRect(x, y, step, step);
                    }
                }
            }
            return tile;
        }
    });

    canvasLayer = new HeatGrid({ opacity: 0.8 }).addTo(map);

    // Efecto visual "Neon"
    setTimeout(() => {
        const container = canvasLayer.getContainer();
        if (container) {
            container.style.filter = "blur(8px) saturate(1.4)";
            container.style.mixBlendMode = "screen";
        }
    }, 100);
}

// --- 5. CARGA DE DATOS DESDE LA API ---
async function initLandingMap() {
    const today = new Date().toISOString().split('T')[0]; // Fecha HOY formato YYYY-MM-DD

    // Si quieres probar con el día 16 fijado, descomenta la siguiente línea:
    // const today = '2026-01-16';

    console.log("Cargando mapa landing para fecha:", today);

    const promesas = Object.keys(GAS_IDS).map(async (gasKey) => {
        const id = GAS_IDS[gasKey];
        // Ruta relativa a la API desde index.html
        const url = `api/index.php?accion=getMedicionesXTipo&tipo_id=${id}&fecha=${today}`;

        try {
            const response = await fetch(url);
            if (!response.ok) return { key: gasKey, data: [] };
            const data = await response.json();

            // Convertir y Normalizar datos
            const conversion = config[gasKey].conversion || 1;

            const datosProcesados = data.map(p => ({
                lat: parseFloat(p.lat),
                lon: parseFloat(p.lon),
                value: parseFloat(p.value) * conversion
            }));

            return { key: gasKey, data: datosProcesados };

        } catch (e) {
            console.warn(`Error cargando ${gasKey}`, e);
            return { key: gasKey, data: [] };
        }
    });

    const resultados = await Promise.all(promesas);

    resultados.forEach(item => {
        multiGasData[item.key] = item.data;
    });

    renderMaxRiskMap();
}

// Ejecutar al cargar
document.addEventListener('DOMContentLoaded', initLandingMap);