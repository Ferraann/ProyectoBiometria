/**
 * @file map-logic-index.js
 * @brief Mapa Visual de Riesgo para Landing Page (Sin Popups, Solo Visual)
 */

'use strict';

// --- 1. CONFIGURACIÓN VISUAL (Umbrales de contaminación) ---
const config = {
    NO2: { conversion: 1, stops: [{val:0,c:[0,100,150]},{val:100,c:[74,12,0]}] }, // Simplificado para landing
    PM10: { conversion: 1, stops: [{val:0,c:[1,101,150]},{val:200,c:[74,12,0]}] },
    O3:   { conversion: 1, stops: [{val:0,c:[1,101,150]},{val:1000,c:[74,12,0]}] },
    CO:   { conversion: 500, stops: [{val:0,c:[124,124,124]},{val:1200,c:[130,26,25]}] },
    SO2:  { conversion: 1, stops: [{val:0,c:[0,102,151]},{val:100,c:[74,12,0]}] }
};

// Configuración de Colores para el Riesgo Combinado (Azul -> Verde -> Amarillo -> Rojo)
const maxRiskConfig = {
    stops: [
        { val: 0,   c: [0, 100, 150] },   // Azul (Limpio)
        { val: 25,  c: [0, 220, 220] },   // Cian
        { val: 50,  c: [194, 195, 126] }, // Amarillo verdoso
        { val: 75,  c: [222, 117, 53] },  // Naranja
        { val: 100, c: [74, 12, 0] }      // Rojo Sangre (Peligro)
    ]
};

const GAS_IDS = { "NO2": 1, "O3": 2, "SO2": 3, "CO": 4, "PM10": 5 };
let multiGasData = {};
let canvasLayer = null;

// --- 2. INICIALIZACIÓN DEL MAPA ---
// zoomControl: false -> Quita los botones + / -
// scrollWheelZoom: false -> Evita que la web haga zoom al bajar con el ratón
// dragging: false -> (Opcional) Si quieres que el mapa sea 100% estático como una imagen
const map = L.map('map', {
    zoomControl: false,
    scrollWheelZoom: false,
    attributionControl: false,
    doubleClickZoom: false,
    boxZoom: false,
    keyboard: false,
    dragging: true // Lo dejamos en true para que puedan moverse si quieren
}).setView([40.416, -3.703], 6); // Centrado en España

L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
    opacity: 0.9
}).addTo(map);

// --- 3. FUNCIONES MATEMÁTICAS ---
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

// Normaliza el valor de 0 a 100 según peligrosidad
function getSeverityScore(val, gas) {
    if (!config[gas]) return 0;
    const maxLimit = config[gas].stops[config[gas].stops.length - 1].val;
    let score = (val / maxLimit) * 100;
    return score > 100 ? 100 : score;
}

// Interpolación IDW (Distancia Inversa)
function calculateIDW(latlng, points) {
    let num = 0, den = 0;
    for (let p of points) {
        if(p.value < 0) continue;
        const d2 = Math.pow(latlng.lat - p.lat, 2) + Math.pow(latlng.lng - p.lon, 2);
        if (d2 < 0.00001) return p.value;
        const w = 1 / (d2 * d2); // Peso cuadrático
        num += w * p.value;
        den += w;
    }
    return den < 0.0001 ? null : num / den;
}

// --- 4. RENDERIZADO (Canvas) ---
function renderMaxRiskMap() {
    if (canvasLayer) map.removeLayer(canvasLayer);

    const HeatGrid = L.GridLayer.extend({
        createTile: function(coords) {
            const tile = L.DomUtil.create('canvas', 'leaflet-tile');
            const size = this.getTileSize();
            tile.width = size.x; tile.height = size.y;
            const ctx = tile.getContext('2d');
            const step = 4; // CALIDAD: 4 es alta calidad (píxeles pequeños), 8 es más rápido

            for (let x = 0; x < size.x; x += step) {
                for (let y = 0; y < size.y; y += step) {
                    const latlng = map.unproject(coords.scaleBy(size).add([x, y]), coords.z);

                    let maxRisk = 0;

                    // Comprobamos todos los gases disponibles para ver cual es peor en este punto
                    ['NO2', 'O3', 'PM10', 'SO2', 'CO'].forEach(g => {
                        if (multiGasData[g] && multiGasData[g].length > 0) {
                            const v = calculateIDW(latlng, multiGasData[g]);
                            if (v !== null) {
                                const risk = getSeverityScore(v, g);
                                if (risk > maxRisk) { maxRisk = risk; }
                            }
                        }
                    });

                    // Solo pintamos si hay riesgo visible
                    if (maxRisk > 1) {
                        ctx.fillStyle = interpolateColor(maxRisk);
                        ctx.fillRect(x, y, step, step);
                    }
                }
            }
            return tile;
        }
    });

    canvasLayer = new HeatGrid({ opacity: 0.8 }).addTo(map);

    // Filtros CSS para efecto visual potente (Blur + Saturación)
    setTimeout(() => {
        const container = canvasLayer.getContainer();
        if (container) {
            container.style.filter = "blur(10px) saturate(1.5)";
            container.style.mixBlendMode = "screen";
            container.style.pointerEvents = "none"; // IMPORTANTE: Evita que el canvas intercepte clics
        }
    }, 100);
}

// --- 5. CARGA DE DATOS ---
async function initLandingMap() {
    // 1. FECHA: Usamos HOY automáticamente
    const today = new Date().toISOString().split('T')[0];

    // NOTA: Si quieres forzar para ver tus datos del día 16, descomenta esto:
    // const today = '2026-01-16';

    const promesas = Object.keys(GAS_IDS).map(async (gasKey) => {
        const id = GAS_IDS[gasKey];
        // OJO: La ruta api/... asume que index.html está en la raíz y api en /api
        const url = `api/index.php?accion=getMedicionesXTipo&tipo_id=${id}&fecha=${today}`;

        try {
            const response = await fetch(url);
            if (!response.ok) return { key: gasKey, data: [] };
            const data = await response.json();

            // Conversión (Ej: CO x 500)
            const conversion = config[gasKey].conversion || 1;

            const datosProcesados = data.map(p => ({
                lat: parseFloat(p.lat),
                lon: parseFloat(p.lon),
                value: parseFloat(p.value) * conversion
            }));

            return { key: gasKey, data: datosProcesados };

        } catch (e) {
            return { key: gasKey, data: [] };
        }
    });

    const resultados = await Promise.all(promesas);

    resultados.forEach(item => {
        multiGasData[item.key] = item.data;
    });

    // Pintamos el mapa
    renderMaxRiskMap();
}

// Ejecutar al cargar la página
document.addEventListener('DOMContentLoaded', initLandingMap);