const ACCEPT = ['image/png', 'image/jpeg', 'image/webp', 'image/jpg'];
const MAX_BYTES = 2 * 1024 * 1024;
const OUT_SIZE = 512;

function isImageFile(file) {
    if (!file) {
        return false;
    }

    return ACCEPT.includes(file.type) || /\.(png|jpe?g|webp)$/i.test(file.name || '');
}

export function companyLogoField(initialPreview) {
    return {
        preview: initialPreview || '',
        hadServerLogo: Boolean(initialPreview),
        removeLogo: false,
        error: '',
        dragging: false,
        editorOpen: false,
        img: null,
        angle: 0,
        panX: 0,
        panY: 0,
        pointer: { down: false, lastX: 0, lastY: 0 },

        pick() {
            this.$refs.filePicker.click();
        },

        onPicker(event) {
            const file = event.target.files?.[0];
            event.target.value = '';
            this.openEditor(file);
        },

        onDrop(event) {
            this.dragging = false;
            const file = event.dataTransfer?.files?.[0];
            this.openEditor(file);
        },

        onPaste(event) {
            if (this.editorOpen) {
                return;
            }
            const tag = (event.target?.tagName || '').toLowerCase();
            if (tag === 'textarea' || tag === 'input') {
                return;
            }
            const item = [...(event.clipboardData?.items || [])].find((entry) => entry.type.startsWith('image/'));
            if (!item) {
                return;
            }
            const file = item.getAsFile();
            event.preventDefault();
            this.openEditor(file);
        },

        openEditor(file) {
            this.error = '';
            if (!file) {
                return;
            }
            if (!isImageFile(file)) {
                this.error = 'Usa PNG, JPG o WebP.';
                return;
            }
            if (file.size > MAX_BYTES * 4) {
                this.error = 'El archivo es demasiado grande. Máximo 2 MB al guardar.';
                return;
            }

            const img = new Image();
            img.onload = () => {
                this.img = img;
                this.angle = 0;
                this.panX = 0;
                this.panY = 0;
                this.editorOpen = true;
                this.$nextTick(() => this.draw());
            };
            img.onerror = () => {
                this.error = 'No se pudo leer la imagen.';
            };
            img.src = URL.createObjectURL(file);
        },

        rotate() {
            this.angle = (this.angle + 90) % 360;
            this.draw();
        },

        closeEditor() {
            this.editorOpen = false;
            this.img = null;
        },

        bounds() {
            const rotated = this.angle % 180 === 0;
            const bw = rotated ? this.img.width : this.img.height;
            const bh = rotated ? this.img.height : this.img.width;

            return { bw, bh };
        },

        draw() {
            const canvas = this.$refs.cropCanvas;
            if (!canvas || !this.img) {
                return;
            }
            const stage = canvas.parentElement;
            const css = stage?.clientWidth || 280;
            const size = Math.round(css * 2);
            canvas.width = size;
            canvas.height = size;
            const ctx = canvas.getContext('2d');
            const { bw, bh } = this.bounds();
            const scale = Math.max(size / bw, size / bh);
            ctx.fillStyle = '#0f172a';
            ctx.fillRect(0, 0, size, size);
            ctx.save();
            ctx.translate(size / 2 + this.panX, size / 2 + this.panY);
            ctx.rotate((this.angle * Math.PI) / 180);
            ctx.drawImage(this.img, -this.img.width * scale / 2, -this.img.height * scale / 2, this.img.width * scale, this.img.height * scale);
            ctx.restore();
        },

        pointerStart(event) {
            if (!this.img) {
                return;
            }
            const p = event.touches ? event.touches[0] : event;
            this.pointer = { down: true, lastX: p.clientX, lastY: p.clientY };
            event.preventDefault();
        },

        pointerMove(event) {
            if (!this.pointer.down || !this.img) {
                return;
            }
            const p = event.touches ? event.touches[0] : event;
            this.panX += (p.clientX - this.pointer.lastX) * 2;
            this.panY += (p.clientY - this.pointer.lastY) * 2;
            this.pointer.lastX = p.clientX;
            this.pointer.lastY = p.clientY;
            this.draw();
            event.preventDefault();
        },

        pointerEnd() {
            this.pointer.down = false;
        },

        async accept() {
            if (!this.img) {
                return;
            }
            const blob = await this.exportBlob();
            if (!blob) {
                this.error = 'No se pudo recortar el logo.';
                return;
            }
            if (blob.size > MAX_BYTES) {
                this.error = 'El recorte supera 2 MB. Usa una imagen más simple.';
                return;
            }

            const mime = blob.type || 'image/png';
            const ext = mime.includes('jpeg') ? 'jpg' : 'png';
            const file = new File([blob], `logo.${ext}`, { type: mime });
            const dt = new DataTransfer();
            dt.items.add(file);
            this.$refs.logoInput.files = dt.files;
            this.preview = URL.createObjectURL(blob);
            this.removeLogo = false;
            this.closeEditor();
        },

        exportBlob() {
            return new Promise((resolve) => {
                const canvas = this.$refs.cropCanvas;
                const out = document.createElement('canvas');
                out.width = OUT_SIZE;
                out.height = OUT_SIZE;
                const ctx = out.getContext('2d');
                const ratio = OUT_SIZE / canvas.width;
                const { bw, bh } = this.bounds();
                const scale = Math.max(canvas.width / bw, canvas.height / bh) * ratio;
                ctx.fillStyle = '#ffffff';
                ctx.fillRect(0, 0, OUT_SIZE, OUT_SIZE);
                ctx.save();
                ctx.translate(OUT_SIZE / 2 + this.panX * ratio, OUT_SIZE / 2 + this.panY * ratio);
                ctx.rotate((this.angle * Math.PI) / 180);
                ctx.drawImage(this.img, -this.img.width * scale / 2, -this.img.height * scale / 2, this.img.width * scale, this.img.height * scale);
                ctx.restore();
                out.toBlob((blob) => resolve(blob), 'image/png');
            });
        },

        clear() {
            this.preview = '';
            this.removeLogo = this.hadServerLogo;
            const dt = new DataTransfer();
            this.$refs.logoInput.files = dt.files;
            this.error = '';
        },
    };
}
