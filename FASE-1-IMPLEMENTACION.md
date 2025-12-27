# Fase 1: Base de datos y registro - COMPLETADA ✅

## Resumen de implementación

Hemos completado exitosamente la **Fase 1** del sistema de gestión de planes y tokens para Vera.

### ✅ Archivos creados

#### Migraciones (3)
1. `database/migrations/2025_12_27_200000_create_vera_token_usage_table.php`
   - Tabla para registrar cada uso de tokens de la API de Gemini
   - Incluye: user_id, email_id, action_type, tokens, model, cost, metadata

2. `database/migrations/2025_12_27_200100_add_plan_fields_to_users_table.php`
   - Añade campos de plan a la tabla users
   - Campos: plan_type, monthly_email_limit, billing_cycle_start, plan_metadata

3. `database/migrations/2025_12_27_200200_add_processing_fields_to_emails_table.php`
   - Añade campos de control de procesamiento a emails
   - Campos: processed_this_billing_cycle, last_processed_at, vera_ai_skipped

#### Modelos (1)
4. `app/Models/VeraTokenUsage.php`
   - Modelo completo con scopes, relaciones y métodos helper
   - Métodos: record(), getStatsByUser(), getTopConsumingEmails(), detectAnomalies()

#### Eventos y Listeners (2)
5. `app/Events/VeraTokensConsumed.php`
   - Evento que se dispara cada vez que se consumen tokens

6. `app/Listeners/RecordTokenUsage.php`
   - Listener que registra automáticamente el uso en la BD

#### Servicios modificados (3)
7. `app/Services/AI/GeminiProvider.php` ✏️ MODIFICADO
   - Ahora registra automáticamente tokens al llamar analyze()
   - Nuevos parámetros: userId, emailId, actionType, metadata

8. `app/Services/VeraService.php` ✏️ MODIFICADO
   - Actualizado para pasar parámetros a GeminiProvider
   - Registra automáticamente análisis de emails

9. `app/Services/VeraResponseGenerator.php` ✏️ MODIFICADO
   - Actualizado para registrar generación de respuestas
   - Incluye metadata de contexto

#### Seeders (1)
10. `database/seeders/UserPlansSeeder.php`
    - Inicializa usuarios existentes con plan Basic
    - Establece límites y ciclo de facturación

---

## 🚀 Cómo ejecutar

### 1. Ejecutar las migraciones

```bash
php artisan migrate
```

Esto creará:
- Tabla `vera_token_usage`
- Campos nuevos en `users` (plan_type, monthly_email_limit, etc.)
- Campos nuevos en `emails` (processed_this_billing_cycle, etc.)

### 2. Ejecutar el seeder (opcional, pero recomendado)

```bash
php artisan db:seed --class=UserPlansSeeder
```

Esto asignará el plan Basic (100 emails/mes) a todos los usuarios existentes.

### 3. Verificar que todo funciona

```bash
# Ver las tablas creadas
php artisan tinker

# En tinker:
>>> \App\Models\VeraTokenUsage::count()
=> 0 # Normal, aún no hay registros

>>> \App\Models\User::first()->plan_type
=> "basic" # ✅ Plan asignado correctamente

>>> \App\Models\User::first()->monthly_email_limit
=> 100 # ✅ Límite establecido

>>> exit
```

---

## 📊 ¿Qué hace ahora el sistema?

### Registro automático de tokens

Cada vez que Vera usa la API de Gemini para:
1. **Analizar un email** → Se registra automáticamente con `action_type = 'email_analysis'`
2. **Generar una respuesta** → Se registra automáticamente con `action_type = 'response_generation'`
3. **Chat (futuro)** → Se registrará con `action_type = 'chat_interaction'`

### Datos que se guardan

Por cada acción:
```php
[
    'user_id' => 5,
    'email_id' => 1234,
    'action_type' => 'email_analysis',
    'prompt_tokens' => 234,
    'completion_tokens' => 87,
    'total_tokens' => 321,
    'model' => 'gemini-1.5-flash',
    'cost' => 0.000000, // Por ahora gratis
    'metadata' => [
        'subject' => 'Consulta sobre proyecto',
        'from' => 'client@example.com',
        'content_length' => 1456,
    ],
    'created_at' => '2024-12-27 14:35:22',
]
```

### Planes de usuario

Ahora cada usuario tiene:
- **plan_type**: 'basic', 'starter', o 'pro'
- **monthly_email_limit**: 100, 250, o 450 emails según plan
- **billing_cycle_start**: Fecha de inicio del mes de facturación
- **plan_metadata**: JSON con historial y promociones

---

## 🧪 Probar el sistema

### Prueba 1: Analizar un email y verificar registro

```bash
php artisan tinker
```

```php
// Obtener un email
$email = \App\Models\Email::first();

// Analizar con Vera (esto debería registrar tokens automáticamente)
$veraService = new \App\Services\VeraService();
$analysis = $veraService->analyzeEmail($email);

// Verificar que se registró
$usage = \App\Models\VeraTokenUsage::latest()->first();
dd($usage->toArray());

// Deberías ver:
// [
//   'user_id' => X,
//   'email_id' => X,
//   'action_type' => 'email_analysis',
//   'total_tokens' => ~500,
//   ...
// ]
```

