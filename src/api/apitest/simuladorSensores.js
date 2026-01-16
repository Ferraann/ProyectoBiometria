const URL = "https://fsanpra.upv.edu.es/src/api/index.php";
const TOTAL_SENSORES = 200;      // total de nodos simulados
const INTERVALO_MS = 2000;       // cada cuánto se envían datos
const LOTE = 20;                 // procesar en lotes
const MAX_HISTORY = 1000;        // máximo de claves idempotentes guardadas
const PAUSA_LOTE_MS = 300;

const sensores = Array.from({ length: TOTAL_SENSORES }, (_, i) => i + 1);

// memoria idempotente (clave → timestamp)
const enviados = new Map();

// control de ejecución
let running = true;

// redondea el tiempo a slots fijos
function timeSlot() {
  return Math.floor(Date.now() / INTERVALO_MS) * INTERVALO_MS;
}

function claveIdempotente(sensorId, slot) {
  return `${sensorId}_1_${slot}`;
}

// genera datos de sensor
function generarDato(sensorId) {
  return {
    accion: "guardarMedicion",
    sensor_id: sensorId,
    tipo_medicion_id: 1,
    valor: Number((20 + Math.random() * 10).toFixed(2)),
    localizacion: "DemoSala1"
  };
}

// envío de un dato
async function enviarDato(sensorId) {
  const slot = timeSlot();
  const key = claveIdempotente(sensorId, slot);

  // ⛔ evitar duplicados
  if (enviados.has(key)) return;

  enviados.set(key, Date.now());

  try {
    const res = await fetch(URL, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "Idempotency-Key": key
      },
      body: JSON.stringify(generarDato(sensorId))
    });

    if (!res.ok) {
      const texto = await res.text();
      enviados.delete(key); // permitir reintento
      console.error(`❌ Sensor ${sensorId} HTTP ${res.status}: ${texto}`);
    } else {
      console.log(`✅ Sensor ${sensorId} enviado`);
    }
  } catch (e) {
    enviados.delete(key);
    console.error(`🔥 Sensor ${sensorId} error: ${e.message}`);
  }
}

// limpieza por antigüedad real
function limpiarMemoria() {
  if (enviados.size <= MAX_HISTORY) return;

  const ahora = Date.now();
  for (const [key, timestamp] of enviados) {
    if (ahora - timestamp > INTERVALO_MS * 2) {
      enviados.delete(key);
    }
    if (enviados.size <= MAX_HISTORY) break;
  }
}

// simulador principal
async function simulador() {
  console.log("🚀 Simulador iniciado");

  while (running) {
    for (let i = 0; i < sensores.length && running; i += LOTE) {
      const lote = sensores.slice(i, i + LOTE);
      await Promise.all(lote.map(enviarDato));
      await new Promise(r => setTimeout(r, PAUSA_LOTE_MS));
    }

    limpiarMemoria();
    await new Promise(r => setTimeout(r, INTERVALO_MS));
  }

  console.log("🛑 Simulador detenido limpiamente");
}

// parada limpia con Ctrl+C
process.on("SIGINT", () => {
  console.log("\n⏹ Deteniendo simulador...");
  running = false;
});

// iniciar
simulador();
