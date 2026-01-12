# Script para copiar arquivos do frontend
# Execute este script no terminal (PowerShell ou Bash)

# De: docs/frontend_files/
# Para: ../ri_ifba_v1_frontend/src/

# === INSTRUÇÕES ===
# 1. Navegue para a pasta do backend
# 2. Execute os comandos abaixo

# PowerShell:
Copy-Item -Path "docs\frontend_files\vite.config.ts" -Destination "..\ri_ifba_v1_frontend\"
Copy-Item -Path "docs\frontend_files\main.ts" -Destination "..\ri_ifba_v1_frontend\src\"
Copy-Item -Path "docs\frontend_files\style.css" -Destination "..\ri_ifba_v1_frontend\src\"
Copy-Item -Path "docs\frontend_files\App.vue" -Destination "..\ri_ifba_v1_frontend\src\" -Force

New-Item -ItemType Directory -Force -Path "..\ri_ifba_v1_frontend\src\router"
New-Item -ItemType Directory -Force -Path "..\ri_ifba_v1_frontend\src\stores"
New-Item -ItemType Directory -Force -Path "..\ri_ifba_v1_frontend\src\services"
New-Item -ItemType Directory -Force -Path "..\ri_ifba_v1_frontend\src\types"
New-Item -ItemType Directory -Force -Path "..\ri_ifba_v1_frontend\src\views\estudante"
New-Item -ItemType Directory -Force -Path "..\ri_ifba_v1_frontend\src\views\admin"
New-Item -ItemType Directory -Force -Path "..\ri_ifba_v1_frontend\src\components\common"
New-Item -ItemType Directory -Force -Path "..\ri_ifba_v1_frontend\src\components\sideBar"

Copy-Item -Path "docs\frontend_files\router\index.ts" -Destination "..\ri_ifba_v1_frontend\src\router\"
Copy-Item -Path "docs\frontend_files\stores\usuario.ts" -Destination "..\ri_ifba_v1_frontend\src\stores\"
Copy-Item -Path "docs\frontend_files\services\api.ts" -Destination "..\ri_ifba_v1_frontend\src\services\"
Copy-Item -Path "docs\frontend_files\types\index.ts" -Destination "..\ri_ifba_v1_frontend\src\types\"

Write-Host "Arquivos copiados com sucesso!"

# === OU BASH (WSL): ===
# cp docs/frontend_files/vite.config.ts ../ri_ifba_v1_frontend/
# cp docs/frontend_files/main.ts ../ri_ifba_v1_frontend/src/
# cp docs/frontend_files/style.css ../ri_ifba_v1_frontend/src/
# cp docs/frontend_files/App.vue ../ri_ifba_v1_frontend/src/
# mkdir -p ../ri_ifba_v1_frontend/src/{router,stores,services,types,views/{estudante,admin},components/{common,sideBar}}
# cp docs/frontend_files/router/index.ts ../ri_ifba_v1_frontend/src/router/
# cp docs/frontend_files/stores/usuario.ts ../ri_ifba_v1_frontend/src/stores/
# cp docs/frontend_files/services/api.ts ../ri_ifba_v1_frontend/src/services/
# cp docs/frontend_files/types/index.ts ../ri_ifba_v1_frontend/src/types/
