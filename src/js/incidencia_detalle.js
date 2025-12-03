const API_URL = '../api/index.php';
const cont = document.getElementById('contenido-detalle');

// Obtener ?id=XX
const params = new URLSearchParams(window.location.search);
const id = params.get('id');

if (!id) {
  cont.innerHTML = "<p>Error: ID no especificado.</p>";
  throw new Error("ID no encontrado en la URL");
}

async function cargarDetalle() {
  try {
    // 1. Datos de la incidencia
    const res = await fetch(`${API_URL}?accion=getIncidencia&id=${id}`);
    const data = await res.json();

    if (!data || !data.id) {
      cont.innerHTML = "<p>No se encontró la incidencia.</p>";
      return;
    }

    // 2. Fotos
    const resFotos = await fetch(`${API_URL}?accion=getFotosIncidencia&incidencia_id=${id}`);
    const fotosData = await resFotos.json();

    const htmlFotos = (fotosData.fotos?.length)
      ? fotosData.fotos.map(f => `<img src="data:image/jpeg;base64,${f.foto}" />`).join('')
      : "<p><em>Sin fotos</em></p>";

    cont.innerHTML = `
      <h2>${data.titulo}</h2>
      <p><strong>Descripción:</strong> ${data.descripcion}</p>
      <p><strong>Usuario:</strong> ${data.usuario || "Anónimo"}</p>
      <p><strong>Técnico:</strong> ${data.tecnico}</p>
      <p><strong>Estado:</strong> ${data.estado}</p>
      <p><strong>Fecha:</strong> ${new Date(data.fecha_creacion).toLocaleString()}</p>

      <h3>Fotos</h3>
      <div class="fotos-detalle">${htmlFotos}</div>

      <br>
      <button onclick="history.back()">Volver</button>
    `;
  } catch (error) {
    console.error(error);
    cont.innerHTML = "<p>Error cargando los datos.</p>";
  }
}

cargarDetalle();
