export function employeeReassignForm(config) {
    return {
        open: false,
        tree: config.tree || [],
        clientId: config.clientId ? String(config.clientId) : '',
        installationId: config.installationId ? String(config.installationId) : '',
        postId: config.postId ? String(config.postId) : '',

        get installations() {
            const client = this.tree.find((row) => String(row.id) === String(this.clientId));

            return client?.installations || [];
        },

        get posts() {
            const installation = this.installations.find((row) => String(row.id) === String(this.installationId));

            return installation?.posts || [];
        },

        onClientChange() {
            this.installationId = this.installations[0] ? String(this.installations[0].id) : '';
            this.onInstallationChange();
        },

        onInstallationChange() {
            this.postId = this.posts[0] ? String(this.posts[0].id) : '';
        },
    };
}
