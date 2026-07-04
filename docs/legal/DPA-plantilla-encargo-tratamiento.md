# Contrato de Encargo de Tratamiento de Datos (DPA)

> **PLANTILLA — Requiere revisión por un abogado antes de firmar con el primer cliente de pago.**
> Basada en el artículo 28 del RGPD (Reglamento UE 2016/679) y la LOPDGDD 3/2018.
> Rellenar los campos entre corchetes. Este documento se firma como anexo al contrato de servicio de Vera.

---

## 1. Partes

- **Responsable del tratamiento**: [NOMBRE DEL NEGOCIO CLIENTE], con NIF [___], domicilio en [___] (en adelante, el "Responsable").
- **Encargado del tratamiento**: [JURUX / NOMBRE FISCAL DEL TITULAR], con NIF [___], domicilio en [___] (en adelante, el "Encargado").

## 2. Objeto

El Encargado presta al Responsable el servicio "Vera", una secretaria virtual que gestiona comunicaciones y citas con los clientes del Responsable a través de email, Telegram, WhatsApp y páginas web de reserva. Para prestar el servicio, el Encargado trata datos personales por cuenta del Responsable.

## 3. Datos tratados

| Categoría | Datos |
|-----------|-------|
| Identificativos | Nombre, email, número de teléfono, identificador de Telegram/WhatsApp |
| Comunicaciones | Contenido de los mensajes intercambiados con la secretaria virtual |
| Citas | Fecha, hora, servicio solicitado, notas de la reserva |
| Perfil de interacción | Preferencias horarias, temas tratados, estilo de comunicación (generados automáticamente para mejorar la atención) |

**Categorías de interesados**: clientes, pacientes y potenciales clientes del Responsable.

**No se tratan** categorías especiales de datos (art. 9 RGPD). Si un interesado incluye datos de salud en un mensaje libre (p. ej. describe una dolencia a una clínica), el sistema está instruido para no procesarlos con fines distintos a la gestión de la cita y el Responsable se compromete a no solicitarlos a través del servicio.

## 4. Duración

Este contrato dura lo que dure la prestación del servicio. A su finalización, el Encargado suprimirá o devolverá (a elección del Responsable) todos los datos personales, salvo los que deba conservar por obligación legal.

## 5. Obligaciones del Encargado

El Encargado se compromete a:

1. Tratar los datos únicamente siguiendo instrucciones documentadas del Responsable y solo para prestar el servicio.
2. Garantizar la confidencialidad: las personas autorizadas a tratar los datos se comprometen a confidencialidad.
3. Aplicar las medidas técnicas y organizativas del **Anexo I**.
4. No subcontratar sin autorización. Los subencargados actuales se listan en el **Anexo II**; el Encargado notificará cambios con [30] días de antelación, pudiendo el Responsable oponerse.
5. Asistir al Responsable en la respuesta al ejercicio de derechos (acceso, rectificación, supresión, oposición, limitación, portabilidad) de los interesados. El Encargado dispone de mecanismos técnicos de supresión selectiva por interesado.
6. Notificar al Responsable sin dilación indebida (máximo [48] horas desde su conocimiento) las violaciones de seguridad que afecten a los datos.
7. Poner a disposición del Responsable la información necesaria para demostrar cumplimiento y permitir auditorías razonables.
8. Suprimir los datos al finalizar el servicio conforme a la cláusula 4.

## 6. Obligaciones del Responsable

1. Informar a sus clientes (interesados) del tratamiento, incluyendo el uso de un asistente automatizado, en su propia política de privacidad.
2. Garantizar la base jurídica del tratamiento (normalmente: ejecución de contrato o medidas precontractuales — gestión de citas solicitadas por el propio interesado).
3. Atender los ejercicios de derechos de sus clientes, solicitando al Encargado la ejecución técnica cuando aplique.
4. No usar el servicio para tratar categorías especiales de datos ni para fines distintos de la gestión de comunicaciones y citas.

## 7. Política de retención

- Contenido de las conversaciones: se anonimiza automáticamente a los **24 meses**.
- Registros de citas: se conservan sin datos identificativos tras la supresión de un interesado.
- Logs técnicos: los identificadores personales se registran enmascarados; los registros de actividad se purgan a los 90 días.

## 8. Transferencias internacionales

El servicio utiliza proveedores de inferencia de lenguaje ubicados en Estados Unidos (ver Anexo II), amparados en [Cláusulas Contractuales Tipo / Marco de Privacidad de Datos UE-EE.UU., verificar según proveedor]. No se utilizan los datos del Responsable para entrenar modelos de terceros.

---

## Anexo I — Medidas de seguridad

- Cifrado TLS en todas las comunicaciones.
- Tokens y credenciales de terceros cifrados en reposo (AES-256 vía Laravel encrypted casts).
- Aislamiento de datos por cliente: base de conocimiento vectorial separada por negocio (una colección por cliente); imposibilidad técnica de cruce entre negocios.
- Autenticación de servicios internos por token secreto con comparación en tiempo constante; servicio de IA accesible solo desde localhost.
- Webhooks verificados criptográficamente (firma HMAC-SHA256 en WhatsApp; token secreto en Telegram).
- Enlaces públicos de reserva/cancelación: tokens de un solo uso de 160 bits con caducidad.
- Rate-limiting anti-abuso en todos los canales de entrada.
- Defensas contra inyección de instrucciones en el modelo de lenguaje (delimitación de contenido + reglas de sistema).
- Datos personales enmascarados en logs técnicos.
- Firewall a nivel de servidor (solo puertos 22/80/443) + WAF Cloudflare.
- Copias de seguridad periódicas [detallar frecuencia y cifrado cuando esté definido].

## Anexo II — Subencargados

| Subencargado | Servicio | Ubicación | Garantía de transferencia |
|--------------|----------|-----------|---------------------------|
| [Proveedor hosting/Forge] | Infraestructura del servidor | [UE — verificar región] | — |
| Groq Inc. | Inferencia LLM (generación de respuestas) | EE.UU. | [SCC/DPF — verificar] |
| Google LLC | Inferencia LLM de respaldo y embeddings (Gemini), Calendar/Gmail si el Responsable los conecta | EE.UU. | [SCC/DPF — verificar] |
| Meta Platforms | Canal WhatsApp Business (si el Responsable lo activa) | EE.UU. | [SCC/DPF — verificar] |
| Telegram | Canal Telegram (si el Responsable lo activa) | — | [verificar] |
| Cloudflare | CDN / WAF | UE/EE.UU. | [SCC/DPF — verificar] |
| [Proveedor email transaccional] | Envío de emails de notificación | [verificar] | [verificar] |

---

Firmado en [lugar], a [fecha].

| El Responsable | El Encargado |
|----------------|--------------|
| Fdo.: | Fdo.: |
