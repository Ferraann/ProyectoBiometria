// Obtener los elementos
const profileButton = document.getElementById('profile-toggle-button');
const profileMenu = document.getElementById('profile-menu');
const closeButton = document.getElementById('close-menu-button');

// Función para mostrar/ocultar el menú
function toggleProfileMenu() {
    // Alterna la clase 'show' para mostrar/ocultar el menú
    profileMenu.classList.toggle('show');
}

// 1. Mostrar el menú al hacer clic en el botón/icono de perfil
if (profileButton) {
    profileButton.addEventListener('click', function(event) {
        event.preventDefault(); // Evita que el enlace salte
        toggleProfileMenu();
    });
}

// 2. Ocultar el menú al hacer clic en la 'X' de cerrar
if (closeButton) {
    closeButton.addEventListener('click', function(event) {
        // Asegura que solo oculte el menú sin propagar el evento
        event.stopPropagation();
        profileMenu.classList.remove('show');
    });
}

// 3. Ocultar el menú al hacer clic fuera de él (opcional pero recomendado)
document.addEventListener('click', function(event) {
    const isClickInsideMenu = profileMenu.contains(event.target);
    const isClickOnButton = profileButton.contains(event.target);
    
    // Si el menú está visible y el clic no está dentro del menú ni en el botón...
    if (profileMenu.classList.contains('show') && !isClickInsideMenu && !isClickOnButton) {
        profileMenu.classList.remove('show');
    }
});