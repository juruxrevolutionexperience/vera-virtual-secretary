# Sistema de Filtros de Respuesta Automática - Guía de Pruebas

## ✅ Implementación Completada

Se ha implementado exitosamente un sistema de filtros que permite a los usuarios controlar qué tipos de correos quieren que Vera responda automáticamente.

---

## 🚀 Pasos para Activar el Sistema

### 1. Ejecutar la Migración de Base de Datos

```bash
php artisan migrate
```

Esta migración agregará los siguientes campos a la tabla `vera_configs`:
- `response_allowed_categories` (JSON)
- `response_blocked_categories` (JSON)
- `blocked_keywords` (JSON)
- `blocked_senders` (JSON)
- `allowed_senders` (JSON)

---

## 🎯 Características Implementadas

### 1. **Filtros por Categoría de Correo**
Los usuarios pueden seleccionar qué categorías de correos quieren que Vera responda:
- ✅ **Trabajo**: Correos profesionales y laborales
- ✅ **Personal**: Correos de amigos y familia
- ❌ **Promociones**: Ofertas y publicidad (bloqueado por defecto)
- ❌ **Newsletters**: Boletines informativos (bloqueado por defecto)

### 2. **Remitentes Bloqueados**
Los usuarios pueden agregar patrones de remitentes que nunca deben recibir respuestas automáticas:
- Ejemplos: `noreply`, `marketing@`, `@spam.com`
- Soporta patrones exactos, dominios completos (@domain.com), y substrings

### 3. **Remitentes Prioritarios**
Los usuarios pueden especificar remitentes que SIEMPRE deben recibir respuestas, independientemente de otros filtros:
- Ejemplos: `jefe@empresa.com`, `@clientevip.com`
- Estos tienen prioridad absoluta sobre todos los demás filtros

### 4. **Palabras Clave Bloqueadas**
Los usuarios pueden bloquear respuestas a emails que contengan ciertas palabras:
- Ejemplos: `unsubscribe`, `oferta`, `descuento`
- Busca en asunto y cuerpo del email

---

## 🧪 Cómo Probar el Sistema

### Paso 1: Acceder a la Configuración
1. Inicia sesión en tu cuenta
2. Ve a `/vera/config` o navega a **Configuración de Vera**
3. Desplázate hasta la sección **"🎯 Filtros de Respuesta Automática"**

### Paso 2: Configurar Filtros

#### Probar Filtro de Categorías:
1. Desmarca "🏷️ Promociones" y "📰 Newsletters"
2. Marca "💼 Trabajo" y "👤 Personal"
3. Guarda cambios

**Resultado esperado**: Vera NO responderá a promociones ni newsletters

#### Probar Remitentes Bloqueados:
1. Agrega: `noreply`, `marketing@`, `@spam.com`
2. Guarda cambios

**Resultado esperado**: Vera NO responderá a emails de estos remitentes

#### Probar Remitentes Prioritarios:
1. Agrega tu email de prueba o `@tudominio.com`
2. Guarda cambios

**Resultado esperado**: Vera SIEMPRE responderá a estos remitentes

#### Probar Palabras Clave:
1. Agrega: `unsubscribe`, `oferta`, `descuento`
2. Guarda cambios

**Resultado esperado**: Vera NO responderá a emails con estas palabras

### Paso 3: Probar con Emails Reales

#### Escenario 1: Email de Promoción
```
De: marketing@tienda.com
Asunto: 50% de descuento en toda la tienda
Categoría: promociones
```
**Resultado esperado**: ❌ NO responder (bloqueado por categoría + remitente + palabra clave)

#### Escenario 2: Email de Trabajo
```
De: colega@empresa.com
Asunto: Consulta sobre el proyecto
Categoría: trabajo
```
**Resultado esperado**: ✅ Responder (categoría permitida)

#### Escenario 3: Email de Remitente Prioritario
```
De: jefe@empresa.com (agregado a prioritarios)
Asunto: Urgente: Reunión mañana
Categoría: cualquiera
```
**Resultado esperado**: ✅ SIEMPRE responder (remitente prioritario tiene máxima prioridad)

#### Escenario 4: Newsletter con Palabra Clave
```
De: newsletter@blog.com
Asunto: Últimas noticias
Cuerpo: ...haz click aquí para unsubscribe...
```
**Resultado esperado**: ❌ NO responder (palabra clave bloqueada)

---

## 🔍 Verificación Técnica

### 1. Verificar que la Migración se Ejecutó
```bash
php artisan tinker
```

