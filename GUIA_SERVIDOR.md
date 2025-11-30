# Guía Rápida - Servidor Docker

## Levantar el Servidor

Desde PowerShell o CMD en `C:\Users\josel\Desktop\virtualEmployee`:

```bash
docker-compose up -d
```

## Servicios Disponibles

Una vez levantado, puedes acceder a:

- **Aplicación Laravel**: http://localhost
- **phpMyAdmin**: http://localhost:8080
- **Mailpit** (emails de prueba): http://localhost:8025
- **MySQL**: localhost:3306
- **Redis**: localhost:6379

## Comandos Útiles

### Ver estado de los contenedores
```bash
docker ps
```

### Ver logs en tiempo real
```bash
docker-compose logs -f
```

### Ver logs de un servicio específico
```bash
docker-compose logs -f laravel.test
docker-compose logs -f mysql
```

### Detener el servidor
```bash
docker-compose down
```

### Reiniciar el servidor
```bash
docker-compose restart
```

### Ejecutar comandos Artisan
```bash
docker-compose exec laravel.test php artisan migrate
docker-compose exec laravel.test php artisan db:seed
docker-compose exec laravel.test php artisan cache:clear
```

### Acceder al contenedor de Laravel
```bash
docker-compose exec laravel.test bash
```

### Acceder a MySQL
```bash
docker-compose exec mysql mysql -u root -p
```

### Ver bases de datos
```bash
docker-compose exec mysql mysql -u root -ppassword -e "SHOW DATABASES;"
```

### Limpiar todo (⚠️ CUIDADO - Borra volúmenes)
```bash
docker-compose down -v
```

## Solución de Problemas

### Puerto ocupado
Si el puerto 80 está ocupado, edita `.env` y cambia:
```
APP_PORT=8000
```

### Reconstruir contenedores
```bash
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

### Ver espacio usado por Docker
```bash
docker system df
```

### Limpiar imágenes y contenedores no usados
```bash
docker system prune -a
```

## Workflow Típico

1. **Iniciar desarrollo**:
   ```bash
   docker-compose up -d
   ```

2. **Ver que todo funciona**:
   ```bash
   docker ps
   ```

3. **Trabajar en tu código** (los cambios se reflejan automáticamente)

4. **Si necesitas migrar**:
   ```bash
   docker-compose exec laravel.test php artisan migrate
   ```

5. **Al terminar**:
   ```bash
   docker-compose down
   ```

## Notas Importantes

- **NO necesitas WSL**: Con Docker Desktop puedes usar PowerShell/CMD directamente
- Los cambios en el código se reflejan inmediatamente (no necesitas reiniciar)
- Los datos de MySQL se guardan en un volumen (persisten entre reinicios)
- Si cambias `.env`, reinicia los contenedores: `docker-compose restart`
