@extends('teste.layout')

@section('title', 'Dashboard - RI IFBA')

@section('content')
<div x-data="dashboardApp()" x-init="loadData()">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">📊 Dashboard de Utilização</h1>
        <p class="text-gray-600 mt-1">Estatísticas do Restaurante Institucional</p>
    </div>
    
    <!-- Filtros -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <div class="flex flex-wrap gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700">Mês</label>
                <select x-model="mes" @change="loadData()" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="1">Janeiro</option>
                    <option value="2">Fevereiro</option>
                    <option value="3">Março</option>
                    <option value="4">Abril</option>
                    <option value="5">Maio</option>
                    <option value="6">Junho</option>
                    <option value="7">Julho</option>
                    <option value="8">Agosto</option>
                    <option value="9">Setembro</option>
                    <option value="10">Outubro</option>
                    <option value="11">Novembro</option>
                    <option value="12">Dezembro</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Ano</label>
                <select x-model="ano" @change="loadData()" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="2025">2025</option>
                    <option value="2026">2026</option>
                </select>
            </div>
            <button @click="loadData()" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition">
                🔄 Atualizar
            </button>
        </div>
    </div>
    
    <!-- Loading -->
    <div x-show="loading" class="text-center py-8">
        <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-500 border-t-transparent"></div>
        <p class="mt-2 text-gray-600">Carregando dados...</p>
    </div>
    
    <!-- Cards Resumo -->
    <div x-show="!loading" x-cloak class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        <!-- Card Bolsistas -->
        <div class="bg-white rounded-lg shadow p-6 card-hover transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Bolsistas Ativos</p>
                    <p class="text-3xl font-bold text-blue-600" x-text="dados.resumo?.bolsistas_ativos || 0"></p>
                </div>
                <div class="bg-blue-100 rounded-full p-3">
                    <span class="text-2xl">👥</span>
                </div>
            </div>
            <p class="mt-2 text-sm text-gray-500">
                Total: <span x-text="dados.resumo?.total_bolsistas || 0"></span>
            </p>
        </div>
        
        <!-- Card Taxa Presença -->
        <div class="bg-white rounded-lg shadow p-6 card-hover transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Taxa de Presença</p>
                    <p class="text-3xl font-bold text-green-600" x-text="(dados.taxa_presenca?.valor || 0) + '%'"></p>
                </div>
                <div class="bg-green-100 rounded-full p-3">
                    <span class="text-2xl">✅</span>
                </div>
            </div>
            <p class="mt-2 text-sm" :class="dados.taxa_presenca?.comparativo_anterior?.startsWith('+') ? 'text-green-600' : 'text-red-600'">
                <span x-text="dados.taxa_presenca?.comparativo_anterior || '0%'"></span> vs mês anterior
            </p>
        </div>
        
        <!-- Card Faltas -->
        <div class="bg-white rounded-lg shadow p-6 card-hover transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Faltas no Período</p>
                    <p class="text-3xl font-bold text-red-600" x-text="dados.faltas?.total || 0"></p>
                </div>
                <div class="bg-red-100 rounded-full p-3">
                    <span class="text-2xl">❌</span>
                </div>
            </div>
            <p class="mt-2 text-sm text-gray-500">
                Justificadas: <span x-text="dados.faltas?.justificadas || 0"></span> |
                Injustificadas: <span x-text="dados.faltas?.injustificadas || 0"></span>
            </p>
        </div>
        
        <!-- Card Extras -->
        <div class="bg-white rounded-lg shadow p-6 card-hover transition">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Extras Atendidos</p>
                    <p class="text-3xl font-bold text-purple-600" x-text="dados.extras?.atendidos || 0"></p>
                </div>
                <div class="bg-purple-100 rounded-full p-3">
                    <span class="text-2xl">🎫</span>
                </div>
            </div>
            <p class="mt-2 text-sm text-gray-500">
                Taxa: <span x-text="dados.extras?.taxa_atendimento || '0%'"></span>
            </p>
        </div>
    </div>
    
    <!-- Gráfico de distribuição -->
    <div x-show="!loading" x-cloak class="bg-white rounded-lg shadow p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">📈 Distribuição de Status</h3>
        
        <div class="flex flex-wrap gap-8">
            <!-- Barra de presença -->
            <div class="flex-1 min-w-64">
                <div class="flex justify-between text-sm text-gray-600 mb-2">
                    <span>Confirmados</span>
                    <span x-text="dados.taxa_presenca?.total_confirmados || 0"></span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-4">
                    <div class="bg-green-500 h-4 rounded-full transition-all" 
                         :style="'width: ' + (dados.taxa_presenca?.valor || 0) + '%'"></div>
                </div>
            </div>
            
            <!-- Barra de faltas justificadas -->
            <div class="flex-1 min-w-64">
                <div class="flex justify-between text-sm text-gray-600 mb-2">
                    <span>Faltas Justificadas</span>
                    <span x-text="dados.faltas?.percentual_justificadas || '0%'"></span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-4">
                    <div class="bg-yellow-500 h-4 rounded-full transition-all"
                         :style="'width: ' + (parseFloat(dados.faltas?.percentual_justificadas) || 0) + '%'"></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Debug Info -->
    <div x-show="!loading && error" class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
        <h4 class="font-semibold text-red-800">⚠️ Erro ao carregar dados</h4>
        <p class="text-red-600 text-sm" x-text="error"></p>
        <p class="text-gray-500 text-sm mt-2">Verifique se a API está rodando e se existem dados no banco.</p>
    </div>
    
    <!-- Raw Data (Debug) -->
    <details x-show="!loading" class="bg-gray-50 rounded-lg p-4">
        <summary class="cursor-pointer font-medium text-gray-700">🔧 Debug: Dados brutos da API</summary>
        <pre class="mt-2 text-xs overflow-x-auto bg-gray-800 text-green-400 p-4 rounded" x-text="JSON.stringify(dados, null, 2)"></pre>
    </details>
</div>
@endsection

@push('scripts')
<script>
function dashboardApp() {
    return {
        loading: true,
        error: null,
        mes: new Date().getMonth() + 1,
        ano: new Date().getFullYear(),
        dados: {},
        
        async loadData() {
            this.loading = true;
            this.error = null;
            
            try {
                const response = await fetch(`/api/v1/admin/dashboard?mes=${this.mes}&ano=${this.ano}`);
                const json = await response.json();
                
                if (json.errors && Object.keys(json.errors).length > 0) {
                    this.error = JSON.stringify(json.errors);
                } else {
                    this.dados = json.data || {};
                }
            } catch (e) {
                this.error = e.message;
                console.error('Erro:', e);
            }
            
            this.loading = false;
        }
    }
}
</script>
@endpush
