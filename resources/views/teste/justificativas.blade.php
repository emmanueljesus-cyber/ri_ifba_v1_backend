@extends('teste.layout')

@section('title', 'Justificativas - RI IFBA')

@section('content')
<div x-data="justificativasApp()" x-init="loadJustificativas()">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">📝 Justificativas de Faltas</h1>
        <p class="text-gray-600 mt-1">Gerenciar justificativas enviadas pelos bolsistas (RF10)</p>
    </div>
    
    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-center">
            <p class="text-3xl font-bold text-yellow-700" x-text="stats.pendentes"></p>
            <p class="text-sm text-yellow-600">⏳ Pendentes</p>
        </div>
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
            <p class="text-3xl font-bold text-green-700" x-text="stats.aprovadas"></p>
            <p class="text-sm text-green-600">✅ Aprovadas</p>
        </div>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
            <p class="text-3xl font-bold text-red-700" x-text="stats.rejeitadas"></p>
            <p class="text-sm text-red-600">❌ Rejeitadas</p>
        </div>
    </div>
    
    <!-- Filtros -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <div class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700">Status</label>
                <select x-model="filtroStatus" @change="loadJustificativas()" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    <option value="">Todos</option>
                    <option value="pendente">Pendentes</option>
                    <option value="aprovada">Aprovadas</option>
                    <option value="rejeitada">Rejeitadas</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Tipo</label>
                <select x-model="filtroTipo" @change="loadJustificativas()" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                    <option value="">Todos</option>
                    <option value="antecipada">Antecipada</option>
                    <option value="posterior">Posterior</option>
                </select>
            </div>
            <button @click="loadJustificativas()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition">
                🔄 Atualizar
            </button>
        </div>
    </div>
    
    <!-- Loading -->
    <div x-show="loading" class="text-center py-8">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-500 border-t-transparent"></div>
    </div>
    
    <!-- Lista -->
    <div x-show="!loading" class="space-y-4">
        <template x-for="just in justificativas" :key="just.id">
            <div class="bg-white rounded-lg shadow p-4 hover:shadow-md transition">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <div class="flex items-center gap-3">
                            <span class="text-lg font-semibold text-gray-800" x-text="just.usuario.nome"></span>
                            <span class="text-sm text-gray-500">(<span x-text="just.usuario.matricula"></span>)</span>
                            
                            <!-- Status Badge -->
                            <span x-show="just.status === 'pendente'" class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">⏳ Pendente</span>
                            <span x-show="just.status === 'aprovada'" class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">✅ Aprovada</span>
                            <span x-show="just.status === 'rejeitada'" class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">❌ Rejeitada</span>
                        </div>
                        
                        <div class="mt-2 text-sm text-gray-600">
                            <p><strong>Refeição:</strong> <span x-text="just.refeicao ? just.refeicao.data + ' - ' + just.refeicao.turno : 'N/A'"></span></p>
                            <p><strong>Tipo:</strong> <span x-text="just.tipo"></span></p>
                            <p><strong>Motivo:</strong> <span x-text="just.motivo"></span></p>
                            <p class="text-xs text-gray-400 mt-1">Enviado em: <span x-text="just.enviado_em"></span></p>
                        </div>
                        
                        <div x-show="just.tem_anexo" class="mt-2">
                            <span class="text-blue-600 text-sm">📎 Possui anexo</span>
                        </div>
                        
                        <div x-show="just.avaliado_por" class="mt-2 text-xs text-gray-500">
                            Avaliado por <span x-text="just.avaliado_por"></span> em <span x-text="just.avaliado_em"></span>
                            <span x-show="just.motivo_rejeicao" class="block text-red-600">
                                Motivo: <span x-text="just.motivo_rejeicao"></span>
                            </span>
                        </div>
                    </div>
                    
                    <!-- Ações -->
                    <div x-show="just.status === 'pendente'" class="flex gap-2">
                        <button @click="aprovar(just.id)" 
                                class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700 transition">
                            ✅ Aprovar
                        </button>
                        <button @click="abrirModalRejeitar(just.id)"
                                class="bg-red-600 text-white px-3 py-1 rounded text-sm hover:bg-red-700 transition">
                            ❌ Rejeitar
                        </button>
                    </div>
                </div>
            </div>
        </template>
        
        <div x-show="justificativas.length === 0" class="text-center py-8 text-gray-500">
            Nenhuma justificativa encontrada
        </div>
    </div>
    
    <!-- Paginação -->
    <div x-show="!loading && meta.last_page > 1" class="mt-6 flex justify-center gap-2">
        <button @click="pagina--; loadJustificativas()" :disabled="pagina <= 1"
                class="px-3 py-1 bg-gray-200 rounded disabled:opacity-50">
            ← Anterior
        </button>
        <span class="px-3 py-1">
            Página <span x-text="pagina"></span> de <span x-text="meta.last_page"></span>
        </span>
        <button @click="pagina++; loadJustificativas()" :disabled="pagina >= meta.last_page"
                class="px-3 py-1 bg-gray-200 rounded disabled:opacity-50">
            Próxima →
        </button>
    </div>
    
    <!-- Modal Rejeitar -->
    <div x-show="modalRejeitar" x-cloak class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">❌ Rejeitar Justificativa</h3>
            
            <div>
                <label class="block text-sm font-medium text-gray-700">Motivo da rejeição (opcional)</label>
                <textarea x-model="motivoRejeicao" rows="3"
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                          placeholder="Informe o motivo..."></textarea>
            </div>
            
            <div class="mt-4 flex gap-2 justify-end">
                <button @click="modalRejeitar = false" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">
                    Cancelar
                </button>
                <button @click="rejeitar()" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                    Confirmar Rejeição
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function justificativasApp() {
    return {
        loading: false,
        justificativas: [],
        meta: {},
        stats: { pendentes: 0, aprovadas: 0, rejeitadas: 0 },
        
        filtroStatus: '',
        filtroTipo: '',
        pagina: 1,
        
        modalRejeitar: false,
        idRejeitar: null,
        motivoRejeicao: '',
        
        async loadJustificativas() {
            this.loading = true;
            try {
                let url = `/api/v1/admin/justificativas?page=${this.pagina}`;
                if (this.filtroStatus) url += `&status=${this.filtroStatus}`;
                if (this.filtroTipo) url += `&tipo=${this.filtroTipo}`;
                
                const response = await fetch(url);
                const json = await response.json();
                
                this.justificativas = json.data || [];
                this.meta = json.meta || {};
                this.stats = json.meta?.stats || { pendentes: 0, aprovadas: 0, rejeitadas: 0 };
            } catch (e) {
                console.error('Erro:', e);
            }
            this.loading = false;
        },
        
        async aprovar(id) {
            try {
                const response = await fetch(`/api/v1/admin/justificativas/${id}/aprovar`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json'
                    }
                });
                
                const json = await response.json();
                if (json.data) {
                    alert('✅ Justificativa aprovada!');
                    this.loadJustificativas();
                } else {
                    alert('Erro: ' + JSON.stringify(json.errors));
                }
            } catch (e) {
                alert('Erro: ' + e.message);
            }
        },
        
        abrirModalRejeitar(id) {
            this.idRejeitar = id;
            this.motivoRejeicao = '';
            this.modalRejeitar = true;
        },
        
        async rejeitar() {
            try {
                const response = await fetch(`/api/v1/admin/justificativas/${this.idRejeitar}/rejeitar`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ motivo: this.motivoRejeicao })
                });
                
                const json = await response.json();
                if (json.data) {
                    this.modalRejeitar = false;
                    alert('❌ Justificativa rejeitada!');
                    this.loadJustificativas();
                } else {
                    alert('Erro: ' + JSON.stringify(json.errors));
                }
            } catch (e) {
                alert('Erro: ' + e.message);
            }
        }
    }
}
</script>
@endpush
