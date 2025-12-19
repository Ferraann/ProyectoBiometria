/**
 * @file menu_perfil.js
 * @brief Gestión del menú desplegable de perfil de usuario.
 * @details Controla la apertura, cierre y comportamiento del menú lateral de perfil,
 * incluyendo la lógica para cerrar el menú al hacer clic fuera de su contenedor.
 * @author Greysy
 * @date 13/11/2025
 */

/** * @name ElementosInterfaz
 * @{
 */
/** @brief Botón o icono que dispara la apertura del menú. @type {HTMLElement} */
const profileButton = document.getElementById('profile-toggle-button');
/** @brief Contenedor principal del menú de perfil. @type {HTMLElement} */
const profileMenu = document.getElementById('profile-menu');
/** @brief Botón de cierre (X) dentro del menú. @type {HTMLElement} */
const closeButton = document.getElementById('close-menu-button');
/** @} */

/**
 * @brief Alterna la visibilidad del menú de perfil.
 * @details Añade o elimina la clase CSS 'show' al contenedor del menú.
 * @returns {void}
 */
function toggleProfileMenu() {
    // Alterna la clase 'show' para mostrar/ocultar el menú
    profileMenu.classList.toggle('show');
}

/**
 * @brief Evento para mostrar el menú al pulsar el botón de perfil.
 * @listens click
 */
if (profileButton) {
    profileButton.addEventListener('click', function(event) {
        event.preventDefault(); // Evita el comportamiento por defecto del enlace
        toggleProfileMenu();
    });
}

/**
 * @brief Evento para ocultar el menú mediante el botón de cierre.
 * @details Utiliza stopPropagation para evitar que el evento se propague al listener del documento.
 * @listens click
 */
if (closeButton) {
    closeButton.addEventListener('click', function(event) {
        event.stopPropagation();
        profileMenu.classList.remove('show');
    });
}

/**
 * @brief Listener global para cerrar el menú al detectar clics externos.
 * @details Evalúa si el clic se realizó fuera tanto del menú como del botón de activación.
 * Si el menú está visible (`show`), procede a cerrarlo.
 * @listens click
 */
document.addEventListener('click', function(event) {
    /** @brief Indica si el clic ocurrió dentro del contenedor del menú. */
    const isClickInsideMenu = profileMenu.contains(event.target);
    /** @brief Indica si el clic ocurrió sobre el botón de perfil. */
    const isClickOnButton = profileButton.contains(event.target);

    /** @brief Evalúa si el usuario ha hecho clic fuera del menú desplegable para cerrarlo automáticamente. */
    if (profileMenu.classList.contains('show') && !isClickInsideMenu && !isClickOnButton) {
        profileMenu.classList.remove('show');
    }
});