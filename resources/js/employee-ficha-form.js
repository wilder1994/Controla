export function employeeFichaForm(config) {
    return {
        places: config.places || {},
        birthDepartment: config.birthDepartment || '',
        birthCity: config.birthCity || '',
        issueDepartment: config.issueDepartment || '',
        issueCity: config.issueCity || '',
        types: config.types || [],
        titles: config.titles || [],
        selectedType: config.selectedType ? String(config.selectedType) : '',
        selectedTitle: config.selectedTitle ? String(config.selectedTitle) : '',
        catalogUrl: config.catalogUrl || '',
        catalogOpen: false,
        catalogNotice: '',
        catalogError: '',
        catalogLoading: false,
        newTypeName: '',
        newTitleName: '',
        photoPreview: config.photoPreview || null,

        init() {
            this.$nextTick(() => this.keepPlaceholderIfUnset());
        },

        previewPhoto(event) {
            const file = event.target.files?.[0];
            if (!file) {
                return;
            }
            this.photoPreview = URL.createObjectURL(file);
        },

        keepPlaceholderIfUnset() {
            if (!this.selectedType && this.$refs.typeSelect) {
                this.$refs.typeSelect.value = '';
            }
            if (!this.selectedTitle && this.$refs.titleSelect) {
                this.$refs.titleSelect.value = '';
            }
        },

        get departments() {
            return Object.keys(this.places);
        },
        get birthCities() {
            return this.places[this.birthDepartment] || [];
        },
        get issueCities() {
            return this.places[this.issueDepartment] || [];
        },
        get typesEmpty() {
            return this.types.length === 0;
        },
        get titlesEmpty() {
            return this.titles.length === 0;
        },
        get catalogsEmpty() {
            return this.typesEmpty || this.titlesEmpty;
        },

        syncBirthCity() {
            if (!this.birthCities.includes(this.birthCity)) {
                this.birthCity = '';
            }
        },
        syncIssueCity() {
            if (!this.issueCities.includes(this.issueCity)) {
                this.issueCity = '';
            }
        },
        openCatalog(event) {
            if (!this.catalogsEmpty) {
                return;
            }
            event?.preventDefault();
            this.catalogError = '';
            this.catalogOpen = true;
        },
        closeCatalog() {
            this.catalogOpen = false;
        },
        async saveCatalog() {
            if (!this.catalogUrl) {
                return;
            }
            this.catalogLoading = true;
            this.catalogError = '';
            try {
                const body = new FormData();
                body.append('_token', document.querySelector('meta[name=csrf-token]').content);
                if (this.typesEmpty) {
                    body.append('collaborator_type_name', this.newTypeName.trim());
                }
                if (this.titlesEmpty) {
                    body.append('job_title_name', this.newTitleName.trim());
                }
                const res = await fetch(this.catalogUrl, {
                    method: 'POST',
                    headers: { Accept: 'application/json' },
                    body,
                });
                const data = await res.json();
                if (!res.ok) {
                    const first = data.message || (data.errors && Object.values(data.errors)[0][0]);
                    throw new Error(first || 'No se pudo guardar.');
                }
                if (data.collaborator_type) {
                    this.types.push({
                        id: data.collaborator_type.id,
                        name: data.collaborator_type.name,
                    });
                }
                if (data.job_title) {
                    this.titles.push({
                        id: data.job_title.id,
                        name: data.job_title.name,
                    });
                }
                this.selectedType = '';
                this.selectedTitle = '';
                this.catalogNotice = data.message || 'Ya puedes seleccionar el tipo y el cargo.';
                this.catalogOpen = false;
                this.newTypeName = '';
                this.newTitleName = '';
                this.$nextTick(() => this.keepPlaceholderIfUnset());
            } catch (error) {
                this.catalogError = error.message;
            } finally {
                this.catalogLoading = false;
            }
        },
    };
}
