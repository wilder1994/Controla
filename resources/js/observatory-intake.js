export function observatoryIntake(cfg) {
    return {
        step: cfg.installationId ? 2 : 1,
        query: '',
        sites: [],
        searched: false,
        installationId: cfg.installationId || '',
        installationName: cfg.installationName || '',
        kind: cfg.kind || '',
        anonymous: Boolean(cfg.anonymous),

        async search() {
            const q = this.query.trim();
            if (q.length < 2) {
                this.sites = [];
                this.searched = false;
                return;
            }
            const res = await fetch(cfg.sitesUrl + '?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } });
            const data = await res.json();
            this.sites = data.sites || [];
            this.searched = true;
        },

        pick(row) {
            this.installationId = String(row.id);
            this.installationName = row.name;
            this.sites = [];
            this.query = row.name;
        },
    };
}
