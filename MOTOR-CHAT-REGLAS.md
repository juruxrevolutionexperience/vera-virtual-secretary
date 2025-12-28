# Motor de Chat Basado en Reglas - Vera

## 🎯 Objetivo

Sistema potente que **simula inteligencia** sin usar IA, ahorrando costos y mejorando velocidad.

## 🚀 Ventajas sobre sistema con IA

✅ **Instantáneo** - Sin latencia de API
✅ **Gratis** - No consume tokens del usuario
✅ **Predecible** - Respuestas consistentes
✅ **Rápido** - Queries directas a la BD
✅ **Escalable** - Puede manejar miles de consultas simultáneas

## 🏗️ Arquitectura

### 1. Intent Recognition Engine

Detecta la **intención** del usuario mediante patrones:

```php
$intentPatterns = [
    'inbox' => [
        'keywords' => ['inbox', 'bandeja', 'correo', 'email'],
        'modifiers' => ['urgente', 'importante', 'pendiente'],
        'priority' => 10
    ]
];
```

**Cómo funciona:**
- Escanea el mensaje buscando keywords
- Asigna puntos según prioridad
- Los modifiers añaden puntos extra
- La intención con más puntos gana

**Ejemplos de detección:**
- "¿Qué emails tengo?" → `inbox` (10 puntos)
- "Emails urgentes de hoy" → `inbox` (10 + 2 + 2 = 14 puntos)
- "Dame un resumen" → `stats` (8 puntos)

### 2. Parameter Extraction

Extrae **parámetros** del mensaje:

```php
// Entrada: "emails urgentes de hoy"
// Salida:
[
    'time_filter' => 'today',
    'importance' => 'high'
]
```

**Parámetros soportados:**
- `time_filter`: today, yesterday, week, month
- `importance`: high
- `read_status`: unread
- `email_type`: newsletter, promotional

### 3. Response Generation

Genera respuestas **contextuales** con datos reales:

```php
switch ($intent) {
    case 'inbox':
        return $this->generateInboxResponse($params);
    case 'stats':
        return $this->generateStatsResponse($params);
    // ...
}
```

**Características:**
- Consulta la BD con filtros
- Genera respuesta en lenguaje natural
- Añade emoji apropiados
- Context-aware (cambia según hora del día)

### 4. Action Cards Generator

Genera **tarjetas interactivas** automáticamente:

```php
// Si pregunta por emails importantes
if ($intent === 'inbox' && $params['importance'] === 'high') {
    // Query a la BD
    $emails = Email::where('user_id', $user->id)
        ->where('is_starred', true)
        ->limit(3)
        ->get();

    // Generar cards
    foreach ($emails as $email) {
        $cards[] = [
            'icon' => '📧',
            'title' => $email->from_name,
            'subtitle' => $email->subject,
            'actions' => ['Ver', 'Responder']
        ];
    }
}
```

## 📊 Intenciones Soportadas

| Intención | Keywords | Ejemplo |
|-----------|----------|---------|
| `inbox` | inbox, email, correo | "Muéstrame emails urgentes" |
| `calendar` | agenda, reunión, calendario | "¿Qué reuniones tengo hoy?" |
| `stats` | estadística, resumen, actividad | "Resumen de la semana" |
| `help` | ayuda, help, qué puedes | "¿Qué puedes hacer?" |
| `greeting` | hola, buenos días | "Hola Vera" |
| `create_rule` | crear regla, automatizar | "Crear regla nueva" |
| `archive` | archivar, limpiar, organizar | "Archivar newsletters" |

## 🎨 Respuestas Context-Aware

### Por hora del día:
- **Mañana (< 12h)**: "¡Buenos días, Juan! Tienes 5 emails..."
- **Tarde (12h-19h)**: "¡Buenas tardes, Juan! Tienes 5 emails..."
- **Noche (> 19h)**: "¡Buenas noches, Juan! Tienes 5 emails..."

### Por estado del inbox:
- **0 emails**: "✨ ¡Tu bandeja está al día!"
- **1-5 emails**: "📧 Tienes X emails. ¿Quieres revisarlos?"
- **> 10 emails**: "📧 Tienes X emails. ¿Quieres que te ayude a organizarlos?"

### Por tipo de consulta:
- **Emails urgentes**: Tono más serio, action cards inmediatos
- **Estadísticas**: Incluye gráficos de tendencia
- **Ayuda**: Lista completa de comandos

## 🔄 Flujo Completo

