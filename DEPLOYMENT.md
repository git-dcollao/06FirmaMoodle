# Despliegue de Nuevo Moodle

Este proyecto contiene los archivos necesarios para desplegar un nuevo contenedor Moodle limpio en el servidor de pruebas.

## Archivos

- `docker-compose.yml` - Configuración de Docker Compose para Moodle 4.4 + MariaDB
- `deploy.ps1` - Script PowerShell para desplegar desde Windows
- `deploy_new_moodle.sh` - Script bash que se ejecuta en el servidor
- `backup_moodle_db.sql` - Respaldo de la BD (13MB, ya creado en el servidor)

## Configuración del Nuevo Moodle

### Servicios:
- **Moodle 4.4**: Puerto 8081 (HTTP) y 8443 (HTTPS)
- **MariaDB 11.2**: Base de datos

### Credenciales por defecto:
- **Moodle Admin**: 
  - Usuario: `admin`
  - Password: `Admin123!`
  
- **Base de Datos**:
  - Usuario: `bn_moodle`
  - Password: `MoodleDBPass123!`
  - Base de datos: `bitnami_moodle`

## Despliegue Rápido

### Desde PowerShell (Windows):

```powershell
cd C:\Users\Daniel Collao\Documents\Repositories\06FirmaMoodle
.\deploy.ps1
```

Este script:
1. Copia los archivos al servidor (10.20.10.3)
2. Crea el nuevo Moodle en `/home/admintd/docker/moodle-nuevo`
3. Levanta los contenedores
4. Muestra las credenciales de acceso

### Manual (si prefieres):

1. Copiar archivos al servidor:
```powershell
scp docker-compose.yml admintd@10.20.10.3:/home/admintd/docker/moodle-nuevo/
scp deploy_new_moodle.sh admintd@10.20.10.3:/home/admintd/docker/moodle-nuevo/
```

2. Conectarse y desplegar:
```bash
ssh admintd@10.20.10.3
cd /home/admintd/docker/moodle-nuevo
chmod +x deploy_new_moodle.sh
./deploy_new_moodle.sh
```

## Acceso

Una vez desplegado, accede a:
- **URL**: http://10.20.10.3:8081
- **Usuario**: admin
- **Password**: Admin123!

## Comandos Útiles

### Ver estado de los contenedores:
```bash
cd /home/admintd/docker/moodle-nuevo
docker-compose ps
```

### Ver logs:
```bash
docker-compose logs -f moodle
```

### Detener Moodle:
```bash
docker-compose stop
```

### Reiniciar Moodle:
```bash
docker-compose restart
```

### Eliminar completamente:
```bash
docker-compose down -v  # Borra contenedores y volúmenes
```

## Migración de Datos (Opcional)

Si quieres restaurar los datos del Moodle anterior:

1. **Restaurar base de datos**:
```bash
cd /home/admintd/docker/moodle-nuevo
docker-compose stop moodle
docker-compose exec -T mariadb mysql -u bn_moodle -pMoodleDBPass123! bitnami_moodle < /tmp/backup_moodle_db.sql
docker-compose start moodle
```

2. **Copiar archivos de usuarios** (si es necesario):
```bash
# Esto copiará los archivos subidos por usuarios
docker cp <contenedor_viejo>:/bitnami/moodledata/filedir ./filedir_backup
docker cp ./filedir_backup moodle-app:/bitnami/moodledata/filedir
```

## Solución de Problemas

### Ver logs de errores:
```bash
docker-compose logs moodle | grep -i error
```

### Verificar permisos:
```bash
docker-compose exec moodle ls -la /bitnami/moodledata
```

### Reiniciar desde cero:
```bash
docker-compose down -v  # Borra todo
docker-compose up -d    # Vuelve a crear
```

## Notas

- El nuevo Moodle usa volúmenes Docker limpios (sin problemas de permisos)
- MariaDB tiene healthcheck para asegurar que esté lista antes de iniciar Moodle
- Los contenedores se reinician automáticamente (restart: unless-stopped)
- El puerto es 8081 para no interferir con el Moodle actual
