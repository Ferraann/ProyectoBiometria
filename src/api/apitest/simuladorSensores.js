
const URL = "https://fsanpra.upv.edu.es/src/api/index.php";
const TOTAL_SENSORES = 200;      // total de nodos simulados
const INTERVALO_MS = 2000;       // cada cuánto se envían datos
const LOTE = 20;                 // procesar en lotes para no saturar
const MAX_HISTORY = 1000;        // máximo de claves idempotentes guardadas

const sensores = Array.from({ length: TOTAL_SENSORES }, (_, i) => i + 1);

// memoria idempotente (cliente)
const enviados = new Set();

// estadísticas
const stats = {
  exitos: 0,
  fallidos: 0
};

// redondea el tiempo a slots de INTERVALO_MS
function timeSlot() {
  return Math.floor(Date.now() / INTERVALO_MS) * INTERVALO_MS;
}

function claveIdempotente(sensorId, slot) {
  return `${sensorId}_1_${slot}`;
}

// genera datos de sensor
function generarDato(sensorId, slot) {
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

  // ⛔ ya enviado → no duplicar
  if (enviados.has(key)) return;

  enviados.add(key);

  try {
    const res = await fetch(URL, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(generarDato(sensorId, slot))
    });

    if (!res.ok) {
      const texto = await res.text();
      enviados.delete(key);
      stats.fallidos++;
      console.error(`❌ Sensor ${sensorId} HTTP ${res.status}: ${texto}`);
    } else {
      stats.exitos++;
      console.log(`✅ Sensor ${sensorId} enviado`);
    }
  } catch (e) {
    enviados.delete(key);
    stats.fallidos++;
    console.error(`🔥 Sensor ${sensorId} fallo: ${e.message}`);
  }
}

// limpieza de memoria de claves idempotentes
function limpiarMemoria() {
  if (enviados.size > MAX_HISTORY) {
    const iter = enviados.values();
    for (let i = 0; i < 200; i++) {
      enviados.delete(iter.next().value);
    }
  }
}

// simulador principal
async function simulador() {
  while (true) {
    for (let i = 0; i < sensores.length; i += LOTE) {
      const lote = sensores.slice(i, i + LOTE);
      await Promise.all(lote.map(enviarDato));
      await new Promise(r => setTimeout(r, 300));
    }

    // limpiar memoria de idempotencia
    limpiarMemoria();

    // mostrar estadísticas
    console.log(`📊 Estadísticas: Exitosos=${stats.exitos}, Fallidos=${stats.fallidos}`);

    await new Promise(r => setTimeout(r, INTERVALO_MS));
  }
}

// iniciar simulador
simulador();
