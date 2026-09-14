@props([
    'login' => null,
    'password' => null,
])

@if (filled($login))
    <div
        x-data="{
            open: true,
            copied: false,
            login: @js($login),
            password: @js($password ?? ''),
            async copy() {
                const text = 'Usuario: ' + this.login + '\nContraseña: ' + this.password;
                await navigator.clipboard.writeText(text);
                this.copied = true;
                setTimeout(() => { this.copied = false }, 2000);
            }
        }"
        x-show="open"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 px-4"
    >
        <div class="w-full max-w-md space-y-3 rounded-xl border border-amber-400/40 bg-slate-900 p-5 text-center shadow-2xl">
            <p class="text-sm font-semibold text-amber-100">Copia este usuario y esta contraseña</p>
            <p class="text-xs text-slate-400">En el primer ingreso debe cambiar usuario y contraseña. La clave no se vuelve a mostrar.</p>
            <div class="space-y-1 rounded-lg border border-slate-800 bg-slate-950/70 px-3 py-3 text-left">
                <p class="text-sm text-slate-400">Usuario: <span class="font-mono text-white" x-text="login"></span></p>
                <p class="text-sm text-slate-400">Contraseña: <span class="font-mono text-white" x-text="password"></span></p>
            </div>
            <div class="flex justify-center gap-2">
                <button
                    type="button"
                    class="h-9 rounded-lg bg-slate-800 px-4 text-xs font-semibold text-white"
                    @click="copy()"
                >
                    <span x-text="copied ? 'Copiado' : 'Copiar ambos'"></span>
                </button>
                <button
                    type="button"
                    class="h-9 rounded-lg bg-violet-600 px-4 text-xs font-semibold text-white"
                    @click="open = false"
                >
                    Entendido
                </button>
            </div>
        </div>
    </div>
@endif
