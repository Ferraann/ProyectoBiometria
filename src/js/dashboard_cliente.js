/**
 * @file dashboard_cliente.js
 * @brief Gestión de componentes de interfaz de usuario del Dashboard.
 * @details Controla la lógica de calendarios dinámicos, menús desplegables de navegación,
 * el sistema de pestañas (Tabs) y paneles modales informativos.
 * @author Greysy
 * @date 11/11/2025
 */

/**
 * @section 1. LÓGICA DEL CALENDARIO (REUTILIZABLE)
 */

/**
 * @name Calendario
 * @{
 * @brief Inicialización de selectores de fecha.
 */

/** * @brief Selecciona y configura todos los elementos con la clase .date-picker.
 * @details Utiliza la librería externa Flatpickr para transformar inputs en calendarios.
 * @see https://flatpickr.js.org/
 */
const allDatePickers = document.querySelectorAll('.date-picker');

allDatePickers.forEach(picker => {
    flatpickr(picker, {
        dateFormat: "d/m/Y",
        defaultDate: "12/11/2025",

        /**
         * @brief Callback ejecutado al cambiar la fecha.
         * @param {Array} selectedDates Objetos de fecha seleccionados.
         * @param {string} dateStr Representación de cadena de la fecha.
         * @param {Object} instance La instancia de flatpickr.
         */
        onChange: function(selectedDates, dateStr, instance) {
            instance.element.querySelector('span').textContent = 'Fecha: ' + dateStr;
        }
    });
});
/** @} */

/**
 * @section 2. LÓGICA DEL DROPDOWN (REUTILIZABLE)
 * Lógica dropdown
 */

/**
 * @name DropdownNavegacion
 * @{
 */

/** @brief Botones que activan menús desplegables de selección de mapa. */
const allDropdownButtons = document.querySelectorAll('.dropdown-mapa');

/**
 * @brief Configura el comportamiento de apertura y selección de los menús dropdown.
 * @details Al seleccionar un ítem, actualiza el texto del botón y llama a la lógica de Leaflet.
 * @see switchMapView()
 */
allDropdownButtons.forEach(button => {
    const menu = button.nextElementSibling;
    if (!menu || !menu.classList.contains('dropdown-menu')) return;

    const dropdownItems = menu.querySelectorAll('.dropdown-item');
    const span = button.querySelector('span');

    button.addEventListener('click', function(event) {
        event.stopPropagation();
        menu.classList.toggle('show');
    });

    dropdownItems.forEach(item => {
        item.addEventListener('click', function(event) {
            event.preventDefault();
            span.textContent = item.textContent.trim();

            /**
             * @brief Integración con la lógica del mapa (AirQualityMap.js)
             */
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

/**
 * @brief Cierra todos los menús desplegables abiertos al hacer clic fuera de ellos.
 */
document.addEventListener('click', function(event) {
    if (!event.target.closest('.dropdown-container')) {
        document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
            menu.classList.remove('show');
        });
    }
});
/** @} */

/**
 * @section 3. LÓGICA DE PESTAÑAS (TABS)
 * Lógica de pestañas
 */

/**
 * @name SistemaPestanas
 * @{
 */

/** @brief Enlaces de navegación entre pestañas. */
const tabLinks = document.querySelectorAll('.sensores-nav a');

/**
 * @brief Gestiona el cambio de visibilidad entre los diferentes paneles de contenido.
 * @details Si la pestaña seleccionada es 'mapas', se dispara la inicialización del mapa de Leaflet.
 * @see initializeDashboardMap()
 */
tabLinks.forEach(link => {
    link.addEventListener('click', function(event) {
        event.preventDefault();
        const tabId = this.dataset.tab;

        /**
         * @brief Visualización de pestañas.
         */
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

        if (tabId === 'mapas') {
            // El retraso permite que el contenedor se vuelva visible antes de inicializar Leaflet
            setTimeout(() => {
                if (typeof initializeDashboardMap === 'function') {
                    initializeDashboardMap();
                }
            }, 150);
        }
    });
});

/**
 * @brief Inicialización automática al cargar el documento.
 * @details Verifica si la pestaña activa por defecto es la de mapas para renderizarla.
 */
document.addEventListener('DOMContentLoaded', () => {
    const activeTab = document.querySelector('.sensores-nav a.active');
    if (activeTab && activeTab.dataset.tab === 'mapas') {
        setTimeout(() => {
            if (typeof initializeDashboardMap === 'function') {
                initializeDashboardMap();
            }
        }, 400);
    }
});
/** @} */

/**
 * @section 4. LÓGICA DEL MODAL
 */

/**
 * @name PanelInfoModal
 * @{
 * @brief Gestión del panel de información sobre los gases.
 */
document.addEventListener('DOMContentLoaded', () => {
    const infoModal = document.getElementById('gas-info-panel');
    const openInfoBtn = document.getElementById('open-info-btn');
    const closeInfoBtn = document.getElementById('close-info-btn');

    /** @brief Abre el panel modal. */
    if (openInfoBtn) {
        openInfoBtn.addEventListener('click', () => {
            infoModal.style.display = 'block';
        });
    }

    /** @brief Cierra el panel modal. */
    if (closeInfoBtn) {
        closeInfoBtn.addEventListener('click', () => {
            infoModal.style.display = 'none';
        });
    }

    /** @brief Cierre por clic fuera del contenedor. */
    window.addEventListener('click', (event) => {
        if (event.target === infoModal) {
            infoModal.style.display = 'none';
        }
    });

    /** @brief Cierre mediante la tecla Escape. */
    window.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && infoModal.style.display === 'block') {
            infoModal.style.display = 'none';
        }
    });
});
/** @} */