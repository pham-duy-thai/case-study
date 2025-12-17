window.addEventListener('DOMContentLoaded', () => {
    const sidebarToggle = document.body.querySelector('#sidebarToggle');
    if (!sidebarToggle) return;

    const storedToggle = localStorage.getItem('sb|sidebar-toggle');
    if (storedToggle === 'true') {
        document.body.classList.toggle('sb-sidenav-toggled');
    }

    sidebarToggle.addEventListener('click', (event) => {
        event.preventDefault();
        document.body.classList.toggle('sb-sidenav-toggled');
        localStorage.setItem(
            'sb|sidebar-toggle',
            document.body.classList.contains('sb-sidenav-toggled')
        );
    });
});
