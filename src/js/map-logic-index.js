/**
 * @file map-logic-index.js
 * @brief Mapa Landing Page: Límites geográficos (España/Portugal), Zoom y Loader.
 */

'use strict';

// --- 1. CONFIGURACIÓN VISUAL ---
const config = {
    NO2: { conversion: 1, stops: [{val:0,c:[0,100,150]},{val:100,c:[74,12,0]}] },
    PM10: { conversion: 1, stops: [{val:0,c:[1,101,150]},{val:200,c:[74,12,0]}] },
    O3:   { conversion: 1, stops: [{val:0,c:[1,101,150]},{val:1000,c:[74,12,0]}] },
    CO:   { conversion: 500, stops: [{val:0,c:[124,124,124]},{val:1200,c:[130,26,25]}] },
    SO2:  { conversion: 1, stops: [{val:0,c:[0,102,151]},{val:100,c:[74,12,0]}] }
};

const maxRiskConfig = {
    stops: [
        { val: 0,   c: [0, 100, 150] },   // Azul
        { val: 25,  c: [0, 220, 220] },   // Cian
        { val: 50,  c: [194, 195, 126] }, // Amarillo
        { val: 75,  c: [222, 117, 53] },  // Naranja
        { val: 100, c: [74, 12, 0] }      // Rojo
    ]
};

const GAS_IDS = { "NO2": 1, "O3": 2, "SO2": 3, "CO": 4, "PM10": 5 };
let multiGasData = {};
let canvasLayer = null;
let allPointsBounds = [];

// --- 2. CONFIGURACIÓN DEL MAPA (LÍMITES Y REBOTE) ---

// Definimos la caja que incluye España (Península), Portugal, Baleares y Canarias.
// [Lat Sur, Lon Oeste], [Lat Norte, Lon Este]
const boundsLimit = [
    [27.0, -19.0], // Esquina inferior izquierda (Canarias/Oeste Portugal)
    [44.5, 6.0]    // Esquina superior derecha (Pirineos/Menorca)
];

const map = L.map('map', {
    zoomControl: true,
    scrollWheelZoom: true,
    attributionControl: false,
    doubleClickZoom: true,
    dragging: true,
    minZoom: 5,             // No dejar alejarse más de la vista general
    maxZoom: 15,
    maxBounds: boundsLimit, // <--- AQUÍ PONEMOS EL LÍMITE
    maxBoundsViscosity: 1.0 // <--- ESTO ES EL "REBOTE" (1.0 es sólido, no deja salir)
}).setView([40.0, -4.0], 6); // Vista inicial centrada en la península

L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
    opacity: 0.9,
    bounds: boundsLimit // Optimizamos la carga de tiles
}).addTo(map);


