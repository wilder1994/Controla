<x-access-layout>
    <div class="-mt-6 -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 pt-6 pb-8 bg-gradient-to-r from-slate-800 to-indigo-900 mb-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-indigo-300">Control de Acceso</p>
                <h2 class="text-xl font-bold text-white">Registrar Ingreso</h2>
            </div>
            <a href="{{ route('access.logs.index') }}" class="text-sm text-indigo-300 hover:text-white transition-colors">← Volver</a>
        </div>
    </div>

    <div class="max-w-4xl">
        <div class="bg-slate-900 rounded-xl border border-slate-800 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-800">
                <div x-data="{ type: 'visitor' }">
                    <label class="block text-sm font-semibold text-slate-300 mb-3">Tipo de Ingreso</label>
                    <div class="flex gap-3">
                        <label class="relative flex-1 cursor-pointer">
                            <input type="radio" x-model="type" value="visitor" class="sr-only peer">
                            <div class="p-3 border-2 rounded-xl text-center transition-all peer-checked:border-emerald-500 peer-checked:bg-emerald-900/30 border-slate-700 hover:border-slate-600 bg-slate-800">
                                <svg class="w-6 h-6 mx-auto text-slate-500 peer-checked:text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                <p class="mt-1 text-sm font-medium text-slate-300 peer-checked:text-emerald-300">Visitante peatonal</p>
                            </div>
                        </label>
                        <label class="relative flex-1 cursor-pointer">
                            <input type="radio" x-model="type" value="visitor_vehicle" class="sr-only peer">
                            <div class="p-3 border-2 rounded-xl text-center transition-all peer-checked:border-cyan-500 peer-checked:bg-cyan-900/30 border-slate-700 hover:border-slate-600 bg-slate-800">
                                <svg class="w-6 h-6 mx-auto text-slate-500 peer-checked:text-cyan-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10m0 0V21h14v-5m0 0h3l2-4-2-4h-3l-2 4h-1"/></svg>
                                <p class="mt-1 text-sm font-medium text-slate-300 peer-checked:text-cyan-300">Visitante vehicular</p>
                            </div>
                        </label>
                    </div>

                    <hr class="my-6 border-slate-800">

                    <form method="POST" action="{{ route('access.logs.entry.store') }}" x-show="type === 'visitor'" x-data="entryForm()">
                        @csrf
                        <input type="hidden" name="access_type" value="visitor">

                        <div class="bg-slate-800 rounded-xl p-4 mb-6">
                            <label class="block text-sm font-semibold text-slate-300 mb-2">Buscar Visitante</label>

                            <div class="mb-3 p-3 bg-slate-950/50 border border-slate-700/50 rounded-lg">
                                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Escáner QR Cédula</label>
                                @include('modules.access.partials.qr-scan-field')
                            </div>

                            <div class="relative">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                <input type="text" x-model="search" @input.debounce="searchVisitor()" placeholder="Buscar por documento o nombre..." class="block w-full pl-10 rounded-lg bg-slate-950 border-slate-700 text-white focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <input type="hidden" name="visitor_id" x-model="selectedVisitorId">
                            <div x-show="searchResults.length > 0 && !selectedVisitorId" class="mt-2 bg-slate-900 border border-slate-700 rounded-xl overflow-hidden divide-y divide-slate-800">
                                <template x-for="v in searchResults" :key="v.id">
                                    <div @click="selectVisitor(v)" class="px-4 py-3 hover:bg-indigo-900/30 cursor-pointer flex items-center justify-between transition-colors">
                                        <div>
                                            <p class="text-sm font-medium text-white" x-text="v.first_name + ' ' + v.last_name"></p>
                                            <p class="text-xs text-slate-500" x-text="v.document_type + ' ' + v.document_number"></p>
                                        </div>
                                        <span x-show="v.company" class="text-xs text-slate-500" x-text="v.company"></span>
                                    </div>
                                </template>
                            </div>
                            <div x-show="selectedVisitor" class="mt-3 p-3 bg-emerald-900/30 border border-emerald-800 rounded-xl flex items-center justify-between">
                                <p class="text-sm font-medium text-emerald-300" x-text="selectedVisitorLabel"></p>
                                <button type="button" @click="clearSelection()" class="text-xs text-red-400 hover:text-red-300 font-medium">Cambiar</button>
                            </div>
                            <div class="mt-2">
                                <a href="{{ route('access.visitors.create') }}" target="_blank" class="text-sm text-indigo-400 hover:text-indigo-300 font-medium inline-flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                    Crear nuevo visitante
                                </a>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-300">Anfitrión</label>
                                <select name="host_id" required class="mt-1 block w-full rounded-lg bg-slate-950 border-slate-700 text-white focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Seleccionar...</option>
                                    @foreach($hosts as $host)
                                    <option value="{{ $host->id }}">{{ $host->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-300">Ubicación</label>
                                <select name="location_id" required class="mt-1 block w-full rounded-lg bg-slate-950 border-slate-700 text-white focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Seleccionar...</option>
                                    @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-300">Apartamento Destino</label>
                                <select name="housing_unit_id" class="mt-1 block w-full rounded-lg bg-slate-950 border-slate-700 text-white focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">No aplica</option>
                                    @foreach($housingUnits as $hu)
                                    <option value="{{ $hu->id }}">{{ $hu->full_label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-300">Propósito</label>
                                <input type="text" name="purpose" class="mt-1 block w-full rounded-lg bg-slate-950 border-slate-700 text-white focus:border-indigo-500 focus:ring-indigo-500" placeholder="Reunión, entrega, etc.">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-300">Empresa a Visitar</label>
                                <input type="text" name="company_visited" class="mt-1 block w-full rounded-lg bg-slate-950 border-slate-700 text-white focus:border-indigo-500 focus:ring-indigo-500" placeholder="Opcional">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-300">Temperatura (°C)</label>
                                <input type="number" step="0.1" name="screening_temp" class="mt-1 block w-full rounded-lg bg-slate-950 border-slate-700 text-white focus:border-indigo-500 focus:ring-indigo-500" placeholder="36.5">
                            </div>
                        </div>
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-slate-300">Notas</label>
                            <textarea name="notes" rows="2" class="mt-1 block w-full rounded-lg bg-slate-950 border-slate-700 text-white focus:border-indigo-500 focus:ring-indigo-500" placeholder="Observaciones adicionales..."></textarea>
                        </div>
                        <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-slate-800">
                            <a href="{{ route('access.logs.index') }}" class="inline-flex items-center px-4 py-2 bg-slate-800 border border-slate-700 rounded-lg font-semibold text-xs text-slate-300 hover:bg-slate-700 transition-colors">Cancelar</a>
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-500 transition-colors shadow-sm">
                                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                Registrar Ingreso
                            </button>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('access.logs.entry.store') }}" x-show="type === 'visitor_vehicle'" x-data="visitorVehicleForm()">
                        @csrf
                        <input type="hidden" name="access_type" value="visitor_vehicle">

                        <div class="bg-slate-800 rounded-xl p-4 mb-6">
                            <label class="block text-sm font-semibold text-slate-300 mb-2">Buscar Visitante</label>

                            <div class="mb-3 p-3 bg-slate-950/50 border border-slate-700/50 rounded-lg">
                                <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Escáner QR Cédula</label>
                                @include('modules.access.partials.qr-scan-field')
                            </div>

                            <div class="relative">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                                <input type="text" x-model="vsearch" @input.debounce="searchVisitor()" placeholder="Buscar por documento o nombre..." class="block w-full pl-10 rounded-lg bg-slate-950 border-slate-700 text-white focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <input type="hidden" name="visitor_id" x-model="selectedVisitorId">
                            <div x-show="visitorResults.length > 0 && !selectedVisitorId" class="mt-2 bg-slate-900 border border-slate-700 rounded-xl overflow-hidden divide-y divide-slate-800">
                                <template x-for="v in visitorResults" :key="v.id">
                                    <div @click="selectVisitor(v)" class="px-4 py-3 hover:bg-indigo-900/30 cursor-pointer flex items-center justify-between transition-colors">
                                        <div>
                                            <p class="text-sm font-medium text-white" x-text="v.first_name + ' ' + v.last_name"></p>
                                            <p class="text-xs text-slate-500" x-text="v.document_type + ' ' + v.document_number"></p>
                                        </div>
                                        <span x-show="v.company" class="text-xs text-slate-500" x-text="v.company"></span>
                                    </div>
                                </template>
                            </div>
                            <div x-show="selectedVisitor" class="mt-3 p-3 bg-emerald-900/30 border border-emerald-800 rounded-xl flex items-center justify-between">
                                <p class="text-sm font-medium text-emerald-300" x-text="selectedVisitorLabel"></p>
                                <button type="button" @click="clearVisitor()" class="text-xs text-red-400 hover:text-red-300 font-medium">Cambiar</button>
                            </div>
                            <div class="mt-2">
                                <a href="{{ route('access.visitors.create') }}" target="_blank" class="text-sm text-indigo-400 hover:text-indigo-300 font-medium inline-flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                    Crear nuevo visitante
                                </a>
                            </div>
                        </div>

                        <div class="bg-cyan-900/30 rounded-xl p-4 mb-6">
                            <label class="block text-sm font-semibold text-slate-300 mb-2">Buscar Vehículo por Placa</label>
                            <div class="relative">
                                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10m0 0V21h14v-5m0 0h3l2-4-2-4h-3l-2 4h-1"/></svg>
                                <input type="text" x-model="psearch" @input.debounce="searchPlate()" placeholder="Buscar por placa..." class="block w-full pl-10 rounded-lg bg-slate-950 border-slate-700 text-white focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <input type="hidden" name="vehicle_id" x-model="selectedVehicleId">
                            <div x-show="plateResults.length > 0 && !selectedVehicleId" class="mt-2 bg-slate-900 border border-slate-700 rounded-xl overflow-hidden divide-y divide-slate-800">
                                <template x-for="v in plateResults" :key="v.id">
                                    <div @click="selectVehicle(v)" class="px-4 py-3 hover:bg-cyan-900/30 cursor-pointer flex items-center justify-between transition-colors">
                                        <div>
                                            <p class="text-sm font-medium text-white" x-text="v.plate"></p>
                                            <p class="text-xs text-slate-500" x-text="v.brand + ' ' + v.model + ' (' + v.color + ')'"></p>
                                        </div>
                                        <span x-show="v.visitor" class="text-xs text-slate-500" x-text="v.visitor.first_name + ' ' + v.visitor.last_name"></span>
                                    </div>
                                </template>
                            </div>
                            <div x-show="selectedVehicle" class="mt-3 p-3 bg-cyan-900/30 border border-cyan-800 rounded-xl flex items-center justify-between">
                                <p class="text-sm font-medium text-cyan-300" x-text="selectedPlateLabel"></p>
                                <button type="button" @click="clearVehicle()" class="text-xs text-red-400 hover:text-red-300 font-medium">Cambiar</button>
                            </div>
                            <div class="mt-2">
                                <a href="{{ route('access.vehicles.create') }}" target="_blank" class="text-sm text-indigo-400 hover:text-indigo-300 font-medium inline-flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                    Registrar nuevo vehículo
                                </a>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-300">Anfitrión</label>
                                <select name="host_id" required class="mt-1 block w-full rounded-lg bg-slate-950 border-slate-700 text-white focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Seleccionar...</option>
                                    @foreach($hosts as $host)
                                    <option value="{{ $host->id }}">{{ $host->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-300">Ubicación</label>
                                <select name="location_id" required class="mt-1 block w-full rounded-lg bg-slate-950 border-slate-700 text-white focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Seleccionar...</option>
                                    @foreach($locations as $loc)
                                    <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-300">Apartamento Destino</label>
                                <select name="housing_unit_id" class="mt-1 block w-full rounded-lg bg-slate-950 border-slate-700 text-white focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">No aplica</option>
                                    @foreach($housingUnits as $hu)
                                    <option value="{{ $hu->id }}">{{ $hu->full_label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-300">Propósito</label>
                                <input type="text" name="purpose" class="mt-1 block w-full rounded-lg bg-slate-950 border-slate-700 text-white focus:border-indigo-500 focus:ring-indigo-500" placeholder="Reunión, entrega, etc.">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-300">Empresa a Visitar</label>
                                <input type="text" name="company_visited" class="mt-1 block w-full rounded-lg bg-slate-950 border-slate-700 text-white focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-300">Temperatura (°C)</label>
                                <input type="number" step="0.1" name="screening_temp" class="mt-1 block w-full rounded-lg bg-slate-950 border-slate-700 text-white focus:border-indigo-500 focus:ring-indigo-500" placeholder="36.5">
                            </div>
                        </div>
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-slate-300">Notas</label>
                            <textarea name="notes" rows="2" class="mt-1 block w-full rounded-lg bg-slate-950 border-slate-700 text-white focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                        </div>
                        <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-slate-800">
                            <a href="{{ route('access.logs.index') }}" class="inline-flex items-center px-4 py-2 bg-slate-800 border border-slate-700 rounded-lg font-semibold text-xs text-slate-300 hover:bg-slate-700 transition-colors">Cancelar</a>
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-emerald-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-emerald-500 transition-colors shadow-sm">
                                <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                                Registrar Ingreso
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function entryForm() {
            return {
                scanBuffer: '',
                search: '',
                searchResults: [],
                selectedVisitorId: null,
                selectedVisitor: null,
                get selectedVisitorLabel() {
                    if (!this.selectedVisitor) return '';
                    return this.selectedVisitor.document_type + ' ' + this.selectedVisitor.document_number + ' - ' + this.selectedVisitor.first_name + ' ' + this.selectedVisitor.last_name;
                },
                async searchVisitor() {
                    if (this.search.length < 2) { this.searchResults = []; return; }
                    try {
                        const res = await fetch('{{ route("access.visitors.search.json") }}?q=' + encodeURIComponent(this.search));
                        this.searchResults = await res.json();
                    } catch(e) { this.searchResults = []; }
                },
                selectVisitor(v) {
                    this.selectedVisitor = v;
                    this.selectedVisitorId = v.id;
                    this.search = v.document_number + ' - ' + v.first_name + ' ' + v.last_name;
                    this.searchResults = [];
                },
                clearSelection() {
                    this.selectedVisitor = null;
                    this.selectedVisitorId = null;
                    this.search = '';
                    this.searchResults = [];
                },
                async handleScan() {
                    let parts = this.scanBuffer.trim().split(/[|\t]/);
                    if (parts.length < 5) return;
                    let numero = parts[0], apellido1 = parts[1] || '', apellido2 = parts[2] || '';
                    let nombre1 = parts[3] || '', nombre2 = parts[4] || '';
                    let nombreCompleto = (nombre1 + ' ' + nombre2).trim();
                    let apellidoCompleto = (apellido1 + ' ' + apellido2).trim();
                    this.scanBuffer = '';
                    try {
                        const res = await fetch('{{ route("access.visitors.scan-register") }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                            body: JSON.stringify({ document_number: numero, first_name: nombreCompleto, last_name: apellidoCompleto })
                        });
                        const data = await res.json();
                        this.selectVisitor(data.visitor);
                    } catch(e) {}
                }
            }
        }

        function visitorVehicleForm() {
            return {
                scanBuffer: '',
                vsearch: '',
                visitorResults: [],
                selectedVisitorId: null,
                selectedVisitor: null,
                psearch: '',
                plateResults: [],
                selectedVehicleId: null,
                selectedVehicle: null,
                get selectedVisitorLabel() {
                    if (!this.selectedVisitor) return '';
                    return this.selectedVisitor.document_type + ' ' + this.selectedVisitor.document_number + ' - ' + this.selectedVisitor.first_name + ' ' + this.selectedVisitor.last_name;
                },
                get selectedPlateLabel() {
                    if (!this.selectedVehicle) return '';
                    let label = this.selectedVehicle.plate;
                    if (this.selectedVehicle.visitor) label += ' - ' + this.selectedVehicle.visitor.first_name + ' ' + this.selectedVehicle.visitor.last_name;
                    return label;
                },
                async searchVisitor() {
                    if (this.vsearch.length < 2) { this.visitorResults = []; return; }
                    try {
                        const res = await fetch('{{ route("access.visitors.search.json") }}?q=' + encodeURIComponent(this.vsearch));
                        this.visitorResults = await res.json();
                    } catch(e) { this.visitorResults = []; }
                },
                selectVisitor(v) {
                    this.selectedVisitor = v;
                    this.selectedVisitorId = v.id;
                    this.vsearch = v.document_number + ' - ' + v.first_name + ' ' + v.last_name;
                    this.visitorResults = [];
                },
                clearVisitor() {
                    this.selectedVisitor = null;
                    this.selectedVisitorId = null;
                    this.vsearch = '';
                    this.visitorResults = [];
                },
                async searchPlate() {
                    if (this.psearch.length < 1) { this.plateResults = []; return; }
                    try {
                        const res = await fetch('{{ route("access.vehicles.search.json") }}?q=' + encodeURIComponent(this.psearch));
                        this.plateResults = await res.json();
                    } catch(e) { this.plateResults = []; }
                },
                selectVehicle(v) {
                    this.selectedVehicle = v;
                    this.selectedVehicleId = v.id;
                    this.psearch = v.plate;
                    this.plateResults = [];
                },
                clearVehicle() {
                    this.selectedVehicle = null;
                    this.selectedVehicleId = null;
                    this.psearch = '';
                    this.plateResults = [];
                },
                async handleScan() {
                    let parts = this.scanBuffer.trim().split(/[|\t]/);
                    if (parts.length < 5) return;
                    let numero = parts[0], apellido1 = parts[1] || '', apellido2 = parts[2] || '';
                    let nombre1 = parts[3] || '', nombre2 = parts[4] || '';
                    let nombreCompleto = (nombre1 + ' ' + nombre2).trim();
                    let apellidoCompleto = (apellido1 + ' ' + apellido2).trim();
                    this.scanBuffer = '';
                    try {
                        const res = await fetch('{{ route("access.visitors.scan-register") }}', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                            body: JSON.stringify({ document_number: numero, first_name: nombreCompleto, last_name: apellidoCompleto })
                        });
                        const data = await res.json();
                        this.selectVisitor(data.visitor);
                    } catch(e) {}
                }
            }
        }
    </script>
    @endpush
</x-access-layout>
