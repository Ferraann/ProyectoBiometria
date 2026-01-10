const URL = "http://TU_SERVIDOR/api/sensores";
const TOTAL_SENSORES = 200;
const INTERVALO_MS = 1000;

function generarDato(sensorId) {
  return {
    sensorId,
    temperatura: Number((20 + Math.random() * 10).toFixed(2)),
    humedad: Number((40 + Math.random() * 20).toFixed(2)),
    bateria: Number((50 + Math.random() * 50).toFixed(0)),
    timestamp: Date.now()
  };
}

async function enviarDato(sensorId) {
  try {
    const res = await fetch(URL, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(generarDato(sensorId))
    });

    if (!res.ok) {
      console.error(`Sensor ${sensorId} → ${res.status}`);
    }
  } catch (e) {
    console.error(`Sensor ${sensorId} ❌`, e.message);
  }
}

// Lanzar sensores simulados
for (let i = 1; i <= TOTAL_SENSORES; i++) {
  setInterval(() => enviarDato(i), INTERVALO_MS);
}
