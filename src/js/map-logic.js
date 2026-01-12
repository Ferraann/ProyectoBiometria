// --- 0. LISTA MAESTRA DE ESTACIONES ---
const stations = [
    {code: "28-79-99", lat: 40.42389, lng: -3.68222, name: "MADRID-ESCUELAS AGUIRRE"},
    {code: "28-79-4", lat: 40.42417, lng: -3.71222, name: "MADRID-PLAZA ESPAÑA"},
    {code: "28-79-106", lat: 40.41917, lng: -3.69194, name: "MADRID-RETIRO"},
    {code: "8-19-1", lat: 41.38528, lng: 2.15389, name: "BARCELONA-EIXAMPLE"},
    {code: "8-19-2", lat: 41.39861, lng: 2.15333, name: "BARCELONA-GRACIA"},
    {code: "46-250-1", lat: 39.45861, lng: -0.37667, name: "VALENCIA-PISTA SILLA"},
    {code: "41-91-3", lat: 37.39972, lng: -6.00278, name: "SEVILLA-TORNEO"},
    {code: "48-13-1", lat: 43.26333, lng: -2.94833, name: "BILBAO-Mª DIAZ DE HARO"},
    {code: "1-22-1", lat: 42.51833, lng: -2.61944, name: "EL CIEGO"},
    {code: "1-36-4", lat: 43.14407, lng: -2.96337, name: "LLODIO"},
    {code: "1-51-1", lat: 42.849, lng: -2.3937, name: "AGURAIN"},
    {code: "1-55-1", lat: 42.8752, lng: -3.2317, name: "VALDEREJO"},
    {code: "1-59-8", lat: 42.8548, lng: -2.6807, name: "AVENIDA GASTEIZ"},
    {code: "2-3-2", lat: 38.9808, lng: -1.8452, name: "ALBACETE-PARQUE TECNOLOGICO"},
    {code: "3-14-6", lat: 38.43606, lng: -0.56772, name: "ALACANT-EL PLÁ"},
    {code: "4-13-5", lat: 36.85222, lng: -2.43583, name: "ALMERIA - MEDITERRANEO"},
    {code: "5-19-1", lat: 40.662, lng: -4.693, name: "AVILA"},
    {code: "6-15-1", lat: 38.877, lng: -6.975, name: "BADAJOZ"},
    {code: "9-59-2", lat: 42.345, lng: -3.693, name: "BURGOS 1"},
    {code: "10-52-1", lat: 39.805, lng: -6.398, name: "CAÑAVERAL"},
    {code: "11-12-3", lat: 36.529, lng: -6.291, name: "CADIZ"},
    {code: "12-40-10", lat: 39.993, lng: -0.046, name: "CASTELLÓ - PENYETA"},
    {code: "13-32-1", lat: 38.986, lng: -3.931, name: "CIUDAD REAL"},
    {code: "14-21-4", lat: 37.892, lng: -4.773, name: "CORDOBA - ASOMADILLA"},
    {code: "15-30-1", lat: 43.377, lng: -8.406, name: "A CORUÑA - TORRE HERCULES"},
    {code: "16-78-1", lat: 40.068, lng: -2.132, name: "CUENCA"},
    {code: "17-79-1", lat: 41.979, lng: 2.822, name: "GIRONA"},
    {code: "18-87-1", lat: 37.202, lng: -3.607, name: "GRANADA - NORTE"},
    {code: "19-13-1", lat: 40.632, lng: -3.167, name: "GUADALAJARA"},
    {code: "20-69-1", lat: 43.307, lng: -1.976, name: "DONOSTIA"},
    {code: "21-41-1", lat: 37.268, lng: -6.941, name: "HUELVA - POZO DULCE"},
    {code: "22-125-4", lat: 42.138, lng: -0.408, name: "HUESCA"},
    {code: "23-50-2", lat: 37.773, lng: -3.785, name: "JAEN"},
    {code: "24-89-3", lat: 42.607, lng: -5.575, name: "LEON 1"},
    {code: "25-120-1", lat: 41.616, lng: 0.621, name: "LLEIDA"},
    {code: "26-89-1", lat: 42.463, lng: -2.433, name: "LOGROÑO"},
    {code: "27-28-1", lat: 43.013, lng: -7.558, name: "LUGO"},
    {code: "29-67-1", lat: 36.728, lng: -4.464, name: "MALAGA - EL ATABAL"},
    {code: "30-30-1", lat: 37.973, lng: -1.164, name: "MURCIA-ALCANTARILLA"},
    {code: "31-201-3", lat: 42.813, lng: -1.637, name: "PAMPLONA-ROT.DE"},
    {code: "32-54-1", lat: 42.333, lng: -7.864, name: "OURENSE"},
    {code: "33-44-3", lat: 43.366, lng: -5.861, name: "OVIEDO"},
    {code: "34-120-2", lat: 42.008, lng: -4.534, name: "PALENCIA 2"},
    {code: "35-16-5", lat: 28.113, lng: -15.424, name: "LAS PALMAS-MERCADO"},
    {code: "36-57-1", lat: 42.221, lng: -8.749, name: "VIGO"},
    {code: "37-274-1", lat: 40.971, lng: -5.659, name: "SALAMANCA 1"},
    {code: "38-13-1", lat: 28.463, lng: -16.273, name: "S.CRUZ TENERIFE-GLADIOLOS"},
    {code: "39-75-1", lat: 43.463, lng: -3.821, name: "SANTANDER"},
    {code: "40-194-2", lat: 40.941, lng: -4.106, name: "SEGOVIA 2"},
    {code: "42-173-1", lat: 41.764, lng: -2.463, name: "SORIA"},
    {code: "43-148-4", lat: 41.121, lng: 1.252, name: "TARRAGONA - UNIVERSITAT"},
    {code: "44-216-1", lat: 40.339, lng: -1.107, name: "TERUEL"},
    {code: "45-168-1", lat: 39.866, lng: -4.022, name: "TOLEDO"},
    {code: "47-186-1", lat: 41.666, lng: -4.721, name: "VALLADOLID 1"},
    {code: "49-275-1", lat: 41.506, lng: -5.744, name: "ZAMORA 1"},
    {code: "50-297-36", lat: 41.635, lng: -0.893, name: "ZARAGOZA-RENOVALES"},
    {code: "51-1-1", lat: 35.889, lng: -5.317, name: "CEUTA"},
    {code: "52-1-1", lat: 35.291, lng: -2.946, name: "MELILLA"},
    {code: "41-72-3", lat: 41.72361, lon: -2.85694, name: "DURUELO DE LA SIERRA"}
];

