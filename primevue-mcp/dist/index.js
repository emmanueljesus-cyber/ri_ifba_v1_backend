#!/usr/bin/env node
import { Server } from "@modelcontextprotocol/sdk/server/index.js";
import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";
import { CallToolRequestSchema, ListToolsRequestSchema, } from "@modelcontextprotocol/sdk/types.js";
import { readFileSync } from 'fs';
import { join, dirname } from 'path';
import { fileURLToPath } from 'url';
const __filename = fileURLToPath(import.meta.url);
const __dirname = dirname(__filename);
// Carregar dados
const components = JSON.parse(readFileSync(join(__dirname, 'data/components.json'), 'utf-8'));
const templates = JSON.parse(readFileSync(join(__dirname, 'data/templates.json'), 'utf-8'));
// Criar servidor
const server = new Server({ name: "primevue-mcp", version: "1.0.0" }, { capabilities: { tools: {} } });
// Listar ferramentas
server.setRequestHandler(ListToolsRequestSchema, async () => ({
    tools: [
        {
            name: "list_components",
            description: "Lista todos os componentes PrimeVue disponíveis",
            inputSchema: {
                type: "object",
                properties: {}
            }
        },
        {
            name: "get_component",
            description: "Retorna informações detalhadas de um componente PrimeVue",
            inputSchema: {
                type: "object",
                properties: {
                    component: {
                        type: "string",
                        enum: Object.keys(components),
                        description: "Nome do componente PrimeVue"
                    }
                },
                required: ["component"]
            }
        },
        {
            name: "list_templates",
            description: "Lista templates de página disponíveis",
            inputSchema: {
                type: "object",
                properties: {}
            }
        },
        {
            name: "get_template",
            description: "Retorna código completo de um template de página",
            inputSchema: {
                type: "object",
                properties: {
                    template: {
                        type: "string",
                        enum: Object.keys(templates),
                        description: "Nome do template (crud-page, login-page, dashboard)"
                    },
                    entityName: {
                        type: "string",
                        description: "Nome da entidade para templates CRUD (ex: 'users', 'products')",
                        default: "items"
                    }
                },
                required: ["template"]
            }
        },
        {
            name: "get_setup_guide",
            description: "Retorna guia de instalação e configuração do PrimeVue",
            inputSchema: {
                type: "object",
                properties: {}
            }
        },
        {
            name: "search_component",
            description: "Busca componentes por funcionalidade",
            inputSchema: {
                type: "object",
                properties: {
                    query: {
                        type: "string",
                        description: "Termo de busca (ex: 'tabela', 'formulário', 'botão')"
                    }
                },
                required: ["query"]
            }
        }
    ]
}));
// Executar ferramentas
server.setRequestHandler(CallToolRequestSchema, async (request) => {
    const { name, arguments: args } = request.params;
    switch (name) {
        case "list_components": {
            const list = Object.entries(components)
                .map(([key, val]) => `• **${key}**: ${val.description}`)
                .join('\n');
            return {
                content: [{
                        type: "text",
                        text: `# Componentes PrimeVue Disponíveis\n\n${list}\n\nUse \`get_component\` para ver detalhes e exemplos de cada componente.`
                    }]
            };
        }
        case "get_component": {
            const { component } = args;
            const comp = components[component];
            if (!comp) {
                throw new Error(`Componente '${component}' não encontrado`);
            }
            let output = `# ${component}\n\n`;
            output += `**Descrição:** ${comp.description}\n\n`;
            output += `## Import\n\`\`\`javascript\n${comp.import}\n\`\`\`\n\n`;
            output += `## Template Básico\n\`\`\`vue\n${comp.template}\n\`\`\`\n\n`;
            if (comp.props) {
                output += `## Props Principais\n`;
                Object.entries(comp.props).forEach(([key, value]) => {
                    output += `- **${key}**: ${value}\n`;
                });
                output += '\n';
            }
            if (comp.usage) {
                output += `## Uso no Script\n\`\`\`javascript\n${comp.usage}\n\`\`\`\n\n`;
            }
            if (comp.examples && comp.examples.length > 0) {
                output += `## Exemplos\n\n`;
                comp.examples.forEach((example, index) => {
                    output += `### Exemplo ${index + 1}\n\`\`\`vue\n${example}\n\`\`\`\n\n`;
                });
            }
            return {
                content: [{ type: "text", text: output }]
            };
        }
        case "list_templates": {
            const list = Object.entries(templates)
                .map(([key, val]) => `• **${key}**: ${val.description}`)
                .join('\n');
            return {
                content: [{
                        type: "text",
                        text: `# Templates de Página Disponíveis\n\n${list}\n\nUse \`get_template\` para obter o código completo.`
                    }]
            };
        }
        case "get_template": {
            const { template, entityName = "items" } = args;
            const tmpl = templates[template];
            if (!tmpl) {
                throw new Error(`Template '${template}' não encontrado`);
            }
            let code = tmpl.template;
            // Substituir placeholders
            code = code.replace(/\{\{entityName\}\}/g, entityName);
            return {
                content: [{
                        type: "text",
                        text: `# Template: ${template}\n\n**Descrição:** ${tmpl.description}\n\n## Código Completo\n\n\`\`\`vue\n${code}\n\`\`\``
                    }]
            };
        }
        case "get_setup_guide": {
            return {
                content: [{
                        type: "text",
                        text: `# Guia de Instalação PrimeVue

## 1. Instalação

\`\`\`bash
npm install primevue primeicons
\`\`\`

## 2. Configuração (main.js)

\`\`\`javascript
import { createApp } from 'vue';
import App from './App.vue';
import PrimeVue from 'primevue/config';

// Estilos
import 'primevue/resources/themes/lara-light-blue/theme.css';
import 'primevue/resources/primevue.min.css';
import 'primeicons/primeicons.css';
import 'primeflex/primeflex.css'; // opcional - utility classes

// Toast service (se usar Toast)
import ToastService from 'primevue/toastservice';

const app = createApp(App);

app.use(PrimeVue);
app.use(ToastService);

app.mount('#app');
\`\`\`

## 3. Temas Disponíveis

- lara-light-blue
- lara-light-indigo
- lara-dark-blue
- lara-dark-indigo
- bootstrap4-light-blue
- bootstrap4-dark-blue
- md-light-indigo
- md-dark-indigo

Troque em: \`primevue/resources/themes/[tema]/theme.css\`

## 4. Auto Import (Opcional)

Para importar componentes automaticamente:

\`\`\`bash
npm install -D unplugin-vue-components
\`\`\`

**vite.config.js:**
\`\`\`javascript
import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import Components from 'unplugin-vue-components/vite';
import { PrimeVueResolver } from 'unplugin-vue-components/resolvers';

export default defineConfig({
  plugins: [
    vue(),
    Components({
      resolvers: [PrimeVueResolver()]
    })
  ]
});
\`\`\`

## 5. Ícones PrimeIcons

Disponível em: https://primevue.org/icons

Uso: \`<i class="pi pi-check"></i>\` ou \`icon="pi pi-check"\`

## 6. PrimeFlex (Utility CSS)

\`\`\`bash
npm install primeflex
\`\`\`

Fornece classes como: \`flex\`, \`grid\`, \`p-3\`, \`mt-2\`, etc.

## 7. Documentação Oficial

https://primevue.org/
`
                    }]
            };
        }
        case "search_component": {
            const { query } = args;
            const searchTerm = query.toLowerCase();
            const results = Object.entries(components)
                .filter(([key, val]) => key.toLowerCase().includes(searchTerm) ||
                val.description.toLowerCase().includes(searchTerm))
                .map(([key, val]) => `• **${key}**: ${val.description}`)
                .join('\n');
            if (!results) {
                return {
                    content: [{
                            type: "text",
                            text: `Nenhum componente encontrado para: "${query}"`
                        }]
                };
            }
            return {
                content: [{
                        type: "text",
                        text: `# Resultados para: "${query}"\n\n${results}\n\nUse \`get_component\` para ver detalhes.`
                    }]
            };
        }
        default:
            throw new Error(`Ferramenta desconhecida: ${name}`);
    }
});
// Inicializar
async function main() {
    const transport = new StdioServerTransport();
    await server.connect(transport);
    console.error("✅ MCP PrimeVue iniciado com sucesso");
}
main().catch((error) => {
    console.error("❌ Erro ao iniciar MCP:", error);
    process.exit(1);
});
