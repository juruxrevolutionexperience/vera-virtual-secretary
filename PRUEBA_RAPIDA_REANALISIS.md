# 🧪 PRUEBA RÁPIDA: Re-Análisis Semanal de Vera

## ✅ PASO 1: Ejecutar el comando manualmente

```bash
./vendor/bin/sail artisan vera:weekly-reanalysis --user-id=1 --force
```

**Deberías ver:**
```
🔄 Iniciando re-análisis semanal de Vera...

👥 Usuarios a re-analizar: 1

📊 Analizando usuario: prueba (ID: 1)
   ✅ Configuración actualizada
   📝 Emails analizados: 4246
   ✍️  Tono detectado: profesional
   📏 Longitud promedio: 500 caracteres

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📊 RESUMEN:
   ✅ Exitosos: 1
   ❌ Errores: 0
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

---

## ✅ PASO 2: Verificar que se actualizó en la BD

```bash
./vendor/bin/sail artisan tinker

# Ejecuta:
$config = \App\Models\VeraConfig::first();
$config->last_re_analysis_at; // Debe ser ahora
$config->analysis_summary; // Debe tener datos nuevos
$config->writing_style; // Debe tener el estilo detectado
```

---

## ✅ PASO 3: Verificar el scheduler automático

```bash
# Ver todos los schedules programados
./vendor/bin/sail artisan schedule:list

# Debería aparecer:
# vera-weekly-reanalysis (cada domingo 2:00 AM)
```

---

## 🔄 FRECUENCIA DE RE-ANÁLISIS

Por defecto: **Cada 30 días** (configurado en `re_analysis_frequency_days`)

Puedes cambiarla en el onboarding o después en la configuración de Vera.

---

## 💡 TIP

Si quieres que se re-analice más seguido (ej: cada 7 días):

```bash
./vendor/bin/sail artisan tinker

$config = \App\Models\VeraConfig::first();
$config->update(['re_analysis_frequency_days' => 7]);
```

Ahora se re-analizará cada 7 días automáticamente.