// --- 3. FUNCIONES AUXILIARES ---
function interpolateColor(value) {
    const stops = maxRiskConfig.stops;
    if (value <= stops[0].val) return `rgba(${stops[0].c.join(',')}, 0.65)`;
    if (value >= stops[stops.length-1].val) return `rgba(${stops[stops.length-1].c.join(',')}, 0.65)`;
    for (let i = 0; i < stops.length - 1; i++) {
        if (value >= stops[i].val && value <= stops[i+1].val) {
            const min = stops[i], max = stops[i+1];
            const pct = (value - min.val) / (max.val - min.val);
            const r = Math.round(min.c[0] + (max.c[0] - min.c[0]) * pct);
            const g = Math.round(min.c[1] + (max.c[1] - min.c[1]) * pct);
            const b = Math.round(min.c[2] + (max.c[2] - min.c[2]) * pct);
            return `rgba(${r}, ${g}, ${b}, 0.8)`;
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
        if (Math.abs(latlng.lat - p.lat) > 1 || Math.abs(latlng.lng - p.lon) > 1) continue;

        const d2 = Math.pow(latlng.lat - p.lat, 2) + Math.pow(latlng.lng - p.lon, 2);
        if (d2 < 0.00001) return p.value;
        const w = 1 / (d2 * d2);
        num += w * p.value;
        den += w;
    }
    return den < 0.0001 ? null : num / den;
}

// --- 4. RENDERIZADO (OPTIMIZADO) ---
function renderMaxRiskMap() {
    if (canvasLayer) map.removeLayer(canvasLayer);

    const HeatGrid = L.GridLayer.extend({
        createTile: function(coords) {
            const tile = L.DomUtil.create('canvas', 'leaflet-tile');
            const size = this.getTileSize();
            tile.width = size.x; tile.height = size.y;
            const ctx = tile.getContext('2d');
            const step = 8; // Rendimiento rápido

            for (let x = 0; x < size.x; x += step) {
                for (let y = 0; y < size.y; y += step) {
                    const latlng = map.unproject(coords.scaleBy(size).add([x, y]), coords.z);

                    // Solo pintamos si estamos dentro de los límites visuales (ahorro de CPU)
                    if (latlng.lat < boundsLimit[0][0] || latlng.lat > boundsLimit[1][0] ||
                        latlng.lng < boundsLimit[0][1] || latlng.lng > boundsLimit[1][1]) {
                        continue;
                    }

                    let maxRisk = 0;
                    ['NO2', 'O3', 'PM10', 'SO2', 'CO'].forEach(g => {
                        if (multiGasData[g] && multiGasData[g].length > 0) {
                            const v = calculateIDW(latlng, multiGasData[g]);
                            if (v !== null) {
                                const risk = getSeverityScore(v, g);
                                if (risk > maxRisk) { maxRisk = risk; }
                            }
                        }
                    });

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

    setTimeout(() => {
        const container = canvasLayer.getContainer();
        if (container) {
            container.style.filter = "blur(12px) saturate(1.4)";
            container.style.mixBlendMode = "screen";
            container.style.pointerEvents = "none";
        }

        const loader = document.getElementById('loader');
        if (loader) {
            loader.style.transition = "opacity 0.5s ease";
            loader.style.opacity = "0";
            setTimeout(() => { loader.style.display = 'none'; }, 500);
        }

    }, 200);
}

// --- 5. CARGA DE DATOS ---
async function initLandingMap() {
    const loader = document.getElementById('loader');
    if (loader) loader.style.display = 'flex';

    // FECHA FIJA: DÍA 15
    const targetDate = '2026-01-15';
    console.log("Cargando atmósfera del día:", targetDate);

    const promesas = Object.keys(GAS_IDS).map(async (gasKey) => {
        const id = GAS_IDS[gasKey];
        const url = `api/index.php?accion=getMedicionesXTipo&tipo_id=${id}&fecha=${targetDate}`;
        try {
            const response = await fetch(url);
            if (!response.ok) return { key: gasKey, data: [] };
            const data = await response.json();
            const conversion = config[gasKey].conversion || 1;
            const datosProcesados = data.map(p => {
                const lat = parseFloat(p.lat);
                const lon = parseFloat(p.lon);
                allPointsBounds.push([lat, lon]);
                return { lat: lat, lon: lon, value: parseFloat(p.value) * conversion };
            });
            return { key: gasKey, data: datosProcesados };
        } catch (e) {
            return { key: gasKey, data: [] };
        }
    });

    const resultados = await Promise.all(promesas);

    resultados.forEach(item => {
        multiGasData[item.key] = item.data;
    });

    // Auto-zoom suave si hay datos
    if (allPointsBounds.length > 0) {
        const bounds = L.latLngBounds(allPointsBounds);
        // Nos aseguramos que el auto-zoom no se salga de los límites permitidos
        if (boundsLimit) {
            const safeBounds = bounds.extend(L.latLng(boundsLimit[0])).extend(L.latLng(boundsLimit[1]));
            // Centrado preferente en los datos, pero sin romper nada
            map.fitBounds(bounds, { padding: [50, 50], maxZoom: 10 });
        }
    }

    renderMaxRiskMap();
}

document.addEventListener('DOMContentLoaded', initLandingMap);