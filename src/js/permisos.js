/**
 * @file permisos.js
 * @brief Script de utilidad modular para la gestión de roles y permisos.
 * @details Este módulo proporciona funciones para verificar si un usuario activo
 * posee privilegios de Administrador o Técnico mediante consultas a la API.
 * Implementa una política de redirección automática en caso de falta de sesión o errores.
 * @author Manuel
 * @date 11/12/2025
 */

/** @brief Ruta base del punto de entrada de la API. */
const API_URL = "../api/index.php";
/** @brief URL de redirección en caso de acceso no autorizado o error. */
const REDIRECT_URL = "dashboard.php";
/** @brief Objeto por defecto para usuarios sin privilegios. */
const NINGUN_ROL = { esAdmin: false, esTecnico: false };

/**
 * @brief Obtiene los roles del usuario desde el servidor.
 * @details Realiza peticiones asíncronas para comprobar los privilegios del usuario.
 * Si el ID de usuario no es válido o ocurre un error de red, el script redirige
 * automáticamente al usuario al Dashboard o Login.
 * * @param {number} userId Identificador único del usuario a consultar.
 * @returns {Promise<{esAdmin: boolean, esTecnico: boolean}>} Objeto con el estado de los roles.
 * @async
 */
export async function obtenerRoles(userId) {

  /** * @section ValidacionSesion
   * @brief Comprobación crítica de autenticación.
   */
  if (!userId || userId <= 0) {
    location.href = REDIRECT_URL;
    return NINGUN_ROL;
  }

  try {
    let esAdmin = false;
    let esTecnico = false;

    /**
     * @section VerificacionRoles
     * @brief Consulta de privilegios a la API.
     */

    /** @brief  2. Verificar Rol de Administrador */
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

    /** @brief 3. Verificar Rol de Técnico */
    const resTecnico = await fetch(`${API_URL}?accion=esTecnico&id=${userId}`);

    if (resTecnico.ok) {
      const dataTecnico = await resTecnico.json();
      esTecnico = !!dataTecnico.es_tecnico;
    } else {
      console.warn(
          `[Permisos] Fallo HTTP al verificar Técnico: ${resTecnico.status}`
      );
    }

    /**
     * @brief Control de acceso.
     * @note En este flujo específico, si el usuario no cumple al menos un rol
     * administrativo o técnico, se considera acceso no autorizado.
     */
    if (esAdmin == false && esTecnico == false) {
      location.href = REDIRECT_URL;
      return NINGUN_ROL;
    }

    return { esAdmin: esAdmin, esTecnico: esTecnico };

  } catch (error) {
    /** * @section ManejoErrores
     * @brief Captura de fallos de red o errores de parseo JSON.
     */
    console.error("Fallo grave al verificar permisos (Red o JSON):", error);
    location.href = REDIRECT_URL;
    return NINGUN_ROL;
  }
}