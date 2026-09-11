export function firstAdminAccess(config) {
    return {
        previewUrl: config.previewUrl || '',
        username: config.username || '',
        password: config.password || '',
        loading: false,
        timer: null,

        init() {
            ['first_names', 'last_name_paternal', 'last_name_maternal'].forEach((id) => {
                document.getElementById(id)?.addEventListener('input', () => this.schedule());
            });
            this.schedule();
        },

        names() {
            return {
                first: document.getElementById('first_names')?.value.trim() ?? '',
                paternal: document.getElementById('last_name_paternal')?.value.trim() ?? '',
                maternal: document.getElementById('last_name_maternal')?.value.trim() ?? '',
            };
        },

        canGenerate() {
            const { first, paternal, maternal } = this.names();

            return first.length >= 2 && (paternal !== '' || maternal !== '');
        },

        schedule() {
            if (this.timer) {
                clearTimeout(this.timer);
            }
            if (! this.canGenerate()) {
                this.username = '';
                this.password = '';

                return;
            }
            this.timer = setTimeout(() => this.generate(), 450);
        },

        async generate() {
            if (! this.canGenerate() || ! this.previewUrl) {
                return;
            }
            const { first, paternal, maternal } = this.names();
            this.loading = true;
            try {
                const body = new FormData();
                body.append('first_names', first);
                body.append('last_name_paternal', paternal);
                body.append('last_name_maternal', maternal);
                body.append('_token', document.querySelector('meta[name=csrf-token]').content);
                const res = await fetch(this.previewUrl, {
                    method: 'POST',
                    headers: { Accept: 'application/json' },
                    body,
                });
                const data = await res.json();
                if (! res.ok) {
                    throw new Error('preview');
                }
                this.username = data.username;
                this.password = data.password;
            } catch (error) {
                this.username = '';
                this.password = '';
            } finally {
                this.loading = false;
            }
        },
    };
}
