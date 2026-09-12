export function panelSidebar() {
    return {
        open: true,
        init() {
            this.open = window.localStorage.getItem('controla-sidebar-open') !== '0';
        },
        toggle() {
            this.open = ! this.open;
            window.localStorage.setItem('controla-sidebar-open', this.open ? '1' : '0');
        },
    };
}
