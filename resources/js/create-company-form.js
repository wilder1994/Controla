const fieldLabels = [
    ['party_type', 'tipo de suscriptor'],
    ['legal_name', 'razón social'],
    ['trade_name', 'nombre comercial'],
    ['tax_id', 'NIT'],
    ['email', 'email comercial'],
    ['phone', 'teléfono'],
    ['address', 'dirección'],
    ['city', 'ciudad'],
    ['department', 'departamento'],
    ['latitude', 'coordenadas (mapa)'],
    ['longitude', 'coordenadas (mapa)'],
    ['package_sku', 'paquete de Accesos'],
    ['billing_cycle', 'ciclo'],
    ['supervision_package_sku', 'paquete de Supervisión'],
];

export function createCompanyForm(config) {
    return {
        packageSku: config.packageSku || '',
        supervisionSku: config.supervisionSku || '',
        toast: '',
        toastTimer: null,

        get accessSize() {
            const match = String(this.packageSku).match(/pack_(\d+)_/);

            return match ? Number(match[1]) : 0;
        },

        get allowsSupervision() {
            return this.accessSize >= 5;
        },

        showToast(message) {
            this.toast = message;
            if (this.toastTimer) {
                clearTimeout(this.toastTimer);
            }
            this.toastTimer = setTimeout(() => {
                this.toast = '';
            }, 2800);
        },

        onPackageChange() {
            if (!this.allowsSupervision) {
                this.supervisionSku = '';
            }
        },

        validate(event) {
            const form = event.target;
            for (const [name, label] of fieldLabels) {
                if (name === 'supervision_package_sku' && !this.allowsSupervision) {
                    continue;
                }
                const el = form.elements.namedItem(name);
                const value = el ? String(el.value ?? '').trim() : '';
                if (value === '') {
                    event.preventDefault();
                    this.showToast(`Falta ${label} por llenar`);
                    if (el && typeof el.focus === 'function') {
                        el.focus();
                    }

                    return;
                }
            }
        },
    };
}