```
Usuario: "emails importantes de hoy"
   ↓
1. Intent Detection
   → Detectado: "inbox" (10 puntos + modifiers)
   ↓
2. Parameter Extraction
   → time_filter: "today"
   → importance: "high"
   ↓
3. Database Query
   → Email::where('user_id', $user->id)
           ->where('is_starred', true)
           ->whereDate('received_at', today())
   ↓
4. Response Generation
   → "📧 Tienes 3 emails importantes de hoy."
   → + Lista de emails
   ↓
5. Action Cards
   → Card para cada email con botones [Ver] [Responder]
   ↓
6. Save to DB
   → Guardar conversación (tokens: 0)
   → Log de actividad
```

## 🛠️ Cómo Expandir

### Añadir nueva intención:

```php
// 1. Agregar al array de patrones
'invoice' => [
    'keywords' => ['factura', 'invoice', 'pago'],
    'modifiers' => ['pendiente', 'vencida'],
    'priority' => 9
]

// 2. Crear método de respuesta
private function generateInvoiceResponse(array $params): string
{
    // Tu lógica aquí
    $invoices = Invoice::where('user_id', $this->user->id)->get();

    return "💳 Tienes {$invoices->count()} facturas pendientes.";
}

// 3. Añadir al switch en generateResponse()
case 'invoice':
    return $this->generateInvoiceResponse($params);
```

### Añadir nuevo parámetro:

```php
// En extractParameters()
if (str_contains($messageLower, 'vencido')) {
    $params['status'] = 'overdue';
}
```

### Añadir nueva action card:

```php
// En generateActionCards()
if ($intent === 'invoice') {
    foreach ($invoices as $invoice) {
        $cards[] = [
            'icon' => '💳',
            'title' => "Factura #{$invoice->number}",
            'subtitle' => "Vence: {$invoice->due_date}",
            'actions' => [
                ['label' => 'Ver', 'action' => 'view_invoice'],
                ['label' => 'Pagar', 'action' => 'pay_invoice'],
            ]
        ];
    }
}
```

## 📈 Mejoras Futuras

### Fase 1 ✅ (Implementado)
- [x] Intent recognition básico
- [x] Parameter extraction
- [x] Response templates
- [x] Action cards
- [x] Context-awareness

### Fase 2 (Próximamente)
- [ ] **Learning System**: Aprender de conversaciones pasadas
- [ ] **Synonym Detection**: Detectar sinónimos (ej: "correos" = "emails")
- [ ] **Multi-intent**: Detectar múltiples intenciones en un mensaje
- [ ] **Smart Suggestions**: Sugerencias proactivas según patrones

### Fase 3 (Futuro)
- [ ] **Contextual Memory**: Recordar conversaciones previas
- [ ] **User Preferences**: Adaptar respuestas a preferencias del usuario
- [ ] **Advanced Queries**: Queries más complejas con joins
- [ ] **Predictive Actions**: Anticipar necesidades del usuario

## 🎯 Cuándo SÍ usar IA (Claude API)

El motor de reglas NO debe usarse para:

1. ❌ **Generar respuestas de emails** → Usa Claude API
2. ❌ **Analizar sentimiento** → Usa Claude API
3. ❌ **Categorizar emails complejos** → Usa Claude API
4. ❌ **Aprender estilo de escritura** → Usa Claude API
5. ❌ **Tareas creativas** → Usa Claude API

El motor de reglas ES PERFECTO para:

1. ✅ **Consultas a la BD** (emails, stats, agenda)
2. ✅ **Comandos simples** (/inbox, /stats)
3. ✅ **Respuestas predecibles** (saludos, ayuda)
4. ✅ **Acciones repetitivas** (archivar, organizar)
5. ✅ **Navegación** (mostrar datos, filtrar)

## 💡 Tips de Uso

### Para usuarios:
- Usa lenguaje natural: "¿Qué emails tengo pendientes?"
- Combina filtros: "emails urgentes de esta semana"
- Usa comandos rápidos: `/inbox`, `/stats`

### Para desarrolladores:
- Añade emojis apropiados en las respuestas
- Usa queries optimizadas (limit, select)
- Cachea contexto del usuario cuando sea posible
- Loggea intenciones detectadas para mejorar el sistema

## 📊 Métricas

El sistema loggea:
- Intención detectada
- Parámetros extraídos
- Tiempo de respuesta
- Action cards generadas
- Tokens consumidos: **0** 🎉

## 🔒 Seguridad

- ✅ Valida usuario antes de queries
- ✅ Usa Eloquent ORM (protección SQL injection)
- ✅ No ejecuta código arbitrario
- ✅ Filtra inputs del usuario
- ✅ Limita resultados de queries

---

**Creado por:** Sistema Vera
**Versión:** 1.0
**Última actualización:** 2025-12-28
