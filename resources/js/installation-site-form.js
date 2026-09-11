export function installationSiteForm(config) {
    return {
        sameClient: Boolean(config.sameClient),
        name: config.name || '',
        clientName: config.clientName || '',
        clientHasGeo: Boolean(config.clientHasGeo),

        init() {
            if (this.sameClient) {
                this.applyClient();
            }
        },

        toggleSameClient() {
            if (this.sameClient) {
                this.applyClient();
            }
        },

        applyClient() {
            this.name = this.clientName;
        },
    };
}
