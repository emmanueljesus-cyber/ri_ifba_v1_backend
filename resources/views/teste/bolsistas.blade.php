@extends('teste.layout')

@section('title', 'Bolsistas - RI IFBA')

@section('content')
<div x-data="bolsistasApp()" x-init="loadBolsistas()">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">👥 Gerenciar Bolsistas</h1>
            <p class="text-gray-600 mt-1">Lista e importação de bolsistas</p>
        </div>
    </div>

    <!-- Tabs -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                <button @click="tab = 'lista'" 
                        :class="tab === 'lista' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition">
                    📋 Lista de Bolsistas
                </button>
                <button @click="tab = 'importar'"
                        :class="tab === 'importar' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition">
                    📤 Importar Excel/CSV (RF15)
                </button>
                <button @click="tab = 'dia'"
                        :class="tab === 'dia' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition">
                    📅 Bolsistas do Dia
                </button>
            </nav>
        </div>
    </div>

    <!-- Tab: Lista de Bolsistas -->
    <div x-show="tab === 'lista'" x-cloak>
        <!-- Filtros -->
        <div class="bg-white rounded-lg shadow p-4 mb-4">
            <div class="flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-48">
                    <label class="block text-sm font-medium text-gray-700">Buscar</label>
                    <input type="text" x-model="search" @input.debounce.500ms="loadBolsistas()"
                           placeholder="Nome, matrícula ou email..."
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <select x-model="ativo" @change="loadBolsistas()" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="">Todos</option>
                        <option value="true">Ativos</option>
                        <option value="false">Inativos</option>
                    </select>
                </div>
                <button @click="loadBolsistas()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition">
                    🔄 Atualizar
                </button>
            </div>
        </div>

        <!-- Loading -->
        <div x-show="loading" class="text-center py-8">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-500 border-t-transparent"></div>
        </div>

        <!-- Tabela -->
        <div x-show="!loading" class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Matrícula</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nome</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Curso</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Turno</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Dias</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="bolsista in bolsistas" :key="bolsista.user_id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="bolsista.matricula"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="bolsista.nome"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="bolsista.curso || '-'"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="bolsista.turno || '-'"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="bolsista.dias_semana_texto || '-'"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span x-show="bolsista.ativo" class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Ativo</span>
                                <span x-show="!bolsista.ativo" class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Inativo</span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
            
            <div x-show="bolsistas.length === 0" class="text-center py-8 text-gray-500">
                Nenhum bolsista encontrado
            </div>
        </div>

        <!-- Meta info -->
        <div x-show="!loading && meta.total" class="mt-4 text-sm text-gray-600">
            Total: <span x-text="meta.total"></span> bolsistas |
            Ativos: <span x-text="meta.ativos"></span> |
            Inativos: <span x-text="meta.inativos"></span>
        </div>
    </div>

    <!-- Tab: Importar -->
    <div x-show="tab === 'importar'" x-cloak>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">📤 Importar Bolsistas via Excel/CSV (RF15)</h3>
            
            <!-- Formato esperado -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                <h4 class="font-medium text-blue-800 mb-2">📋 Formato esperado do arquivo:</h4>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-blue-100">
                            <tr>
                                <th class="px-3 py-2 text-left">matricula</th>
                                <th class="px-3 py-2 text-left">nome</th>
                                <th class="px-3 py-2 text-left">email</th>
                                <th class="px-3 py-2 text-left">curso</th>
                                <th class="px-3 py-2 text-left">turno</th>
                                <th class="px-3 py-2 text-left">dias_semana</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            <tr>
                                <td class="px-3 py-2 border">20231234</td>
                                <td class="px-3 py-2 border">João Silva</td>
                                <td class="px-3 py-2 border">joao@ifba.edu.br</td>
                                <td class="px-3 py-2 border">Informática</td>
                                <td class="px-3 py-2 border">almoco</td>
                                <td class="px-3 py-2 border">1,2,3,4,5</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p class="mt-2 text-xs text-blue-700">
                    * dias_semana: 0=Dom, 1=Seg, 2=Ter, 3=Qua, 4=Qui, 5=Sex, 6=Sab
                </p>
            </div>
            
            <!-- Form de upload -->
            <form @submit.prevent="uploadFile()" class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Arquivo Excel/CSV</label>
                    <input type="file" @change="file = $event.target.files[0]" accept=".xlsx,.xls,.csv"
                           class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                </div>
                
                <div class="flex gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Turno padrão (opcional)</label>
                        <select x-model="turnoPadrao" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">Conforme arquivo</option>
                            <option value="almoco">Almoço</option>
                            <option value="jantar">Jantar</option>
                        </select>
                    </div>
                    <div class="flex items-center mt-6">
                        <input type="checkbox" x-model="atualizarExistentes" id="atualizar" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <label for="atualizar" class="ml-2 text-sm text-gray-700">Atualizar existentes</label>
                    </div>
                </div>
                
                <button type="submit" :disabled="!file || uploading"
                        class="bg-green-600 text-white px-6 py-2 rounded-md hover:bg-green-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                    <span x-show="!uploading">📤 Importar</span>
                    <span x-show="uploading">⏳ Processando...</span>
                </button>
            </form>
            
            <!-- Resultado -->
            <div x-show="resultado" x-cloak class="mt-6">
                <h4 class="font-medium text-gray-800 mb-2">Resultado da importação:</h4>
                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                        <div class="bg-green-100 rounded p-3">
                            <p class="text-2xl font-bold text-green-700" x-text="resultado.meta?.total_criados || 0"></p>
                            <p class="text-sm text-green-600">Criados</p>
                        </div>
                        <div class="bg-blue-100 rounded p-3">
                            <p class="text-2xl font-bold text-blue-700" x-text="resultado.meta?.total_atualizados || 0"></p>
                            <p class="text-sm text-blue-600">Atualizados</p>
                        </div>
                        <div class="bg-red-100 rounded p-3">
                            <p class="text-2xl font-bold text-red-700" x-text="resultado.meta?.total_erros || 0"></p>
                            <p class="text-sm text-red-600">Erros</p>
                        </div>
                        <div class="bg-gray-200 rounded p-3">
                            <p class="text-2xl font-bold text-gray-700" x-text="resultado.meta?.total_linhas || 0"></p>
                            <p class="text-sm text-gray-600">Total Linhas</p>
                        </div>
                    </div>
                    
                    <div x-show="resultado.errors && resultado.errors.length > 0" class="mt-4">
                        <h5 class="text-sm font-medium text-red-800">Erros encontrados:</h5>
                        <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                            <template x-for="err in resultado.errors" :key="err.linha">
                                <li>Linha <span x-text="err.linha"></span>: <span x-text="err.erro"></span></li>
                            </template>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab: Bolsistas do Dia -->
    <div x-show="tab === 'dia'" x-cloak>
        <div class="bg-white rounded-lg shadow p-4 mb-4">
            <div class="flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Data</label>
                    <input type="date" x-model="dataDia" @change="loadBolsistasDia()"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Turno</label>
                    <select x-model="turnoDia" @change="loadBolsistasDia()" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="">Todos</option>
                        <option value="almoco">Almoço</option>
                        <option value="jantar">Jantar</option>
                    </select>
                </div>
                <button @click="loadBolsistasDia()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition">
                    🔄 Carregar
                </button>
            </div>
        </div>

        <div x-show="loadingDia" class="text-center py-8">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-500 border-t-transparent"></div>
        </div>

        <div x-show="!loadingDia" class="bg-white rounded-lg shadow overflow-hidden">
            <div class="p-4 bg-gray-50 border-b">
                <p class="text-sm text-gray-600">
                    📅 <span x-text="metaDia.data"></span> (<span x-text="metaDia.dia_semana_texto"></span>) |
                    Total: <span x-text="metaDia.total_bolsistas"></span> bolsistas
                </p>
            </div>
            
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Matrícula</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nome</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Curso</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status Presença</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <template x-for="bolsista in bolsistasDia" :key="bolsista.user_id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="bolsista.matricula"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="bolsista.nome"></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="bolsista.curso || '-'"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span x-show="bolsista.status_presenca === 'confirmado'" class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">✅ Confirmado</span>
                                <span x-show="bolsista.status_presenca === 'pendente'" class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">⏳ Pendente</span>
                                <span x-show="bolsista.status_presenca === 'falta_justificada'" class="px-2 py-1 text-xs font-semibold rounded-full bg-orange-100 text-orange-800">📝 Falta Just.</span>
                                <span x-show="bolsista.status_presenca === 'falta_injustificada'" class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">❌ Falta Injust.</span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
            
            <div x-show="bolsistasDia.length === 0" class="text-center py-8 text-gray-500">
                Nenhum bolsista cadastrado para este dia
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function bolsistasApp() {
    return {
        tab: 'lista',
        loading: false,
        loadingDia: false,
        uploading: false,
        
        // Lista geral
        bolsistas: [],
        meta: {},
        search: '',
        ativo: '',
        
        // Importação
        file: null,
        turnoPadrao: '',
        atualizarExistentes: true,
        resultado: null,
        
        // Dia
        bolsistasDia: [],
        metaDia: {},
        dataDia: new Date().toISOString().split('T')[0],
        turnoDia: '',
        
        async loadBolsistas() {
            this.loading = true;
            try {
                let url = '/api/v1/admin/bolsistas?';
                if (this.search) url += `search=${encodeURIComponent(this.search)}&`;
                if (this.ativo) url += `ativo=${this.ativo}`;
                
                const response = await fetch(url);
                const json = await response.json();
                this.bolsistas = json.data || [];
                this.meta = json.meta || {};
            } catch (e) {
                console.error('Erro:', e);
            }
            this.loading = false;
        },
        
        async loadBolsistasDia() {
            this.loadingDia = true;
            try {
                let url = `/api/v1/admin/bolsistas/dia?data=${this.dataDia}`;
                if (this.turnoDia) url += `&turno=${this.turnoDia}`;
                
                const response = await fetch(url);
                const json = await response.json();
                this.bolsistasDia = json.data || [];
                this.metaDia = json.meta || {};
            } catch (e) {
                console.error('Erro:', e);
            }
            this.loadingDia = false;
        },
        
        async uploadFile() {
            if (!this.file) return;
            
            this.uploading = true;
            this.resultado = null;
            
            try {
                const formData = new FormData();
                formData.append('file', this.file);
                if (this.turnoPadrao) formData.append('turno_padrao', this.turnoPadrao);
                formData.append('atualizar_existentes', this.atualizarExistentes ? '1' : '0');
                
                const response = await fetch('/api/v1/admin/bolsistas/import', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                this.resultado = await response.json();
                
                if (this.resultado.meta?.total_criados > 0 || this.resultado.meta?.total_atualizados > 0) {
                    this.loadBolsistas();
                }
            } catch (e) {
                this.resultado = { errors: [{ linha: 0, erro: e.message }], meta: {} };
            }
            
            this.uploading = false;
        }
    }
}
</script>
@endpush
