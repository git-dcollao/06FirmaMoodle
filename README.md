# Moodle Local Firma

Local plugin for Moodle 5.1 that enables legally compliant (Ley 19.799, FES) document signing workflows with checklist gating, handwritten signatures, PDF templates, QR verification, and reminder automation.

## Features
- Course or module level template definitions with versioning.
- Checklist requirements tied to activity completion or custom progress (e.g., video 100%).
- Canvas-based handwritten signature capture, embedded directly into PDF templates via FPDI/TCPDF.
- Automatic insertion of learner/course data (including custom profile fields) at teacher-defined coordinates.
- QR code per signed document pointing to verification endpoint with status and hashes.
- Audit trail: timestamps, IP, user agent, SHA-256 hashes, reminder history.
- Reminder cron task with notification dispatch for pending signatures.

## Project Layout
```
local/
  firma/
    classes/
    db/
    lang/
    vendor/ (after composer install)
```
Additional docs: `docs/ARCHITECTURE.md`.

## Getting Started
1. Ensure Moodle 5.1 or later.
2. Copy `local/firma` into your Moodle installation.
3. Run `composer install` inside `local/firma` to fetch PDF/QR dependencies.
4. Visit `Site administration > Notifications` to trigger database upgrade.

## Development Notes
- PHP 8.1+ recommended.
- Follow Moodle coding guidelines.
- All strings must go through language packs in `lang/en/local_firma.php` (and translations).
- Use `	ool_task




MIT for plugin code. External libraries retain their respective licenses (TCPDF LGPLv3, FPDI MIT, Endroid QRCode MIT).## Licenseun_from_cli` to manually trigger scheduled reminders for testing.

## Despliegue Docker

Este repositorio incluye configuración completa de Docker Compose para desplegar Moodle con arquitectura PHP-FPM + Nginx + MariaDB.

### Arquitectura
- **PHP-FPM 8.2**: Imagen personalizada con todas las extensiones requeridas por Moodle
- **Nginx 1.24**: Servidor web con configuración optimizada para Moodle
- **MariaDB 10.11**: Base de datos con charset utf8mb4

### Estructura de archivos
```
docker-compose.yml       # Orquestación de servicios
.env                     # Variables de entorno (NO COMMITEAR)
deploy.ps1               # Script de despliegue desde Windows
deploy_new_moodle.sh     # Script de despliegue en servidor
download_moodle.sh       # Descarga automática de Moodle
php/
  Dockerfile             # Construcción de imagen PHP-FPM
  php.ini                # Configuración PHP optimizada
  opcache.ini            # Cache de código PHP
nginx/
  nginx.conf             # Configuración principal Nginx
  sites/moodle.conf      # Virtual host de Moodle
```

### Credenciales de producción
```
Servidor: 10.20.10.3:7080
DB Root Password: StrongRootPassword123!
DB Name: moodle_production
DB User: moodle_admin
DB Password: SecureMoodlePassword123!
Moodle Admin: admin
Moodle Admin Password: AdminSecurePass123!
```

### Despliegue

**Desde Windows (PowerShell):**
```powershell
.\deploy.ps1
```

**Manualmente en el servidor:**
```bash
ssh admintd@10.20.10.3
cd /home/admintd/docker/moodle-nuevo
chmod +x *.sh
./deploy_new_moodle.sh
```

### Restauración de base de datos
Si necesitas restaurar un backup previo:
```bash
docker-compose exec -T db mysql -u root -pStrongRootPassword123! moodle_production < /tmp/backup_moodle_db.sql
```

### Comandos útiles
```bash
# Ver logs
docker-compose logs -f

# Reiniciar servicios
docker-compose restart

# Detener todo
docker-compose down

# Reconstruir PHP
docker-compose up -d --build php

# Acceder al contenedor PHP
docker-compose exec php bash

# Backup de DB
docker-compose exec db mysqldump -u root -pStrongRootPassword123! moodle_production > backup_$(date +%Y%m%d).sql
```

### Instalación web de Moodle
Después del despliegue, accede a `http://10.20.10.3:7080` y completa el instalador web:
- **Database Host:** db
- **Database Name:** moodle_production
- **Database User:** moodle_admin
- **Database Password:** SecureMoodlePassword123!
- **Data Directory:** /var/moodledata

### Troubleshooting
- Si los contenedores no inician: `docker-compose logs`
- Si hay problemas de permisos: Verificar que moodledata tiene permisos 755 y pertenece a www-data
- Si Nginx retorna 502: Verificar que PHP-FPM esté corriendo en puerto 9000
- Si la DB no conecta: Revisar credenciales en .env

## Autoría y contacto
- Proyecto creado y mantenido por **Daniel Collao Vivanco**.
- Portafolio y código fuente: https://github.com/git-dcollao
- Contacto directo: daniel.collao@gmail.com