@extends('teste.layout')

@section('title', 'Relatórios - RI IFBA')

@section('content')
<div x-data="relatoriosApp()">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800">📈 Relatórios</h1>
        <p class="text-gray-600 mt-1">Gerar relatórios por período para prestação de contas (RF12)</p>
    </div>

    <!-- Tabs -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8 px-6">
                <button @click="tab = 'semanal'" 
                        :class="tab === 'semanal' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500'"
                        class="py-4 px-1 border-b-2 font-medium text-sm transition">
                    📅 Mensal por Semanas
                </button>
                <button @click="tab = 'presencas'" 
                        :class="tab === 'presencas' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500'"
                        class="py-4 px-1 border-b-2 font-medium text-sm transition">
                    📊 Presenças por Período
                </button>
                <button @click="tab = 'mensal'"
                        :class="tab === 'mensal' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500'"
                        class="py-4 px-1 border-b-2 font-medium text-sm transition">
                    📈 Resumo Mensal
                </button>
                <button @click="tab = 'bolsista'"
                        :class="tab === 'bolsista' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500'"
                        class="py-4 px-1 border-b-2 font-medium text-sm transition">
                    👤 Por Bolsista
                </button>
            </nav>
        </div>
    </div>

    <!-- Tab: Mensal por Semanas (formato planilha) -->
    <div x-show="tab === 'semanal'" x-cloak>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">📅 Relatório Mensal por Semanas</h3>
            <p class="text-sm text-gray-500 mb-4">Formato similar à planilha de prestação de contas</p>
            
            <!-- Filtros -->
            <div class="flex flex-wrap gap-4 items-end mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Mês</label>
                    <select x-model="mesSemanal" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
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
                    <select x-model="anoSemanal" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="2025">2025</option>
                        <option value="2026">2026</option>
                    </select>
                </div>
                <button @click="gerarSemanal()" 
                        class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition">
                    📊 Gerar
                </button>
                <button @click="exportarSemanal()" 
                        class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition">
                    📥 Exportar Excel
                </button>
            </div>
            
            <div x-show="loadingSemanal" class="text-center py-8">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-500 border-t-transparent"></div>
            </div>
            
            <!-- Tabela formato planilha -->
            <div x-show="!loadingSemanal && relSemanal.semanas" class="overflow-x-auto">
                <h4 class="text-lg font-bold text-center py-2 bg-yellow-100 rounded-t-lg" x-text="relSemanal.mes_ano"></h4>
                
                <table class="min-w-full border border-gray-300">
                    <!-- Cabeçalho semanas -->
                    <thead>
                        <tr class="bg-blue-100">
                            <th class="border px-3 py-2 text-left font-medium"></th>
                            <template x-for="semana in relSemanal.semanas" :key="semana.numero">
                                <th class="border px-3 py-2 text-center font-medium" x-text="semana.label"></th>
                            </template>
                            <th class="border px-3 py-2 text-center font-bold">Total Mês</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Presente -->
                        <tr class="bg-green-50">
                            <td class="border px-3 py-2 font-medium">Presente</td>
                            <template x-for="(valor, idx) in relSemanal.dados?.presente" :key="'presente-'+idx">
                                <td class="border px-3 py-2 text-center" x-text="valor.valor"></td>
                            </template>
                            <td class="border px-3 py-2 text-center font-bold" x-text="relSemanal.totais?.presente"></td>
                        </tr>
                        <!-- Extra -->
                        <tr class="bg-yellow-50">
                            <td class="border px-3 py-2 font-medium">Extra</td>
                            <template x-for="(valor, idx) in relSemanal.dados?.extra" :key="'extra-'+idx">
                                <td class="border px-3 py-2 text-center" x-text="valor.valor"></td>
                            </template>
                            <td class="border px-3 py-2 text-center font-bold" x-text="relSemanal.totais?.extra"></td>
                        </tr>
                        <!-- Ausente -->
                        <tr class="bg-red-50">
                            <td class="border px-3 py-2 font-medium">Ausente</td>
                            <template x-for="(valor, idx) in relSemanal.dados?.ausente" :key="'ausente-'+idx">
                                <td class="border px-3 py-2 text-center" x-text="valor.valor"></td>
                            </template>
                            <td class="border px-3 py-2 text-center font-bold" x-text="relSemanal.totais?.ausente"></td>
                        </tr>
                        <!-- Atestado -->
                        <tr class="bg-blue-50">
                            <td class="border px-3 py-2 font-medium">Atestado</td>
                            <template x-for="(valor, idx) in relSemanal.dados?.atestado" :key="'atestado-'+idx">
                                <td class="border px-3 py-2 text-center" x-text="valor.valor"></td>
                            </template>
                            <td class="border px-3 py-2 text-center font-bold" x-text="relSemanal.totais?.atestado"></td>
                        </tr>
                        <!-- Justificado -->
                        <tr class="bg-orange-50">
                            <td class="border px-3 py-2 font-medium">Justificado</td>
                            <template x-for="(valor, idx) in relSemanal.dados?.justificado" :key="'justificado-'+idx">
                                <td class="border px-3 py-2 text-center" x-text="valor.valor"></td>
                            </template>
                            <td class="border px-3 py-2 text-center font-bold" x-text="relSemanal.totais?.justificado"></td>
                        </tr>
                        <!-- Ñ Frequenta -->
                        <tr class="bg-gray-100">
                            <td class="border px-3 py-2 font-medium">Ñ Frequenta</td>
                            <template x-for="(valor, idx) in relSemanal.dados?.n_frequenta" :key="'nfreq-'+idx">
                                <td class="border px-3 py-2 text-center" x-text="valor.valor"></td>
                            </template>
                            <td class="border px-3 py-2 text-center font-bold" x-text="relSemanal.totais?.n_frequenta"></td>
                        </tr>
                        <!-- Total Mensal -->
                        <tr class="bg-indigo-100 font-bold">
                            <td class="border px-3 py-2">Total Mensal de Refeições</td>
                            <template x-for="semana in relSemanal.semanas" :key="'total-'+semana.numero">
                                <td class="border px-3 py-2 text-center">-----</td>
                            </template>
                            <td class="border px-3 py-2 text-center text-lg" x-text="relSemanal.total_mensal_refeicoes"></td>
                        </tr>
                    </tbody>
                </table>
                
                <!-- Legenda semanas -->
                <div class="mt-4 text-sm text-gray-600">
                    <h5 class="font-medium mb-2">Períodos das Semanas:</h5>
                    <div class="flex flex-wrap gap-4">
                        <template x-for="semana in relSemanal.semanas" :key="'leg-'+semana.numero">
                            <span class="bg-gray-100 px-2 py-1 rounded">
                                <span x-text="semana.label"></span>: <span x-text="semana.periodo"></span>
                            </span>
                        </template>
                    </div>
                </div>
            </div>
            
            <div x-show="!loadingSemanal && !relSemanal.semanas" class="text-center py-8 text-gray-500">
                Selecione mês/ano e clique em "Gerar"
            </div>
        </div>
    </div>

    <!-- Tab: Presenças por Período -->
    <div x-show="tab === 'presencas'" x-cloak>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">📊 Relatório de Presenças</h3>
            
            <!-- Filtros -->
            <div class="flex flex-wrap gap-4 items-end mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Data Início</label>
                    <input type="date" x-model="dataInicio" 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Data Fim</label>
                    <input type="date" x-model="dataFim"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Turno</label>
                    <select x-model="turno" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="">Todos</option>
                        <option value="almoco">Almoço</option>
                        <option value="jantar">Jantar</option>
                    </select>
                </div>
                <button @click="gerarPresencas()" 
                        class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition">
                    📊 Gerar Relatório
                </button>
                <button @click="exportar()" 
                        class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 transition">
                    📥 Exportar Excel
                </button>
            </div>
            
            <!-- Loading -->
            <div x-show="loading" class="text-center py-8">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-500 border-t-transparent"></div>
            </div>
            
            <!-- Totais -->
            <div x-show="!loading && relPresencas.totais" class="mb-6">
                <h4 class="font-medium text-gray-700 mb-3">Totais do Período</h4>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <div class="bg-green-50 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold text-green-700" x-text="relPresencas.totais?.confirmados || 0"></p>
                        <p class="text-sm text-green-600">Confirmados</p>
                    </div>
                    <div class="bg-yellow-50 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold text-yellow-700" x-text="relPresencas.totais?.falta_justificada || 0"></p>
                        <p class="text-sm text-yellow-600">Faltas Just.</p>
                    </div>
                    <div class="bg-red-50 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold text-red-700" x-text="relPresencas.totais?.falta_injustificada || 0"></p>
                        <p class="text-sm text-red-600">Faltas Injust.</p>
                    </div>
                    <div class="bg-gray-100 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold text-gray-700" x-text="relPresencas.totais?.cancelados || 0"></p>
                        <p class="text-sm text-gray-600">Cancelados</p>
                    </div>
                    <div class="bg-blue-50 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold text-blue-700" x-text="relPresencas.totais?.total_registros || 0"></p>
                        <p class="text-sm text-blue-600">Total</p>
                    </div>
                </div>
            </div>
            
            <!-- Tabela -->
            <div x-show="!loading && relPresencas.dados && relPresencas.dados.length > 0" class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Data</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Turno</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Confirmados</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">F. Just.</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">F. Injust.</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Total</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <template x-for="item in relPresencas.dados" :key="item.data + item.turno">
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-900" x-text="item.data"></td>
                                <td class="px-4 py-3 text-sm text-gray-500" x-text="item.turno"></td>
                                <td class="px-4 py-3 text-sm text-center text-green-600 font-medium" x-text="item.confirmados"></td>
                                <td class="px-4 py-3 text-sm text-center text-yellow-600" x-text="item.falta_justificada"></td>
                                <td class="px-4 py-3 text-sm text-center text-red-600" x-text="item.falta_injustificada"></td>
                                <td class="px-4 py-3 text-sm text-center font-medium" x-text="item.total"></td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
            
            <div x-show="!loading && (!relPresencas.dados || relPresencas.dados.length === 0)" class="text-center py-8 text-gray-500">
                Selecione o período e clique em "Gerar Relatório"
            </div>
        </div>
    </div>

    <!-- Tab: Resumo Mensal -->
    <div x-show="tab === 'mensal'" x-cloak>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">📅 Resumo Mensal</h3>
            
            <div class="flex flex-wrap gap-4 items-end mb-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Mês</label>
                    <select x-model="mes" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
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
                    <select x-model="ano" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="2025">2025</option>
                        <option value="2026">2026</option>
                    </select>
                </div>
                <button @click="gerarMensal()" 
                        class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition">
                    📊 Gerar
                </button>
            </div>
            
            <div x-show="loadingMensal" class="text-center py-8">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-500 border-t-transparent"></div>
            </div>
            
            <div x-show="!loadingMensal && relMensal.mes_texto" class="space-y-4">
                <h4 class="text-xl font-semibold text-gray-800">
                    <span x-text="relMensal.mes_texto"></span> / <span x-text="relMensal.ano"></span>
                </h4>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <p class="text-3xl font-bold text-blue-700" x-text="relMensal.total_refeicoes"></p>
                        <p class="text-sm text-blue-600">Total Refeições</p>
                    </div>
                    <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <p class="text-3xl font-bold text-green-700" x-text="relMensal.confirmados"></p>
                        <p class="text-sm text-green-600">Confirmados</p>
                    </div>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <p class="text-3xl font-bold text-yellow-700" x-text="relMensal.falta_justificada"></p>
                        <p class="text-sm text-yellow-600">Faltas Justificadas</p>
                    </div>
                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                        <p class="text-3xl font-bold text-red-700" x-text="relMensal.falta_injustificada"></p>
                        <p class="text-sm text-red-600">Faltas Injustificadas</p>
                    </div>
                </div>
                
                <div class="bg-gray-100 rounded-lg p-4">
                    <p class="text-lg">
                        Taxa de Presença: <span class="font-bold text-green-600" x-text="relMensal.taxa_presenca"></span>
                    </p>
                    <p class="text-sm text-gray-600">
                        Total de registros: <span x-text="relMensal.total_registros"></span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab: Por Bolsista -->
    <div x-show="tab === 'bolsista'" x-cloak>
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">👤 Relatório Individual</h3>
            
            <div class="flex flex-wrap gap-4 items-end mb-6">
                <div class="flex-1 min-w-48">
                    <label class="block text-sm font-medium text-gray-700">ID do Bolsista</label>
                    <input type="number" x-model="userId" placeholder="ID do usuário"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Data Início</label>
                    <input type="date" x-model="dataInicioBol" 
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Data Fim</label>
                    <input type="date" x-model="dataFimBol"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <button @click="gerarBolsista()" 
                        class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition">
                    📊 Gerar
                </button>
            </div>
            
            <div x-show="loadingBol" class="text-center py-8">
                <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-blue-500 border-t-transparent"></div>
            </div>
            
            <div x-show="!loadingBol && relBolsista.bolsista" class="space-y-4">
                <!-- Info do bolsista -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-800" x-text="relBolsista.bolsista?.nome"></h4>
                    <p class="text-sm text-gray-600">
                        Matrícula: <span x-text="relBolsista.bolsista?.matricula"></span> |
                        Curso: <span x-text="relBolsista.bolsista?.curso || 'N/A'"></span>
                    </p>
                    <p class="text-xs text-gray-500 mt-1">
                        Período: <span x-text="relBolsista.periodo?.inicio"></span> a <span x-text="relBolsista.periodo?.fim"></span>
                    </p>
                </div>
                
                <!-- Resumo -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-blue-50 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold text-blue-700" x-text="relBolsista.resumo?.total || 0"></p>
                        <p class="text-sm text-blue-600">Total</p>
                    </div>
                    <div class="bg-green-50 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold text-green-700" x-text="relBolsista.resumo?.confirmados || 0"></p>
                        <p class="text-sm text-green-600">Confirmados</p>
                    </div>
                    <div class="bg-yellow-50 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold text-yellow-700" x-text="relBolsista.resumo?.falta_justificada || 0"></p>
                        <p class="text-sm text-yellow-600">F. Justificadas</p>
                    </div>
                    <div class="bg-red-50 rounded-lg p-3 text-center">
                        <p class="text-2xl font-bold text-red-700" x-text="relBolsista.resumo?.falta_injustificada || 0"></p>
                        <p class="text-sm text-red-600">F. Injustificadas</p>
                    </div>
                </div>
                
                <p class="text-lg font-medium">
                    Taxa de Presença: <span class="text-green-600" x-text="relBolsista.resumo?.taxa_presenca || '0%'"></span>
                </p>
                
                <!-- Histórico -->
                <div x-show="relBolsista.historico && relBolsista.historico.length > 0">
                    <h5 class="font-medium text-gray-700 mb-2">Histórico</h5>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left">Data</th>
                                    <th class="px-3 py-2 text-left">Turno</th>
                                    <th class="px-3 py-2 text-left">Status</th>
                                    <th class="px-3 py-2 text-left">Validado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="item in relBolsista.historico" :key="item.data + item.turno">
                                    <tr>
                                        <td class="px-3 py-2" x-text="item.data"></td>
                                        <td class="px-3 py-2" x-text="item.turno"></td>
                                        <td class="px-3 py-2">
                                            <span :class="{
                                                'text-green-600': item.status === 'confirmado',
                                                'text-yellow-600': item.status === 'falta_justificada',
                                                'text-red-600': item.status === 'falta_injustificada'
                                            }" x-text="item.status"></span>
                                        </td>
                                        <td class="px-3 py-2 text-gray-500" x-text="item.validado_em || '-'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div x-show="erroBol" class="bg-red-50 border border-red-200 rounded-lg p-4 text-red-700">
                <span x-text="erroBol"></span>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function relatoriosApp() {
    const hoje = new Date();
    const primeiroDia = new Date(hoje.getFullYear(), hoje.getMonth(), 1).toISOString().split('T')[0];
    const ultimoDia = new Date(hoje.getFullYear(), hoje.getMonth() + 1, 0).toISOString().split('T')[0];
    
    return {
        tab: 'semanal',
        
        // Semanal (formato planilha)
        loadingSemanal: false,
        mesSemanal: hoje.getMonth() + 1,
        anoSemanal: hoje.getFullYear(),
        relSemanal: {},
        
        // Presenças
        loading: false,
        dataInicio: primeiroDia,
        dataFim: ultimoDia,
        turno: '',
        relPresencas: {},
        
        // Mensal
        loadingMensal: false,
        mes: hoje.getMonth() + 1,
        ano: hoje.getFullYear(),
        relMensal: {},
        
        // Bolsista
        loadingBol: false,
        userId: '',
        dataInicioBol: primeiroDia,
        dataFimBol: ultimoDia,
        relBolsista: {},
        erroBol: '',
        
        async gerarSemanal() {
            this.loadingSemanal = true;
            try {
                const response = await fetch(`/api/v1/admin/relatorios/semanal?mes=${this.mesSemanal}&ano=${this.anoSemanal}`);
                const json = await response.json();
                this.relSemanal = json.data || {};
            } catch (e) {
                console.error('Erro:', e);
            }
            this.loadingSemanal = false;
        },
        
        exportarSemanal() {
            window.open(`/api/v1/admin/relatorios/exportar-semanal?mes=${this.mesSemanal}&ano=${this.anoSemanal}`, '_blank');
        },
        
        async gerarPresencas() {
            if (!this.dataInicio || !this.dataFim) {
                alert('Selecione o período');
                return;
            }
            
            this.loading = true;
            try {
                let url = `/api/v1/admin/relatorios/presencas?data_inicio=${this.dataInicio}&data_fim=${this.dataFim}`;
                if (this.turno) url += `&turno=${this.turno}`;
                
                const response = await fetch(url);
                const json = await response.json();
                this.relPresencas = {
                    dados: json.data || [],
                    totais: json.meta?.totais || {},
                    periodo: json.meta?.periodo || {}
                };
            } catch (e) {
                console.error('Erro:', e);
            }
            this.loading = false;
        },
        
        async exportar() {
            if (!this.dataInicio || !this.dataFim) {
                alert('Selecione o período primeiro');
                return;
            }
            
            let url = `/api/v1/admin/relatorios/exportar?data_inicio=${this.dataInicio}&data_fim=${this.dataFim}&formato=xlsx`;
            if (this.turno) url += `&turno=${this.turno}`;
            
            window.open(url, '_blank');
        },
        
        async gerarMensal() {
            this.loadingMensal = true;
            try {
                const response = await fetch(`/api/v1/admin/relatorios/mensal?mes=${this.mes}&ano=${this.ano}`);
                const json = await response.json();
                this.relMensal = json.data || {};
            } catch (e) {
                console.error('Erro:', e);
            }
            this.loadingMensal = false;
        },
        
        async gerarBolsista() {
            if (!this.userId) {
                alert('Informe o ID do bolsista');
                return;
            }
            
            this.loadingBol = true;
            this.erroBol = '';
            this.relBolsista = {};
            
            try {
                let url = `/api/v1/admin/relatorios/bolsista/${this.userId}`;
                if (this.dataInicioBol) url += `?data_inicio=${this.dataInicioBol}`;
                if (this.dataFimBol) url += `&data_fim=${this.dataFimBol}`;
                
                const response = await fetch(url);
                const json = await response.json();
                
                if (json.errors && Object.keys(json.errors).length > 0) {
                    this.erroBol = JSON.stringify(json.errors);
                } else {
                    this.relBolsista = json.data || {};
                }
            } catch (e) {
                this.erroBol = e.message;
            }
            
            this.loadingBol = false;
        }
    }
}
</script>
@endpush
