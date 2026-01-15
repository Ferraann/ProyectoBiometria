// ==========================================
// ARCHIVO: js/map-logic.js
// ==========================================

// --- 0. LISTA MAESTRA DE ESTACIONES ---
const stations = [
    {code: "28-79-99", lat: 40.42389, lng: -3.68222, name: "MADRID-ESCUELAS AGUIRRE"},
    {code: "28-79-4", lat: 40.42417, lng: -3.71222, name: "MADRID-PLAZA ESPAÑA"},
    {code: "28-79-106", lat: 40.41917, lng: -3.69194, name: "MADRID-RETIRO"},
    // ... (El resto de tus estaciones igual) ...
    {code: "41-72-3", lat: 41.72361, lon: -2.85694, name: "DURUELO DE LA SIERRA"}
];

// --- 1. CONFIGURACIÓN VISUAL ---
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

const boundsEspana = [[34.5, -11.0], [44.5, 5.5]];
const map = L.map('map', {
    maxBounds: boundsEspana, maxBoundsViscosity: 1.0, minZoom: 6, maxZoom: 12
}).setView([39.90, -3.70], 6);

L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', { noWrap: true }).addTo(map);

// Variables Globales
let globalPointsData = [];
let multiGasData = {};
let canvasLayer = null;

// --- FUNCIONES MATEMÁTICAS ---
function interpolateColor(value, gas) {
    const conf = (gas === 'MAX') ? maxRiskConfig : config[gas];
    const stops = conf.stops;
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

function updateLegend(gas) {
    const conf = (gas === 'MAX') ? maxRiskConfig : config[gas];
    const legendTitle = document.getElementById('legend-title');
    const legendUnit = document.getElementById('legend-unit');
    const legendBar = document.getElementById('legend-bar');
    const legendValues = document.getElementById('legend-values');

    // Protección extra por si no existen elementos de leyenda
    if(legendTitle) legendTitle.innerText = (gas === 'MAX') ? "Nivel de Riesgo" : gas;
    if(legendUnit) legendUnit.innerText = (gas === 'MAX') ? "% Saturación" : conf.unit;

    const stops = conf.stops;
    let gradientStr = "linear-gradient(to right";
    stops.forEach(s => { gradientStr += `, rgb(${s.c.join(',')})`; });
    gradientStr += ")";

    if(legendBar) legendBar.style.background = gradientStr;
    if(legendValues) legendValues.innerHTML = stops.map(s => `<span>${s.val}</span>`).join('');
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

function findNearestStation(lat, lng) {
    let nearest = null;
    let minDistance = Infinity;
    stations.forEach(st => {
        const d = Math.sqrt(Math.pow(lat - st.lat, 2) + Math.pow(lng - st.lng, 2));
        if (d < minDistance) {
            minDistance = d;
            nearest = { ...st, distanceKm: d * 111 };
        }
    });
    return nearest;
}

function resolveStationName(p) {
    if (p.ubicacion_nombre && p.ubicacion_nombre !== "undefined") return p.ubicacion_nombre;
    if (p.name && p.name !== "undefined") return p.name;
    if (p.NOMBRE && p.NOMBRE !== "undefined") return p.NOMBRE;
    let bestMatch = null;
    let minGeoDist = 0.005;
    for(let s of stations) {
        const d = Math.sqrt(Math.pow(p.lat - s.lat, 2) + Math.pow(p.lon - s.lon, 2));
        if (d < minGeoDist) { minGeoDist = d; bestMatch = s.name; }
    }
    if (bestMatch) return bestMatch;
    return "Estación Desconocida";
}

// --- EVENTO CLIC EN MAPA ---
map.on('click', function(e) {
    const selector = document.getElementById('gasSelect');
    if (!selector) return;

    const selectedMode = selector.value;
    const lat = e.latlng.lat;
    const lng = e.latlng.lng;
    const nearest = findNearestStation(lat, lng);

    let contentHtml = "";
    let mainColor = "#fff";

    if (selectedMode === 'MAX') {
        let maxRisk = -1;
        let dominantGas = "N/A";
        let dominantVal = 0;
        const gases = ['NO2', 'O3', 'PM10', 'SO2', 'CO'];
        gases.forEach(g => {
            if (multiGasData[g] && multiGasData[g].length > 0) {
                const val = calculateIDW(e.latlng, multiGasData[g]);
                if (val !== null) {
                    const risk = getSeverityScore(val, g);
                    if (risk > maxRisk) {
                        maxRisk = risk;
                        dominantGas = g;
                        dominantVal = val;
                    }
                }
            }
        });
        mainColor = interpolateColor(maxRisk, 'MAX').replace('0.9', '1');
        contentHtml = `
            <div style="border-bottom:1px solid #444; padding-bottom:5px; margin-bottom:10px;">
                <strong style="color:#ffae00;">MÁXIMO RIESGO</strong>
            </div>
            <div style="font-size:11px; color:#aaa; text-align:center;">Gas Predominante</div>
            <div style="font-size:32px; font-weight:800; color:${mainColor}; line-height:1; margin-bottom:5px; text-align:center;">
                ${dominantGas}
            </div>
            <div style="text-align:center; font-size:14px; margin-bottom:15px;">
                ${dominantVal.toFixed(1)} ${config[dominantGas] ? config[dominantGas].unit : ''}
            </div>
        `;
    } else {
        if (globalPointsData.length === 0) return;
        const val = calculateIDW(e.latlng, globalPointsData);
        if (val === null) return;
        mainColor = interpolateColor(val, selectedMode).replace('0.9', '1');
        contentHtml = `
            <div style="border-bottom:1px solid #444; padding-bottom:5px; margin-bottom:10px;">
                <strong style="color:#ffae00;">${selectedMode}</strong>
            </div>
            <div style="font-size:32px; font-weight:800; color:${mainColor}; line-height:1; margin-bottom:5px; text-align:center;">
                ${val.toFixed(2)}
            </div>
            <div style="text-align:center; font-size:12px; color:#aaa; margin-bottom:15px;">
                ${config[selectedMode] ? config[selectedMode].unit : ''}
            </div>
        `;
    }

    if (nearest) {
        const finalName = resolveStationName(nearest);
        contentHtml += `
            <div style="font-size:11px; color:#ccc; background:#333; padding:8px; border-radius:6px; text-align:left;">
                <div style="margin-bottom:4px;">
                    <span style="color:#888;">Ref:</span> <strong>${finalName}</strong>
                </div>
                <div style="margin-bottom:4px;">
                    <span style="color:#888;">ID:</span> ${nearest.code}
                </div>
                <div>
                    <span style="color:#888;">Dist:</span> ${nearest.distanceKm.toFixed(2)} km
                </div>
            </div>
        `;
    }

    L.popup().setLatLng(e.latlng)
        .setContent(`<div style="font-family:'Segoe UI',sans-serif; min-width:160px;">${contentHtml}</div>`)
        .openOn(map);
});

// --- RENDERIZADO DEL MAPA ---
function renderMap(mode) {
    if (canvasLayer) map.removeLayer(canvasLayer);

    const HeatGrid = L.GridLayer.extend({
        createTile: function(coords) {
            const tile = L.DomUtil.create('canvas', 'leaflet-tile');
            const size = this.getTileSize();
            tile.width = size.x; tile.height = size.y;
            const ctx = tile.getContext('2d');
            const step = 8; // Pixelación

            for (let x = 0; x < size.x; x += step) {
                for (let y = 0; y < size.y; y += step) {
                    const latlng = map.unproject(coords.scaleBy(size).add([x, y]), coords.z);

                    if (mode === 'MAX') {
                        let maxRisk = -1;
                        let dominantGas = null;
                        ['NO2', 'O3', 'PM10', 'SO2', 'CO'].forEach(g => {
                            if (multiGasData[g] && multiGasData[g].length > 0) {
                                const val = calculateIDW(latlng, multiGasData[g]);
                                if (val !== null) {
                                    const risk = getSeverityScore(val, g);
                                    if (risk > maxRisk) {
                                        maxRisk = risk;
                                        dominantGas = g;
                                    }
                                }
                            }
                        });
                        if (dominantGas) {
                            ctx.fillStyle = interpolateColor(maxRisk, 'MAX');
                            ctx.fillRect(x, y, step, step);
                        }
                    } else {
                        const val = calculateIDW(latlng, globalPointsData);
                        if (val !== null) {
                            ctx.fillStyle = interpolateColor(val, mode);
                            ctx.fillRect(x, y, step, step);
                        }
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
            container.style.filter = "blur(10px) saturate(1.5)";
            container.style.mixBlendMode = "screen";
        }
    }, 100);
}

// --- FUNCIÓN PRINCIPAL DE CARGA (PROTEGIDA) ---
function loadData() {
    const selector = document.getElementById('gasSelect');
    const loader = document.getElementById('loader');

    // !!! PROTECCIÓN CLAVE: Si el selector no existe, no hacemos nada !!!
    if (!selector) return;

    const mode = selector.value;
    if (loader) loader.style.display = 'block';
    updateLegend(mode);

    if (typeof SERVER_DATA === 'undefined') {
        console.error("No hay datos del servidor.");
        if (loader) loader.style.display = 'none';
        return;
    }

    const gases = ['NO2', 'O3', 'PM10', 'SO2', 'CO'];
    gases.forEach(g => {
        const rawData = SERVER_DATA[g] || [];
        multiGasData[g] = rawData.map(p => ({
            ...p,
            lat: parseFloat(p.lat),
            lon: parseFloat(p.lon),
            value: parseFloat(p.value) * config[g].conversion
        }));
    });

    if (mode === 'MAX') {
        renderMap('MAX');
    } else {
        globalPointsData = multiGasData[mode] || [];
        renderMap(mode);
    }
    if (loader) loader.style.display = 'none';
}

// --- INICIALIZACIÓN SEGURA ---
// Esperamos a que el HTML esté listo antes de ejecutar nada
document.addEventListener('DOMContentLoaded', function() {
    // 1. Iniciamos la carga inicial
    loadData();

    // 2. Activamos el listener del selector si existe
    const selectorGas = document.getElementById('gasSelect');
    if (selectorGas) {
        selectorGas.addEventListener('change', function() {
            loadData();
        });
    }
});