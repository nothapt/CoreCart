(() => {
    'use strict';

    const body = document.body;
    const isMobile = () => window.matchMedia('(max-width: 780px)').matches;

    const sidebarToggle = document.querySelector('[data-sidebar-toggle]');
    const sidebarOverlay = document.querySelector('[data-sidebar-close]');

    const applyDesktopSidebarState = () => {
        if (!isMobile()) {
            const collapsed = localStorage.getItem('cc-admin-sidebar-collapsed') === '1';
            body.classList.toggle('cc-sidebar-collapsed', collapsed);
            body.classList.remove('cc-sidebar-mobile-open');
        } else {
            body.classList.remove('cc-sidebar-collapsed');
        }
    };

    sidebarToggle?.addEventListener('click', () => {
        if (isMobile()) {
            body.classList.toggle('cc-sidebar-mobile-open');
            return;
        }

        body.classList.toggle('cc-sidebar-collapsed');
        localStorage.setItem(
            'cc-admin-sidebar-collapsed',
            body.classList.contains('cc-sidebar-collapsed') ? '1' : '0'
        );
    });

    sidebarOverlay?.addEventListener('click', () => {
        body.classList.remove('cc-sidebar-mobile-open');
    });

    document.querySelectorAll('[data-nav-toggle]').forEach((button) => {
        const submenuId = button.getAttribute('aria-controls');
        const submenu = submenuId ? document.getElementById(submenuId) : null;
        if (!submenu) return;

        const storageKey = `cc-admin-menu-${submenuId}`;
        const shouldOpen =
            button.dataset.active === '1' ||
            localStorage.getItem(storageKey) === '1';

        button.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');
        submenu.classList.toggle('open', shouldOpen);

        button.addEventListener('click', () => {
            const open = button.getAttribute('aria-expanded') !== 'true';

            button.setAttribute('aria-expanded', open ? 'true' : 'false');
            submenu.classList.toggle('open', open);
            localStorage.setItem(storageKey, open ? '1' : '0');
        });
    });

    document.querySelectorAll('[data-dropdown-toggle]').forEach((toggle) => {
        const wrapper = toggle.closest('.cc-dropdown');
        if (!wrapper) return;

        toggle.addEventListener('click', (event) => {
            event.stopPropagation();

            document.querySelectorAll('.cc-dropdown.open').forEach((other) => {
                if (other !== wrapper) other.classList.remove('open');
            });

            wrapper.classList.toggle('open');
        });
    });

    document.addEventListener('click', () => {
        document.querySelectorAll('.cc-dropdown.open').forEach((dropdown) => {
            dropdown.classList.remove('open');
        });
    });

    document.querySelectorAll('[data-tab-target]').forEach((button) => {
        button.addEventListener('click', () => {
            const scope = button.closest('[data-tabs]');
            if (!scope) return;

            const target = button.dataset.tabTarget;
            scope.querySelectorAll('[data-tab-target]').forEach((tab) => {
                tab.classList.toggle('active', tab === button);
            });
            scope.querySelectorAll('[data-tab-pane]').forEach((pane) => {
                pane.classList.toggle('active', pane.dataset.tabPane === target);
            });
        });
    });

    document.querySelectorAll('[data-confirm]').forEach((element) => {
        element.addEventListener('click', (event) => {
            const message = element.dataset.confirm || 'Are you sure?';
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            body.classList.remove('cc-sidebar-mobile-open');
            document.querySelectorAll('.cc-dropdown.open').forEach((dropdown) => {
                dropdown.classList.remove('open');
            });
        }
    });

    window.addEventListener('resize', applyDesktopSidebarState);
    applyDesktopSidebarState();
})();