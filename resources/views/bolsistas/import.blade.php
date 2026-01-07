e <!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>RF15 - Importar Bolsistas</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }

        .header p {
            opacity: 0.9;
            font-size: 14px;
        }

        .content {
            padding: 30px;
        }

        .card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 20px;
            border: 1px solid #e9ecef;
        }

        .card h2 {
            font-size: 18px;
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .icon {
            font-size: 24px;
        }

        .btn {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 6px;
            border: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .file-upload {
            border: 2px dashed #667eea;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 20px;
        }

        .file-upload:hover {
            border-color: #764ba2;
            background: #f8f9ff;
        }

        .file-upload.dragover {
            background: #e8ebff;
            border-color: #764ba2;
        }

        .file-upload input[type="file"] {
            display: none;
        }

        .file-info {
            margin-top: 15px;
            padding: 12px;
            background: #e8ebff;
            border-radius: 6px;
            display: none;
        }

        .file-info.show {
            display: block;
        }

        .file-info strong {
            color: #667eea;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: none;
        }

        .alert.show {
            display: block;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }

        .results {
            margin-top: 20px;
            display: none;
        }

        .results.show {
            display: block;
        }

        .result-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border: 2px solid #e9ecef;
        }

        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #6c757d;
            font-size: 14px;
        }

        .list-group {
            background: white;
            border-radius: 8px;
            overflow: hidden;
        }

        .list-group-item {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
        }

        .list-group-item:last-child {
            border-bottom: none;
        }

        .list-group-item strong {
            color: #333;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }

        .badge-success {
            background: #28a745;
            color: white;
        }

        .badge-info {
            background: #17a2b8;
            color: white;
        }

        .badge-danger {
            background: #dc3545;
            color: white;
        }

        .loading {
            text-align: center;
            padding: 20px;
            display: none;
        }

        .loading.show {
            display: block;
        }

        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .error-list {
            margin-top: 20px;
        }

        .error-item {
            background: #fff5f5;
            padding: 12px;
            border-left: 4px solid #dc3545;
            margin-bottom: 10px;
            border-radius: 4px;
        }

        .format-example {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            overflow-x: auto;
            margin-top: 10px;
        }

        .format-example table {
            width: 100%;
            border-collapse: collapse;
        }

        .format-example th,
        .format-example td {
            padding: 8px;
            border: 1px solid #dee2e6;
            text-align: left;
        }

        .format-example th {
            background: #e9ecef;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📚 RF15 - Importar Lista de Bolsistas</h1>
            <p>Sistema de Importação em Lote | IFBA Restaurante Institucional</p>
        </div>

        <div class="content">
            <!-- Alertas -->
            <div id="alert" class="alert"></div>

            <!-- Passo 1: Download Template -->
            <div class="card">
                <h2><span class="icon">📥</span> Passo 1: Download do Template</h2>
                <p style="margin-bottom: 15px; color: #6c757d;">Baixe o template Excel para preencher com os dados dos bolsistas.</p>
                <button onclick="downloadTemplate()" class="btn btn-primary">
                    📥 Baixar Template Excel
                </button>

                <div class="format-example">
                    <strong>Formato esperado:</strong>
                    <table>
                        <thead>
                            <tr>
                                <th>matricula</th>
                                <th>nome</th>
                                <th>email</th>
                                <th>turno</th>
                                <th>curso</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>20231001</td>
                                <td>João Silva</td>
                                <td>joao@email.com</td>
                                <td>matutino</td>
                                <td>Técnico em Informática</td>
                            </tr>
                            <tr>
                                <td>20231002</td>
                                <td>Maria Costa</td>
                                <td>maria@email.com</td>
                                <td>vespertino</td>
                                <td>Técnico em Edificações</td>
                            </tr>
                        </tbody>
                    </table>
                    <p style="margin-top: 10px; font-size: 11px; color: #6c757d;">
                        <strong>Turnos aceitos:</strong> matutino, vespertino, noturno (ou manhã, tarde, noite)
                    </p>
                </div>
            </div>

            <!-- Passo 2: Upload Arquivo -->
            <div class="card">
                <h2><span class="icon">📤</span> Passo 2: Upload do Arquivo</h2>
                <p style="margin-bottom: 15px; color: #6c757d;">Selecione o arquivo Excel preenchido para importar.</p>

                <div class="file-upload" id="dropZone" onclick="document.getElementById('fileInput').click()">
                    <div style="font-size: 48px; margin-bottom: 10px;">📁</div>
                    <p style="font-size: 16px; font-weight: 600; margin-bottom: 5px;">
                        Clique aqui ou arraste o arquivo
                    </p>
                    <p style="font-size: 12px; color: #6c757d;">
                        Formatos aceitos: .xlsx, .xls, .csv (máx. 5MB)
                    </p>
                    <input type="file" id="fileInput" accept=".xlsx,.xls,.csv" onchange="handleFileSelect(event)">
                </div>

                <div id="fileInfo" class="file-info">
                    <strong>Arquivo selecionado:</strong> <span id="fileName"></span>
                    <br>
                    <strong>Tamanho:</strong> <span id="fileSize"></span>
                </div>

                <button id="uploadBtn" onclick="uploadFile()" class="btn btn-success" disabled>
                    🚀 Importar Bolsistas
                </button>
            </div>

            <!-- Loading -->
            <div id="loading" class="loading">
                <div class="spinner"></div>
                <p style="margin-top: 15px; color: #6c757d;">Processando importação...</p>
            </div>

            <!-- Resultados -->
            <div id="results" class="results">
                <div class="card">
                    <h2><span class="icon">📊</span> Resultados da Importação</h2>

                    <div class="result-stats">
                        <div class="stat-card">
                            <div class="stat-number" id="totalImportados">0</div>
                            <div class="stat-label">Criados</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number" id="totalAtualizados">0</div>
                            <div class="stat-label">Atualizados</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number" id="totalErros">0</div>
                            <div class="stat-label">Erros</div>
                        </div>
                    </div>

                    <!-- Lista de Criados -->
                    <div id="criadosSection" style="display: none;">
                        <h3 style="margin-bottom: 15px; color: #28a745;">✅ Bolsistas Criados</h3>
                        <div id="criadosList" class="list-group"></div>
                    </div>

                    <!-- Lista de Atualizados -->
                    <div id="atualizadosSection" style="display: none; margin-top: 20px;">
                        <h3 style="margin-bottom: 15px; color: #17a2b8;">🔄 Bolsistas Atualizados</h3>
                        <div id="atualizadosList" class="list-group"></div>
                    </div>

                    <!-- Lista de Erros -->
                    <div id="errosSection" style="display: none; margin-top: 20px;">
                        <h3 style="margin-bottom: 15px; color: #dc3545;">❌ Erros Encontrados</h3>
                        <div id="errosList" class="error-list"></div>
                    </div>

                    <button onclick="resetForm()" class="btn btn-secondary" style="margin-top: 20px;">
                        🔄 Nova Importação
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let selectedFile = null;
        const API_BASE = '/api/v1/admin/bolsistas';
        const TOKEN = 'YOUR_TOKEN_HERE'; // Substituir com token real ou implementar login

        // Drag and Drop
        const dropZone = document.getElementById('dropZone');

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.add('dragover');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.remove('dragover');
            }, false);
        });

        dropZone.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files.length > 0) {
                handleFile(files[0]);
            }
        }

        function handleFileSelect(event) {
            const file = event.target.files[0];
            if (file) {
                handleFile(file);
            }
        }

        function handleFile(file) {
            // Validar tipo
            const validTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                               'application/vnd.ms-excel',
                               'text/csv'];

            if (!validTypes.includes(file.type)) {
                showAlert('Tipo de arquivo inválido. Use .xlsx, .xls ou .csv', 'error');
                return;
            }

            // Validar tamanho (5MB)
            if (file.size > 5 * 1024 * 1024) {
                showAlert('Arquivo muito grande. Máximo: 5MB', 'error');
                return;
            }

            selectedFile = file;

            // Mostrar informações do arquivo
            document.getElementById('fileName').textContent = file.name;
            document.getElementById('fileSize').textContent = formatBytes(file.size);
            document.getElementById('fileInfo').classList.add('show');
            document.getElementById('uploadBtn').disabled = false;

            showAlert('Arquivo selecionado com sucesso! Clique em "Importar Bolsistas".', 'success');
        }

        function formatBytes(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }

        async function downloadTemplate() {
            try {
                showAlert('Baixando template...', 'warning');

                const response = await fetch(`${API_BASE}/template`, {
                    method: 'GET',
                    headers: {
                        'Authorization': `Bearer ${TOKEN}`,
                        'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                    }
                });

                if (!response.ok) {
                    throw new Error('Erro ao baixar template');
                }

                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'template_bolsistas.xlsx';
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);

                showAlert('Template baixado com sucesso! Preencha e faça o upload.', 'success');
            } catch (error) {
                showAlert('Erro ao baixar template: ' + error.message, 'error');
                console.error(error);
            }
        }

        async function uploadFile() {
            if (!selectedFile) {
                showAlert('Selecione um arquivo primeiro!', 'error');
                return;
            }

            try {
                // Mostrar loading
                document.getElementById('loading').classList.add('show');
                document.getElementById('uploadBtn').disabled = true;
                hideAlert();

                const formData = new FormData();
                formData.append('file', selectedFile);

                const response = await fetch(`${API_BASE}/importar`, {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${TOKEN}`,
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: formData
                });

                const result = await response.json();

                // Esconder loading
                document.getElementById('loading').classList.remove('show');

                if (response.ok || response.status === 207) {
                    displayResults(result);

                    if (result.errors && result.errors.length > 0) {
                        showAlert(`Importação concluída com ${result.errors.length} erro(s). Veja detalhes abaixo.`, 'warning');
                    } else {
                        showAlert('Importação concluída com sucesso!', 'success');
                    }
                } else {
                    throw new Error(result.message || 'Erro ao importar arquivo');
                }

            } catch (error) {
                document.getElementById('loading').classList.remove('show');
                document.getElementById('uploadBtn').disabled = false;
                showAlert('Erro ao importar: ' + error.message, 'error');
                console.error(error);
            }
        }

        function displayResults(data) {
            const resultsDiv = document.getElementById('results');
            resultsDiv.classList.add('show');

            // Stats
            document.getElementById('totalImportados').textContent = data.data.total_importados || 0;
            document.getElementById('totalAtualizados').textContent = data.data.total_atualizados || 0;
            document.getElementById('totalErros').textContent = data.errors ? data.errors.length : 0;

            // Criados
            if (data.data.criados && data.data.criados.length > 0) {
                const criadosSection = document.getElementById('criadosSection');
                const criadosList = document.getElementById('criadosList');
                criadosSection.style.display = 'block';
                criadosList.innerHTML = '';

                data.data.criados.forEach(item => {
                    const div = document.createElement('div');
                    div.className = 'list-group-item';
                    div.innerHTML = `
                        <strong>${item.nome}</strong>
                        <span class="badge badge-success">CRIADO</span>
                        <br>
                        <small style="color: #6c757d;">Matrícula: ${item.matricula}</small>
                    `;
                    criadosList.appendChild(div);
                });
            }

            // Atualizados
            if (data.data.atualizados && data.data.atualizados.length > 0) {
                const atualizadosSection = document.getElementById('atualizadosSection');
                const atualizadosList = document.getElementById('atualizadosList');
                atualizadosSection.style.display = 'block';
                atualizadosList.innerHTML = '';

                data.data.atualizados.forEach(item => {
                    const div = document.createElement('div');
                    div.className = 'list-group-item';
                    div.innerHTML = `
                        <strong>${item.nome}</strong>
                        <span class="badge badge-info">ATUALIZADO</span>
                        <br>
                        <small style="color: #6c757d;">Matrícula: ${item.matricula}</small>
                    `;
                    atualizadosList.appendChild(div);
                });
            }

            // Erros
            if (data.errors && data.errors.length > 0) {
                const errosSection = document.getElementById('errosSection');
                const errosList = document.getElementById('errosList');
                errosSection.style.display = 'block';
                errosList.innerHTML = '';

                data.errors.forEach(error => {
                    const div = document.createElement('div');
                    div.className = 'error-item';
                    div.innerHTML = `
                        <strong>Linha ${error.linha || 'N/A'}:</strong> ${error.erro}
                    `;
                    errosList.appendChild(div);
                });
            }

            // Scroll para resultados
            resultsDiv.scrollIntoView({ behavior: 'smooth' });
        }

        function showAlert(message, type) {
            const alert = document.getElementById('alert');
            alert.textContent = message;
            alert.className = `alert alert-${type} show`;
        }

        function hideAlert() {
            const alert = document.getElementById('alert');
            alert.classList.remove('show');
        }

        function resetForm() {
            selectedFile = null;
            document.getElementById('fileInput').value = '';
            document.getElementById('fileInfo').classList.remove('show');
            document.getElementById('uploadBtn').disabled = true;
            document.getElementById('results').classList.remove('show');
            document.getElementById('criadosSection').style.display = 'none';
            document.getElementById('atualizadosSection').style.display = 'none';
            document.getElementById('errosSection').style.display = 'none';
            hideAlert();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    </script>
</body>
</html>