// --- 1. CONFIGURACIÓN VISUAL ---
// Colores para gases individuales
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

// --- CONFIGURACIÓN PARA MODO MÁXIMO RIESGO (PALETA UNIFICADA) ---
// Esta es la paleta que se usará para TODOS los gases en modo MAX.
// Escala del 0% al 100% de peligrosidad relativa.
const maxRiskConfig = {
    unit: "Dominante",
    stops: [
        { val: 0,   c: [0, 100, 150] },    // Azul (Limpio)
        { val: 25,  c: [0, 220, 220] },    // Cian (Bajo)
        { val: 50,  c: [194, 195, 126] },  // Amarillo (Medio)
        { val: 75,  c: [222, 117, 53] },   // Naranja (Alto)
        { val: 100, c: [74, 12, 0] }       // Rojo/Marrón (Extremo)
    ]
};

const boundsEspana = [[34.5, -11.0], [44.5, 5.5]];
const map = L.map('map', {
    maxBounds: boundsEspana, maxBoundsViscosity: 1.0, minZoom: 6, maxZoom: 12
}).setView([39.90, -3.70], 6);

L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', { noWrap: true }).addTo(map);

// Variables Globales
let globalPointsData = []; // Para modo normal
let multiGasData = {};     // Para modo MAX { NO2: [], PM10: [], ... }

// --- FUNCIONES MATEMÁTICAS ---

