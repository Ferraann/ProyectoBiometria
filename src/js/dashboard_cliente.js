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

// Seleccionamos TODOS los botones de dropdown (los que tienen la clase .dropdown-mapa)
const allDropdownButtons = document.querySelectorAll('.dropdown-mapa');

allDropdownButtons.forEach(button => {
    // Buscamos el menú que es su "hermano" (el que está justo después en el HTML)
    const menu = button.nextElementSibling;
    
    // Verificamos que sea un menú antes de seguir
    if (!menu || !menu.classList.contains('dropdown-menu')) {
        return; 
    }

    const items = menu.querySelectorAll('.dropdown-item');
    const span = button.querySelector('span');

    // Abrir/Cerrar el menú al hacer clic en ESTE botón
    button.addEventListener('click', function(event) {
        event.stopPropagation(); // Evita que el clic se propague al 'document'
        
        // Antes de abrir, cerramos todos los *otros* menús que estén abiertos
        document.querySelectorAll('.dropdown-menu.show').forEach(openMenu => {
            if (openMenu !== menu) {
                openMenu.classList.remove('show');
            }
        });

        // Ahora sí, abrimos o cerramos el menú actual
        menu.classList.toggle('show');
    });

    // Actualizar el texto al hacer clic en un item
    items.forEach(item => {
        item.addEventListener('click', function(event) {
            event.preventDefault(); // Evita que el enlace '#' recargue la página
            span.textContent = item.textContent; // Cambia el texto del botón
            menu.classList.remove('show'); // Cierra el menú
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


/* --- 3. LÓGICA DEL SISTEMA DE PESTAÑAS --- */

const tabLinks = document.querySelectorAll('.sensores-nav a');
const tabContents = document.querySelectorAll('.tab-content');

tabLinks.forEach(link => {
    link.addEventListener('click', function(event) {
        event.preventDefault(); 
        const tabId = this.dataset.tab;
        tabLinks.forEach(l => l.classList.remove('active'));
        tabContents.forEach(c => c.classList.remove('active-tab-content'));
        this.classList.add('active');
        const activeContent = document.querySelector(`.tab-content[data-tab-content="${tabId}"]`);
        activeContent.classList.add('active-tab-content');
    });
});