### Prueba 2: Ver estadísticas de un usuario

```php
use App\Models\VeraTokenUsage;

$userId = 1;
$stats = VeraTokenUsage::getStatsByUser($userId);

print_r($stats);
// Output:
// [
//   'total_actions' => 12,
//   'total_tokens' => 8034,
//   'avg_tokens_per_action' => 669,
//   'by_action_type' => [
//     'email_analysis' => [
//       'count' => 8,
//       'total_tokens' => 4234,
//       'avg_tokens' => 529,
//     ],
//     'response_generation' => [
//       'count' => 4,
//       'total_tokens' => 5800,
//       'avg_tokens' => 1450,
//     ],
//   ],
// ]
```

### Prueba 3: Detectar anomalías

```php
$anomalies = VeraTokenUsage::detectAnomalies(3.0);

// Esto devuelve registros que consumieron 3x más tokens que el promedio
foreach ($anomalies as $anomaly) {
    echo "User {$anomaly->user->name} consumió {$anomaly->total_tokens} tokens (email: {$anomaly->email->subject})\n";
}
```

---

## 🎯 Lo que viene después

### Fase 2: Lógica de negocio (Próxima)

Crear el servicio `VeraUsageService` con métodos:
- `calculateMonthlyUsage(User $user)`
- `getEmailsProcessedThisMonth(User $user)`
- `getRemainingEmails(User $user)`
- `canProcessEmail(User $user)` → Verificar límites
- `recordEmailProcessing(...)` → Marcar email como procesado

### Fase 3: Control de límites

Crear middleware `VeraEmailLimitMiddleware` que:
- Verifica si el usuario puede procesar más emails
- Bloquea si alcanzó el límite
- Devuelve error 429 (Too Many Requests)

### Fase 4: Vista de usuario

Crear la interfaz en `/settings/usage` que muestre:
- Emails procesados este mes
- Barra de progreso
- Proyección de cuándo alcanzará el límite
- Botón de upgrade

### Fase 5: Vista Admin

Panel de analytics en `/admin/vera/token-analytics` con:
- Tokens consumidos totales
- Distribución por acción
- Detección de anomalías
- Exportar CSV

---

## ✅ Checklist Fase 1 - COMPLETADO

- [x] Migración para tabla `vera_token_usage`
- [x] Migración para campos de plan en `users`
- [x] Migración para campos de procesamiento en `emails`
- [x] Modelo `VeraTokenUsage` con métodos helper
- [x] Evento `VeraTokensConsumed`
- [x] Listener `RecordTokenUsage`
- [x] Modificar `GeminiProvider` para registrar tokens
- [x] Modificar `VeraService` para pasar parámetros
- [x] Modificar `VeraResponseGenerator` para pasar parámetros
- [x] Seeder `UserPlansSeeder` para inicializar usuarios

---

## 📝 Notas importantes

1. **El sistema YA está registrando tokens automáticamente**
   - No necesitas hacer nada más
   - Cada vez que Vera use Gemini, se guardará en la BD

2. **Los usuarios existentes tienen plan Basic por defecto**
   - 100 emails/mes
   - Ejecuta el seeder para asignarles el plan

3. **El costo es 0 por ahora**
   - Gemini 1.5 Flash es gratis (tier actual)
   - Cuando migremos a tier de pago, se calculará automáticamente

4. **Los límites aún NO se están aplicando**
   - Fase 1 solo REGISTRA el uso
   - Fase 2 y 3 implementarán el CONTROL de límites

5. **Compatibilidad con código existente**
   - El método `analyze()` sigue funcionando sin parámetros opcionales
   - Solo cuando pasas `userId` se registra el uso
   - No rompe nada del código actual

---

## 🐛 Troubleshooting

### Error: "Class 'VeraTokensConsumed' not found"

Ejecuta:
```bash
composer dump-autoload
```

### Error: "Table 'vera_token_usage' doesn't exist"

Ejecuta las migraciones:
```bash
php artisan migrate
```

### Error: "SQLSTATE[42S22]: Column not found: 'plan_type'"

La migración de users no se ejecutó. Verifica:
```bash
php artisan migrate:status

# Si no aparece, ejecuta:
php artisan migrate
```

### No se están registrando tokens

Verifica que el listener esté registrado:
```bash
php artisan event:list

# Deberías ver:
# App\Events\VeraTokensConsumed
#   App\Listeners\RecordTokenUsage
```

Si no aparece, Laravel 11+ usa auto-discovery. Asegúrate de que:
1. El listener implementa la interfaz correctamente
2. El nombre del método es `handle(VeraTokensConsumed $event)`

---

## 📖 Documentación completa

Consulta `gestion-planes-y-tokens.md` para la documentación completa del sistema.

---

**Fecha de implementación**: 27 diciembre 2024
**Fase**: 1 de 6
**Estado**: ✅ COMPLETADO
**Próxima fase**: Lógica de negocio (VeraUsageService)