// Interpolar Color Genérico
function interpolateColor(value, gas) {
    // Seleccionamos configuración según si es un Gas específico o el modo MAX
    const conf = (gas === 'MAX') ? maxRiskConfig : config[gas];
    const stops = conf.stops;

    // Normalización: si es MAX, value es %, si no, es valor real
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

// Calcula severidad (0 a 100) de un valor para un gas dado
function getSeverityScore(val, gas) {
    if (!config[gas]) return 0;
    const maxLimit = config[gas].stops[config[gas].stops.length - 1].val;
    let score = (val / maxLimit) * 100;
    return score > 100 ? 100 : score;
}

function updateLegend(gas) {
    const conf = (gas === 'MAX') ? maxRiskConfig : config[gas];
    document.getElementById('legend-title').innerText = (gas === 'MAX') ? "Nivel de Riesgo" : gas;
    document.getElementById('legend-unit').innerText = (gas === 'MAX') ? "% Saturación" : conf.unit;

    const stops = conf.stops;
    let gradientStr = "linear-gradient(to right";
    stops.forEach(s => { gradientStr += `, rgb(${s.c.join(',')})`; });
    gradientStr += ")";
    document.getElementById('legend-bar').style.background = gradientStr;
    document.getElementById('legend-values').innerHTML = stops.map(s => `<span>${s.val}</span>`).join('');
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
    // Búsqueda geográfica inversa
    let bestMatch = null;
    let minGeoDist = 0.005;
    for(let s of stations) {
        const d = Math.sqrt(Math.pow(p.lat - s.lat, 2) + Math.pow(p.lon - s.lon, 2));
        if (d < minGeoDist) { minGeoDist = d; bestMatch = s.name; }
    }
    if (bestMatch) return bestMatch;
    return "Estación Desconocida";
}

// --- EVENTO CLIC (LÓGICA DOBLE: NORMAL vs MAX) ---
map.on('click', function(e) {
    const selectedMode = document.getElementById('gasSelect').value;
    const lat = e.latlng.lat;
    const lng = e.latlng.lng;
    const nearest = findNearestStation(lat, lng);

    let contentHtml = "";
    let mainColor = "#fff";

    if (selectedMode === 'MAX') {
        // Lógica Combinada
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

        // ⚠️ CAMBIO CLAVE: Usamos la paleta UNIFICADA 'MAX' para el color del número
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
                    ${dominantVal.toFixed(1)} ${config[dominantGas].unit}
                </div>
            `;

    } else {
        // Lógica Normal
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
                    ${config[selectedMode].unit}
                </div>
            `;
    }

    // Bloque común de estación
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

// --- CARGA DE DATOS ---
let canvasLayer = null;

async function loadData(isBackground = false) {
    const mode = document.getElementById('gasSelect').value;
    const loader = document.getElementById('loader');

    if (!isBackground) {
        loader.style.display = 'block';
        updateLegend(mode);
    }

    try {
        const time = new Date().getTime();

        if (mode === 'MAX') {
            // MODO MAX: Cargar TODO
            const gases = ['NO2', 'O3', 'PM10', 'SO2', 'CO'];
            const promises = gases.map(g =>
                fetch(`./src/php/api_datos.php?gas=${g}&_=${time}`).then(r => r.json()).catch(() => [])
            );

            const results = await Promise.all(promises);

            // Guardar en objeto multiGasData
            multiGasData = {};
            gases.forEach((g, index) => {
                multiGasData[g] = results[index].map(p => ({
                    ...p, lat: parseFloat(p.lat), lon: parseFloat(p.lon),
                    value: p.value * config[g].conversion
                }));
            });

            // Si no hay datos, simular (BORRAR EN PRODUCCION)
            if (results[0].length === 0) {
                gases.forEach(g => {
                    const max = config[g].stops[4].val;
                    multiGasData[g] = [
                        {lat: 40.42, lon: -3.68, value: max * Math.random()},
                        {lat: 41.38, lon: 2.15, value: max * Math.random()}
                    ];
                });
            }

            renderMap('MAX');

        } else {
            // MODO NORMAL: Cargar solo 1

            // CORRECCIÓN: Si no tienes el archivo consultar_openaq.php todavía,
            // es mejor dejar solo la petición a tu base de datos (res1).
            const [res1] = await Promise.all([
                fetch(`../php/api_datos.php?gas=${mode}&_=${time}`)
                    .then(r => r.json())
                    .catch(err => { console.error("Error cargando API:", err); return []; })
            ]);

            // Validamos que res1 sea un array, si no, usamos un array vacío
            const datosSeguros = Array.isArray(res1) ? res1 : [];

            // Mapeamos los datos
            let points = datosSeguros.map(p => ({
                ...p,
                lat: parseFloat(p.lat),
                lon: parseFloat(p.lon),
                value: p.value * (config[mode]?.conversion || 1) // Protección si config[mode] falla
            }));

            // Simulación Fallback (Si no hay datos, ponemos un punto en Madrid para que no falle el mapa)
            if (points.length === 0) {
                console.warn("No hay datos reales, usando simulación");
                const max = config[mode] ? config[mode].stops[4].val : 100;
                points = [{lat: 40.424, lon: -3.682, value: max * 0.5}];
            }

            globalPointsData = points;
            renderMap(mode);
        }

        if (!isBackground) loader.style.display = 'none';

    } catch (error) {
        console.error('Error:', error);
        if (!isBackground) loader.style.display = 'none';
    }
}

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
                        // --- LÓGICA DE PÍXEL PARA MÁXIMO RIESGO ---
                        let maxRisk = -1;
                        let dominantGas = null;
                        let dominantVal = 0;

                        // Calcular IDW para cada gas en este píxel
                        ['NO2', 'O3', 'PM10', 'SO2', 'CO'].forEach(g => {
                            if (multiGasData[g] && multiGasData[g].length > 0) {
                                const val = calculateIDW(latlng, multiGasData[g]);
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

                        if (dominantGas) {
                            // ⚠️ CAMBIO CLAVE: Usar la paleta unificada 'MAX' basada en el riesgo 0-100
                            ctx.fillStyle = interpolateColor(maxRisk, 'MAX');
                            ctx.fillRect(x, y, step, step);
                        }

                    } else {
                        // --- LÓGICA NORMAL ---
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

loadData();
setInterval(() => { loadData(true); }, 60000);
// ==========================================
// PARTE QUE FALTABA: EL DETECTOR DE CAMBIOS
// ==========================================

// 1. Buscamos el elemento HTML del selector por su ID
const selectorGas = document.getElementById('gasSelect');

// 2. Verificamos que exista (por seguridad)
if (selectorGas) {
    // 3. Añadimos el "oído" (Listener) para el evento 'change'
    selectorGas.addEventListener('change', function() {
        loadData(false);
    });
}