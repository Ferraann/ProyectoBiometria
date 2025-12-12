// =================================================================
// ARCHIVO: permisos.js hecho por manu
// =================================================================

const API_URL = '../api/index.php';
const REDIRECT_URL = 'dashboard.php'; 
const NINGUN_ROL = { esAdmin: false, esTecnico: false }; // Objeto predeterminado

export async function obtenerRoles(userId) {
    
    if (!userId || userId <= 0) {
        // Si no hay ID de usuario válido, redirigir y retornar NINGUN_ROL.
        location.href = REDIRECT_URL;
        return NINGUN_ROL;
    }
    
    // Lista de acciones de permiso a verificar
    const acciones = ['esAdministrador', 'esTecnico'];
    
    try {
        // 1. Ejecutar ambas llamadas a la API en paralelo (más rápido)
        const resultados = await Promise.all(
            acciones.map(accion => 
                fetch(`${API_URL}?accion=${accion}&id=${userId}`)
            )
        );

        // 2. Convertir respuestas a JSON
        const data = await Promise.all(
            resultados.map(res => res.json())
        );
        
        // 3. Extraer los permisos del array de respuestas
        const roles = {
            // Buscamos el resultado de 'esAdministrador'
            esAdmin: data.find(item => item.hasOwnProperty('es_admin'))?.es_admin === true,
            // Buscamos el resultado de 'esTecnico'
            esTecnico: data.find(item => item.hasOwnProperty('es_tecnico'))?.es_tecnico === true
        };

        // 4. Comprobación final y Redirección
        if (!roles.esAdmin && !roles.esTecnico) {
            console.warn("Acceso denegado: El usuario no tiene permisos.");
            location.href = REDIRECT_URL;
            return NINGUN_ROL; // Retornamos NINGUN_ROL aunque hayamos redirigido
        }

        return roles; // Retornar los roles específicos

    } catch (error) {
        console.error("Fallo grave al verificar permisos:", error);
        location.href = REDIRECT_URL; // Fallo de red/API: denegar acceso
        return NINGUN_ROL;
    }
}