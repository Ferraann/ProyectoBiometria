/* --- 1. LÓGICA DEL CALENDARIO (REUTILIZABLE) --- */

// Seleccionamos TODOS los elementos que tengan la clase .date-picker
const allDatePickers = document.querySelectorAll('.date-picker');

// Recorremos cada uno de ellos y les aplicamos flatpickr
allDatePickers.forEach(picker => {
    flatpickr(picker, {
        dateFormat: "d/m/Y", // Formato de fecha
        defaultDate: "12/11/2025", // Fecha por defecto
        
        // Esta función se ejecuta cuando seleccionas una fecha
        onChange: function(selectedDates, dateStr, instance) {
            // 'instance.element' se refiere al 'picker' en el que hicimos clic
            instance.element.querySelector('span').textContent = 'Fecha: ' + dateStr;
        }
    });
});

/* --- 2. LÓGICA DEL DROPDOWN (REUTILIZABLE) --- */
const allDropdownButtons = document.querySelectorAll('.dropdown-mapa');

allDropdownButtons.forEach(button => {
    const menu = button.nextElementSibling;
    if (!menu || !menu.classList.contains('dropdown-menu')) return;

    const dropdownItems = menu.querySelectorAll('.dropdown-item'); // Cambiado el nombre para mayor claridad
    const span = button.querySelector('span');

    button.addEventListener('click', function(event) {
        event.stopPropagation();
        menu.classList.toggle('show');
    });

    // AQUÍ ESTABA EL ERROR: Asegúrate de usar 'dropdownItem' dentro del forEach
    dropdownItems.forEach(item => { // Asegúrate de llamar a la variable 'item' aquí
        item.addEventListener('click', function(event) {
            event.preventDefault();
            span.textContent = item.textContent.trim();

            // Llamamos a la lógica del mapa
            if (typeof switchMapView === 'function') {
                if (item.textContent.includes("Mis sensores personales")) {
                    switchMapView('personal');
                } else {
                    switchMapView('general');
                }
            }
            menu.classList.remove('show');
        });
    });
});

// Cerrar TODOS los menús si se hace clic fuera de ellos
document.addEventListener('click', function(event) {
    // Si el clic no fue dentro de un .dropdown-container, cierra todo
    if (!event.target.closest('.dropdown-container')) {
        document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
            menu.classList.remove('show');
        });
    }
});


const tabLinks = document.querySelectorAll('.sensores-nav a');

tabLinks.forEach(link => {
    link.addEventListener('click', function(event) {
        event.preventDefault();
        const tabId = this.dataset.tab;

        // Visualización de pestañas
        tabLinks.forEach(l => l.classList.remove('active'));
        this.classList.add('active');

        const tabContents = document.querySelectorAll('.tab-content');
        tabContents.forEach(content => {
            if (content.getAttribute('data-tab-content') === tabId) {
                content.classList.add('active-tab-content');
            } else {
                content.classList.remove('active-tab-content');
            }
        });

        // Solo llamamos al mapa si la pestaña es "mapas"
        if (tabId === 'mapas') {
            // El retraso permite que el contenedor se vuelva visible antes de inicializar
            setTimeout(() => {
                if (typeof initializeDashboardMap === 'function') {
                    initializeDashboardMap();
                }
            }, 150);
        }
    });
});

// Inicialización controlada al cargar el DOM
document.addEventListener('DOMContentLoaded', () => {
    const activeTab = document.querySelector('.sensores-nav a.active');
    if (activeTab && activeTab.dataset.tab === 'mapas') {
        // Un solo temporizador para la carga inicial
        setTimeout(() => {
            if (typeof initializeDashboardMap === 'function') {
                initializeDashboardMap();
            }
        }, 400);
    }
});