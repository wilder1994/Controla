export function postEmployeePicker(config) {
    return {
        searchUrl: config.searchUrl || '',
        exceptPostId: config.exceptPostId || null,
        selected: config.selected || [],
        query: '',
        results: [],
        open: false,
        warning: '',
        warningUrl: '',
        timer: null,

        search() {
            clearTimeout(this.timer);
            const q = this.query.trim();
            this.warning = '';
            this.warningUrl = '';
            if (q.length < 2) {
                this.results = [];
                this.open = false;

                return;
            }

            this.timer = setTimeout(() => this.fetchResults(q), 220);
        },

        async fetchResults(q) {
            const params = new URLSearchParams({ q });
            if (this.exceptPostId) {
                params.set('except_post', String(this.exceptPostId));
            }

            const { data } = await window.axios.get(`${this.searchUrl}?${params.toString()}`);
            this.results = data.employees || [];
            this.open = this.results.length > 0;
        },

        pick(item) {
            if (item.assigned) {
                const job = item.job || 'empleado';
                const place = [item.assigned.post, item.assigned.client].filter(Boolean).join(' · ');
                this.warning = `${item.name} (${job}) ya está en ${place}. Reasígnalo desde su ficha.`;
                this.warningUrl = item.assigned.url || '';
                this.open = false;

                return;
            }

            if (!this.selected.some((row) => Number(row.id) === Number(item.id))) {
                this.selected.push({
                    id: item.id,
                    name: item.name,
                    document: item.document,
                    job: item.job,
                });
            }

            this.query = '';
            this.results = [];
            this.open = false;
            this.warning = '';
            this.warningUrl = '';
        },

        remove(id) {
            this.selected = this.selected.filter((row) => Number(row.id) !== Number(id));
        },
    };
}
