// ------------------------------------------------------------------
// Fichero: permisos.js
// Autor: Manuel
// Fecha: 11/12/2025
// ------------------------------------------------------------------
// Descripción:
//  Script de utilidad modular para la verificación y obtención de roles
//  (Administrador y Técnico) del usuario activo en el sistema.
//
// Funcionalidad:
//  - Función 'obtenerRoles' que utiliza el ID del usuario de la sesión.
//  - Realiza dos llamadas a la API en paralelo (`Promise.all`) para optimizar
//    la verificación de los roles 'esAdministrador' y 'esTecnico'.
//  - **Seguridad:** Redirige a la página de login/dashboard si el usuario no tiene
//    una sesión válida (ID = 0) o si hay un fallo de comunicación con la API.
//  - Devuelve un objeto `{ esAdmin: boolean, esTecnico: boolean }`.
// ------------------------------------------------------------------

const API_URL = "../api/index.php";
const REDIRECT_URL = "dashboard.php";
const NINGUN_ROL = { esAdmin: false, esTecnico: false };

export async function obtenerRoles(userId) {
  // 1. CRÍTICO: Comprobación de que el usuario está logueado
  if (!userId || userId <= 0) {
    location.href = REDIRECT_URL;
    return NINGUN_ROL;
  }

  try {
    let esAdmin = false;
    let esTecnico = false;

    // 2. Verificar Rol de Administrador
    const resAdmin = await fetch(
      `${API_URL}?accion=esAdministrador&id=${userId}`
    );

    if (resAdmin.ok) {
      const dataAdmin = await resAdmin.json();
      esAdmin = !!dataAdmin.es_admin;
    } else {
      console.warn(
        `[Permisos] Fallo HTTP al verificar Administrador: ${resAdmin.status}`
      );
    }

    // 3. Verificar Rol de Técnico
    const resTecnico = await fetch(`${API_URL}?accion=esTecnico&id=${userId}`);

    if (resTecnico.ok) {
      const dataTecnico = await resTecnico.json();
      esTecnico = !!dataTecnico.es_tecnico;
    } else {
      console.warn(
        `[Permisos] Fallo HTTP al verificar Técnico: ${resTecnico.status}`
      );
    }

    // si no es ninguno de los dos, redirigir
    if (esAdmin == false || esTecnico == false) {
      location.href = REDIRECT_URL;
      return NINGUN_ROL;
    }

    // 4. Devolvemos los roles
    return { esAdmin: esAdmin, esTecnico: esTecnico };
  } catch (error) {
    // Este catch captura errores de red o errores al parsear JSON (si fallara res.json())
    console.error("Fallo grave al verificar permisos (Red o JSON):", error);
    location.href = REDIRECT_URL;
    return NINGUN_ROL;
  }
}
