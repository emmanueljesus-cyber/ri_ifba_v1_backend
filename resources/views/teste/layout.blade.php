<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'RI IFBA - Testes')</title>
    
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js para interatividade -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        [x-cloak] { display: none !important; }
        
        .gradient-bg {
            background: linear-gradient(135deg, #1e3a5f 0%, #2d5a87 100%);
        }
        
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
    </style>
    
    @stack('styles')
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- Navbar -->
    <nav class="gradient-bg text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <span class="text-2xl font-bold">🍽️ RI IFBA</span>
                    <span class="ml-4 text-sm bg-yellow-500 text-black px-2 py-1 rounded">TESTE</span>
                </div>
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-4">
                        <a href="{{ route('teste.dashboard') }}" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-white/10 transition {{ request()->routeIs('teste.dashboard') ? 'bg-white/20' : '' }}">
                            📊 Dashboard
                        </a>
                        <a href="{{ route('teste.bolsistas') }}" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-white/10 transition {{ request()->routeIs('teste.bolsistas*') ? 'bg-white/20' : '' }}">
                            👥 Bolsistas
                        </a>
                        <a href="{{ route('teste.justificativas') }}" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-white/10 transition {{ request()->routeIs('teste.justificativas*') ? 'bg-white/20' : '' }}">
                            📝 Justificativas
                        </a>
                        <a href="{{ route('teste.relatorios') }}" class="px-3 py-2 rounded-md text-sm font-medium hover:bg-white/10 transition {{ request()->routeIs('teste.relatorios*') ? 'bg-white/20' : '' }}">
                            📈 Relatórios
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Mobile menu -->
    <div class="md:hidden gradient-bg border-t border-white/10">
        <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
            <a href="{{ route('teste.dashboard') }}" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-white/10">📊 Dashboard</a>
            <a href="{{ route('teste.bolsistas') }}" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-white/10">👥 Bolsistas</a>
            <a href="{{ route('teste.justificativas') }}" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-white/10">📝 Justificativas</a>
            <a href="{{ route('teste.relatorios') }}" class="text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-white/10">📈 Relatórios</a>
        </div>
    </div>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        @if(session('success'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif
        
        @if(session('error'))
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif
        
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-4 mt-8">
        <div class="max-w-7xl mx-auto px-4 text-center text-sm">
            <p>🧪 Interface de Testes - RI IFBA Backend</p>
            <p class="text-gray-400 mt-1">Ambiente de desenvolvimento - Sem autenticação</p>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
