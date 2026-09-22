document.addEventListener('DOMContentLoaded', function () {
    const sidebar = document.querySelector('.sidebar-wrapper');
    const toggleBtn = document.querySelector('.sidebar-toggle-btn');

    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function (event) {
            event.stopPropagation();
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show', sidebar.classList.contains('show'));
        });

        overlay.addEventListener('click', function () {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
    }

    const currentPath = window.location.pathname.replace(/\/$/, '') || '/';
    const sidebarLinks = Array.from(document.querySelectorAll('.sidebar-menu-link[href], .sidebar-submenu-link[href]'))
        .filter((link) => link.getAttribute('href') && !link.getAttribute('href').startsWith('#'));

    const normalizePath = (href) => new URL(href, window.location.origin).pathname.replace(/\/$/, '') || '/';
    const isRouteMatch = (linkPath) => currentPath === linkPath || (linkPath !== '/' && currentPath.startsWith(`${linkPath}/`));

    let activeLink = sidebarLinks
        .map((link) => ({ link, path: normalizePath(link.href) }))
        .filter((item) => isRouteMatch(item.path))
        .sort((a, b) => b.path.length - a.path.length)[0]?.link;

    if (activeLink) {
        document.querySelectorAll('.sidebar-menu-link.active, .sidebar-submenu-link.active').forEach((link) => {
            link.classList.remove('active');
        });

        activeLink.classList.add('active');
        const submenu = activeLink.closest('.sidebar-submenu');
        if (submenu) {
            submenu.classList.add('show');
            const parentLink = document.querySelector(`[aria-controls="${submenu.id}"]`);
            if (parentLink) {
                parentLink.classList.add('active');
                parentLink.setAttribute('aria-expanded', 'true');
            }
        }
    }

    const desktopToggleBtn = document.querySelector('#desktop-sidebar-toggle');
    if (desktopToggleBtn) {
        desktopToggleBtn.addEventListener('click', function () {
            document.body.classList.toggle('sidebar-minimized');

            const icon = desktopToggleBtn.querySelector('i');
            if (icon) {
                icon.className = document.body.classList.contains('sidebar-minimized')
                    ? 'bi bi-chevron-bar-right'
                    : 'bi bi-chevron-bar-left';
            }

            setTimeout(() => window.dispatchEvent(new Event('resize')), 300);
        });
    }

    const fullscreenBtn = document.querySelector('#btn-fullscreen');
    if (fullscreenBtn) {
        const updateFullscreenIcon = function (isFullscreen) {
            const icon = fullscreenBtn.querySelector('i');
            if (icon) {
                icon.className = isFullscreen ? 'bi bi-fullscreen-exit' : 'bi bi-arrows-fullscreen';
            }
        };

        fullscreenBtn.addEventListener('click', function () {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen()
                    .then(() => updateFullscreenIcon(true))
                    .catch((error) => console.error(`Fullscreen error: ${error.message}`));
            } else {
                document.exitFullscreen()
                    .then(() => updateFullscreenIcon(false))
                    .catch((error) => console.error(`Fullscreen exit error: ${error.message}`));
            }
        });

        document.addEventListener('fullscreenchange', () => updateFullscreenIcon(Boolean(document.fullscreenElement)));
    }
});