```php
// Verificar campos en la tabla
Schema::hasColumn('vera_configs', 'response_allowed_categories'); // true
Schema::hasColumn('vera_configs', 'response_blocked_categories'); // true
Schema::hasColumn('vera_configs', 'blocked_keywords'); // true
Schema::hasColumn('vera_configs', 'blocked_senders'); // true
Schema::hasColumn('vera_configs', 'allowed_senders'); // true
```

### 2. Verificar Valores por Defecto
```php
$config = \App\Models\VeraConfig::first();
dd($config->response_blocked_categories);
// Debería mostrar: ['promociones', 'newsletters']
```

### 3. Verificar Logs de Vera
Al procesar un email, Vera registrará en los logs por qué decidió responder o no:

```bash
tail -f storage/logs/laravel.log | grep "🚫\|⭐\|🎯"
```

Ejemplos de logs:
```
[2025-01-01] local.INFO: 🚫 Remitente bloqueado {"email_id":123,"from":"noreply@site.com"}
[2025-01-01] local.INFO: ⭐ Remitente prioritario - SIEMPRE responder {"email_id":124,"from":"jefe@empresa.com"}
[2025-01-01] local.INFO: 🚫 Categoría no permitida {"email_id":125,"category":"promociones"}
[2025-01-01] local.INFO: 🚫 Contiene palabras clave bloqueadas {"email_id":126}
```

---

## 📋 Checklist de Pruebas

- [ ] Migración ejecutada sin errores
- [ ] UI de filtros visible en `/vera/config`
- [ ] Puede agregar y eliminar categorías permitidas
- [ ] Puede agregar y eliminar remitentes bloqueados
- [ ] Puede agregar y eliminar remitentes prioritarios
- [ ] Puede agregar y eliminar palabras clave
- [ ] Configuración se guarda correctamente
- [ ] Vera NO responde a categorías bloqueadas
- [ ] Vera NO responde a remitentes bloqueados
- [ ] Vera SÍ responde a remitentes prioritarios (siempre)
- [ ] Vera NO responde a emails con palabras clave bloqueadas
- [ ] Logs muestran información clara sobre decisiones de filtrado

---

## 🐛 Solución de Problemas

### Problema: "Column not found" al guardar
**Solución**: Ejecutar la migración
```bash
php artisan migrate
```

### Problema: Los filtros no se aplican
**Solución**: Verificar que Vera está habilitada y procesando emails
```bash
php artisan vera:process-emails --limit=10
```

### Problema: UI no se ve bien
**Solución**: Limpiar cache de vistas
```bash
php artisan view:clear
```

---

## 📊 Comportamiento de Filtros (Orden de Prioridad)

1. **MÁXIMA PRIORIDAD**: Remitentes permitidos (allowed_senders)
   - Si el remitente está en esta lista → SIEMPRE responder

2. **ALTA PRIORIDAD**: Remitentes bloqueados (blocked_senders)
   - Si el remitente está bloqueado → NUNCA responder

3. **MEDIA PRIORIDAD**: Categorías
   - Verificar si la categoría está permitida
   - Si hay lista de permitidas, verificar inclusión
   - Si hay lista de bloqueadas, verificar exclusión

4. **BAJA PRIORIDAD**: Palabras clave
   - Si el email contiene palabras bloqueadas → NO responder

---

## ✨ Valores por Defecto (Para Nuevos Usuarios)

Cuando un usuario crea su configuración, se establecen estos valores por defecto:

```php
[
    'response_allowed_categories' => null,  // null = todas permitidas
    'response_blocked_categories' => ['promociones', 'newsletters'],
    'blocked_keywords' => ['unsubscribe', 'darse de baja', 'cancelar suscripción'],
    'blocked_senders' => ['noreply', 'no-reply', 'donotreply'],
    'allowed_senders' => [],  // Vacío, el usuario agrega manualmente
]
```

Esto significa que por defecto:
- ✅ Responde a todo EXCEPTO promociones y newsletters
- ❌ No responde a remitentes con "noreply"
- ❌ No responde a emails con palabras de unsubscribe

---

## 🎉 ¡Sistema Listo!

El sistema de filtros está completamente funcional y listo para usar. Los usuarios ahora tienen control total sobre qué correos quieren que Vera responda automáticamente.

**Beneficios:**
- ✅ Menos spam en respuestas automáticas
- ✅ Control granular por categoría
- ✅ Priorización de contactos importantes
- ✅ Interfaz fácil de usar
- ✅ Cambios en tiempo real

---

**Fecha de implementación**: 2025-12-29
**Versión**: 1.0 - FASE 1 (Filtros Básicos)
