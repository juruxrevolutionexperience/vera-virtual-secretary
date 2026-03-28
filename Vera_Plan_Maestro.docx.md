

| VERA Asistente de Agenda IA Plan Maestro de Producto |
| :---: |

| *"Vera gestiona tu comunicacion y convierte cada conversacion en una reunion confirmada."* |
| :---: |

Syntra AI  |  Confidencial  |  2025

# **Indice**

1\. Identidad y Mision de Vera

2\. Funciones — Que hace y que NO hace

3\. El Core Loop — Flujo de conversacion

4\. El Cerebro de Vera — Arquitectura tecnica

5\. Onboarding del cliente

6\. Plan de 90 dias

7\. Division del equipo

8\. Reglas de oro

9\. Ventaja de mercado

# **1\. Identidad y Mision de Vera**

| ¿Que es Vera? Chief of Staff virtual. Un empleado, un trabajo, hecho perfecto. |
| :---- |

## **Ejemplo práctico**

El fisioterapeuta termina su jornada. Vera le envía un resumen: "Hoy has confirmado 4 reuniones. Mañana tienes 10 citas: la primera a las 9am con Carlos García, la última a las 18h con María López. Hay 1 consulta pendiente de respuesta.

## **La promesa en una frase**

"Vera gestiona tu comunicacion y convierte cada conversacion en una reunion confirmada."

## **¿Por que existe Vera?**

Cada dia pierdes tiempo respondiendo mensajes, mirando el calendario, proponiendo horarios, esperando confirmaciones y creando eventos manualmente. Vera elimina todo ese ciclo para que tu solo aparezcas a la reunion.

## **¿Para quien es Vera?**

| Perfil de cliente | Contexto |
| :---- | :---- |
| Coaches y formadores | Cualquier negocio donde el tiempo del fundador vale dinero |
| Consultores independientes | Empresas con alta rotacion de reuniones |
| Agencias de marketing | Solopreneurs con agenda saturada |
| Abogados y gestorías | Profesionales con alta demanda de reuniones y tiempo del fundador muy valioso. Sin riesgo legal adicional al RGPD estándar. |
| Clinicas dentales. | TBC |

## **Pricing**

| Tier | Precio y descripcion |
| :---- | :---- |
| Vera Básica | 20 EUR/mes — Email \+ WhatsApp, hasta 30 reuniones/mes, 1 usuario, husos horarios. |
| Vera Pro | 50 EUR/mes —  Email \+ WhatsApp \+ Telegram, reuniones ilimitadas, 1 usuario, husos horarios. |
| Vera Equipo | 150 EUR/mes — Todo lo anterior \+ múltiples agendas, varios usuarios, soporte prioritario. |

# **2\. Funciones — Que hace y que NO hace**

## **Lo que Vera SÍ hace**

| Comunicacion | Responde emails, WhatsApp y Telegram con informacion real del negocio. Mantiene el tono configurado por el usuario. |
| :---- | :---- |
| **Disponibilidad** | Consulta Google Calendar en tiempo real y propone slots validos dentro del horario laboral configurado. |
| **Negociacion** | Gestiona contrapropuestas de horario del cliente hasta confirmar la reunión. Cuando no hay disponibilidad en el horario solicitado, Vera envía en un único mensaje todas las opciones disponibles del día. El cliente elige directamente. Minimiza intercambios y cierra la reunión más rápido. |
| **Eventos** | Crea, modifica y cancela eventos en Google Calendar. Envia confirmacion a ambas partes y recordatorio previo. |
| **24/7** | Disponible siempre. El cliente escribe el domingo a las 22h. Vera responde, propone horario y agenda. Tu te enteras el lunes con la reunion ya confirmada. |
| **Husos Horarios** | Gestiona automáticamente las diferencias horarias entre el negocio y el cliente. Comunica siempre al cliente en su hora local. Internamente opera en hora español |
| **Notificaciones**  | Resumen diario de toda la actividad de Vera: conversaciones gestionadas, reuniones confirmadas y lagunas detectadas. El dueño lo revisa cuando quiere. Notificación inmediata solo cuando Vera escala una conversación que requiere atención urgente. |

## **Limite duro — Lo que Vera NO hace**

| No cualifica leads | No evalúa si un cliente potencial merece la reunión comercialmente. Su trabajo es agendar, no filtrar. |
| :---- | :---- |
| **No es soporte** | No resuelve incidencias ni quejas. Las detecta y escala al humano con notificacion. |
| **No inventa** | Si no tiene la informacion en su cerebro con score suficiente, usa el unknown\_response configurado por el usuario. |
| **No decide** | No toma decisiones que corresponden al humano. Su unico trabajo es agendar. |
| **No almacena** | No guarda datos personales del cliente final más allá de lo necesario para confirmar la reunión. Vera gestiona, no retiene. |

# 

# 

# **3\. El Core Loop — Flujo de conversacion**

El embudo de Vera siempre termina igual. Independientemente del canal o el mensaje inicial, el destino siempre es el mismo: una reunion confirmada en Google Calendar.

## **Los tres modos de conversacion**

| Modo | Descripcion |
| :---- | :---- |
| Modo Respuesta | El cliente tiene una pregunta. Vera responde con la informacion disponible. No fuerza agenda, pero siempre deja la puerta abierta. Ejemplo: "¿Tenéis disponibilidad esta semana? |
| Modo Agenda | El cliente quiere una reunion directamente. Vera propone slots, negocia, confirma y crea el evento.Ejemplo: "Quiero una reunión el martes a las 10” |
|  Modo Transicion | Empieza como pregunta, deriva a reunion. Vera responde primero, luego ofrece agendar de forma natural. Ejemplo: "Me interesa lo que hacéis, ¿podemos hablar? |

## **El flujo completo paso a paso**

| 1 | Mensaje entrante — Email, WhatsApp o Telegram. Vera escucha los tres canales simultaneamente. |
| :---: | :---- |
| **2** | Deteccion de intencion — Vera identifica el modo: Respuesta, Agenda o Transición. Ejemplos internos de referencia: '¿tenéis disponibilidad esta semana?' → Modo Respuesta. '¿podemos hablar?' → Modo Transición. 'Quiero una reunión el martes a las 10' → Modo Agenda." |
| **3** | Verificacion de escalado — Analiza el contexto completo del mensaje para detectar si la intención es una queja, una crisis o una petición urgente legítima. No se basa en palabras sueltas sino en la intención completa. |
| **4** | Consulta el Cerebro — Carga el contexto fijo completo (JSON) \+ busca en RAG por similitud semantica. |
| **5** | Umbral de confianza — Si el score del fragmento RAG es menor a 0.75, lo descarta. Vera prefiere no saber que saber mal. |
| **6** | Respuesta con informacion real — Nunca inventa. Siempre lleva la conversacion hacia agendar una reunion de forma natural. |
| **7** | Consulta disponibilidad real — Google Calendar API. Respeta horario laboral, duracion de reunion y buffer entre citas. |
| **8** | Negociacion de horario — Propone slots en el horario solicitado. Si no hay disponibilidad, envía todas las opciones del día en un único mensaje. El cliente elige directamente. Minimiza intercambios y cierra la reunión más rápido." |
| **9** | Creacion del evento — Crea el evento en Google Calendar. Notificación a ambas partes en su huso horario local. Recordatorio automático previo. |
| **10** | Modo supervisado vs autonomo —  Supervisado: Vera sugiere, usuario aprueba. Autónomo: Vera actúa directamente. Recomendación: activar modo supervisado siempre al inicio. Pasar a autónomo cuando Vera haya confirmado X reuniones sin errores y el usuario confíe en su funcionamiento. |

| Escalado automatico: Cuando Vera detecta palabras criticas, para inmediatamente, notifica al humano y envia el unknown\_response. Nunca improvisa en situaciones sensibles. |
| :---- |

# **4\. El Cerebro de Vera — Arquitectura tecnica**

El cerebro son dos capas distintas que trabajan juntas. Esta arquitectura es la que determina si Vera genera confianza o la destruye.

| Capa 1 — Contexto fijo JSON en BD relacional (Laravel). Se inyecta completo en cada prompt. Siempre disponible. |
| :---- |

| Campo | Descripcion |
| :---- | :---- |
| business\_name | Nombre del negocio. Vera lo usa en sus respuestas. |
| owner\_name | Nombre del dueno. Para escalados: 'Te paso con \[nombre\]'. |
| tone | formal | cercano | profesional. Define el registro de todas las respuestas. |
| language | es | en | ca — idioma base de Vera. |
| timezone | Huso horario del negocio. Vera lo usa internamente para gestionar disponibilidad. Comunica siempre al cliente en su hora local |
| working\_hours | JSON { lun-vie: '9-18', sab: '10-13', dom: null }. Vera nunca propone fuera de esto. |
| meeting\_duration | Duracion default de reunion en minutos: 30 | 45 | 60\. |
| buffer\_minutes | Tiempo entre reuniones. Vera no agenda back-to-back. |
| unknown\_response | Que dice Vera cuando no tiene la respuesta. El usuario lo redacta. |
| escalation\_trigger | Array de palabras que activan escalado al humano. |
| mode | supervised | autonomous. Vera envia sola o espera aprobacion. |
| vera\_signature | Como firma Vera. 'Vera, asistente de \[negocio\]'. |

| Capa 2 — Conocimiento RAG BD vectorial existente. Solo se consulta cuando la conversacion lo necesita. Score minimo 0.75. |
| :---- |

| Campo | Descripcion |
| :---- | :---- |
| type | faq | service | price | policy | custom. Para filtrar en la busqueda. |
| title | Nombre legible del documento. 'Precios sesiones individuales'. |
| content | El contenido en texto plano. Se chunkea antes de vectorizar. |
| embedding | El vector generado del chunk. pgvector o la BD vectorial actual. |
| active | El usuario puede desactivar documentos sin borrarlos. |
| source | manual | url | pdf | whatsapp\_import. Para auditoria. |

## **Reglas criticas de consulta RAG**

| 1 | NUNCA uses un fragmento con score menor a 0.75 — Por debajo del umbral el resultado es ruido, no señal. Vera prefiere no saber que saber mal. Nota: el umbral de 0.75 es un punto de partida. Hay que analizar los scores reales en producción para determinar si es el óptimo o necesita ajuste. |
| :---: | :---- |
| **2** | FILTRA siempre por user\_id — Cada usuario tiene su espacio vectorial aislado. Nunca mezcles documentos entre clientes. Error de arquitectura grave si no esta bien aislado. |
| **3** | MAXIMO 3 fragmentos por consulta — Mas fragmentos no significa mejor respuesta. Significa mas ruido en el contexto y mas coste de tokens. |
| **4** | HISTORIAL limitado a los ultimos 10 mensajes — Ventana deslizante. Contexto sin disparar el coste en conversaciones largas. |

## **Validacion antes de construir el prompt**

| Regla critica: Si working\_hours esta vacio, Vera no arranca. Si tone no esta configurado, Vera no arranca. Valida que el cerebro esta completo ANTES de construir el prompt. Un prompt con variables vacias es peor que no tener prompt. Huso horario del cliente — Se detecta dinámicamente en cada conversación. Vera identifica la zona horaria del cliente y convierte los slots automáticamente antes de responder. |
| :---- |

## 

## 

## **El sistema de lagunas — El cerebro que aprende**

Cada vez que Vera escala por falta de información, el dashboard muestra 'Pregunta sin respuesta: ¿cuánto cuesta X?'. El usuario puede añadir esa información al cerebro con un clic.

El cerebro mejora con el uso, pero necesita que el dueño revise y complete las lagunas activamente. Revisar las lagunas es parte del trabajo con Vera desde el onboarding, no un extra.

Adicionalmente, Vera mejora con entrenamiento — conversaciones reales etiquetadas como correctas e incorrectas. Ejemplo: 50 conversaciones de WhatsApp, 32 bien gestionadas y 18 mal a propósito. Vera identifica el fallo en cada caso y aprende cómo debería haberlo resuelto

# **5\. Onboarding del cliente**

El objetivo es que en menos de 10 minutos, Vera ya este gestionando algo real. Cada paso es obligatorio antes de pasar al siguiente.

| 1 | Tu negocio en 2 minutos — Nombre del negocio, tu nombre, tono (3 opciones con ejemplo real), idioma, huso horario del negocio. OBLIGATORIO. Sin esto Vera no arranca. |
| :---: | :---- |
| **2** | Tu horario y duracion de reuniones — Selector visual de dias y horas. Duracion default de reunion (30/45/60 min). Buffer entre citas. OBLIGATORIO. Esto determina que slots propone Vera. |
| **3** | Lo que Vera necesita saber de tu negocio — Tres opciones: escribelo tu (editor libre), pega tu web (Vera extrae la info relevante), sube un PDF (Vera lo vectoriza automaticamente). |
| **4** | Conecta Google Calendar y canal — OAuth con Google. Luego elegir canal: Email o WhatsApp Business. Este paso puede hacerse despues — Vera arranca en modo email por defecto. |

| Cuando Vera no tiene info suficiente: Vera NUNCA inventa. Si la busqueda RAG no devuelve nada con score suficiente, Vera usa el unknown\_response configurado por el usuario. Por defecto: 'Dejame consultarlo y te respondo en breve.' Y notifica al propietario. |
| :---- |

# **6\. Plan de 60 dias**

| MES 1 — Construcción del núcleo Semanas 1-4. Dev foco 100%. Marketing observa y prepara.. |
| :---- |

**Semana 1 — Auditoria y decisiones**

| 1 | Mapear que codigo actual sirve para el nuevo Vera (RAG, automatizaciones Python, auth) |
| :---: | :---- |
| **2** | Listar que hay que eliminar, que adaptar, que construir desde cero |
| **3** | Definir el flujo tecnico completo de los 3 modos de conversacion en un doc interno |
| **4** | Decidir estructura del Cerebro de Vera — que campos, como se almacena, como se consulta |

**Semanas 2-3 — Construir el nucleo**

| 1 | Limpiar el codigo — eliminar todo lo que no es secretaria de agenda |
| :---: | :---- |
| **2** | Construir onboarding del Cerebro de Vera (formulario de configuracion basico) |
| **3** | Implementar los 3 modos de conversacion con deteccion automatica |
| **4** | Integracion Google Calendar funcionando end-to-end |
| **5** | Modo supervisado — Vera sugiere, usuario aprueba antes de enviar |
| **6** | RAG vectorial con umbral 0.75 y sistema de lagunas |
| **7** | Almacenamiento de conversaciones, reuniones y datos del cliente |

**Semana 4 — Canal WhatsApp \+ prueba interna**

| 1 | Integracion WhatsApp Business API funcionando |
| :---: | :---- |
| **2** | Clasificación de correos por nivel de urgencia (rojo, amarillo, azul) |
| **3** | Dashboard mínimo: ver conversaciones, resumen diario, pausar/activar |
| **4** | Integración Stripe para pagos |
| **5** | Prueba interna completa con el equipo — simular conversaciones reales |
| **6** | Lista de 20 personas para beta — consultores, coaches, pequeños negocios |

| Criterio de salida del Mes 1: Vera completa un ciclo completo end-to-end sin intervención humana en un entorno controlado. |
| :---- |

| MES 2 — Beta, llamadas y lanzamiento Semanas 5-8. El producto toca usuarios reales por primera vez. |
| :---- |

**Semanas 5-6 — Beta con 5 usuarios**

| 1 | Onboarding manual con los primeros 5 beta testers. |
| :---: | :---- |
| **2** | Observar cada conversación que Vera gestiona — tomar notas de errores y fricciones |
| **3** | Corregir errores críticos detectados |
| **4** | Integración de llamadas — usuario llama, Vera gestiona la cita y envía confirmación por WhatsApp |
| **5** | Marketing: capturar primeros testimonios reales con número |

**Semanas 7-8 — Iterar, ampliar y lanzar**

| 1 | Ampliar beta a 10 usuarios con onboarding más autónomo |
| :---: | :---- |
| **2** | Landing page con posicionamiento — resultado con número, no features |
| **3** | Video demo de 60 segundos — Vera gestionando una conversación real de WhatsApp |
| **4** | Ofrecer conversión a los 10 beta testers — descuento de early adopter |
| **5** | Primer cliente de pago cerrado — objetivo mínimo 1, objetivo real 5s |
| **6** | Publicar caso de éxito real del beta con métricas concretas |

| Criterio de salida del Mes 2: Al menos 1 cliente de pago. Métricas concretas: reuniones confirmadas sin intervención del dueño, horas ahorradas por semana, porcentaje de conversaciones resueltas sin escalar. |
| :---- |

# **7\. División del equipo**

| Persona | Mes 1 | Mes 2 |
| :---- | :---- | :---- |
| **TU (Dev)** | Arquitectura, refactorización, integraciones, Stripe. Nada de ventas ni marketing. | Corregir errores de beta. Integración llamadas. Infraestructura de lanzamiento. |
| **MK (Marketing)** | Contenido 'building in public' en LinkedIn desde el día 1\. | Capturar testimonios beta con métricas. Landing page. Lanzamiento y outreach a 50 leads directos. |
| **IA (Especialista)** | Aprender la arquitectura actual de Vera. Ownership de prompts y guardrails. | Optimización del comportamiento en producción. Clasificación de correos. Sistema de entrenamiento. |

## 

# 

# **8\. Reglas de oro**

Si las sigues, llegas al objetivo. Si las rompes, te pierdes.

| 1 | Vera hace una cosa. Solo una. — Cada vez que alguien proponga anadir una feature nueva, la respuesta es: '¿Va al backlog o cancela algo del plan?' No hay termino medio. |
| :---: | :---- |
| **2** | Usuario real antes que feature perfecta. — Un usuario usando Vera imperfecta vale mas que Vera perfecta sin usuarios. Lanza antes de estar listo. |
| **3** | Cero errores criticos es la unica metrica tecnica. — Vera puede ser lenta, puede ser mejorable. No puede mandar un mensaje incorrecto al cliente de alguien. Eso destruye la confianza para siempre. |
| **4** | Construir en publico desde el dia 1\. — LinkedIn del equipo documenta el proceso. No espereis al lanzamiento para contar que existis. La audiencia se construye mientras se desarrolla. |
| **5** | El feedback de los betas manda sobre cualquier opinion interna. — Si un beta tester no entiende algo, el problema es el producto. No el usuario. |
| **6** | El precio lo dictan los datos, no la intuición. — El valor de Vera se mide en tiempo real ahorrado al cliente. Reuniones confirmadas, horas recuperadas, conversaciones resueltas sin intervención. Sin datos no hay precio justo |

| La trampa mas comun: Seguir desarrollando features en vez de salir a hablar con usuarios. El codigo no valida el producto. Las personas si. |
| :---- |

# **9\. Ventaja de mercado**

| La ventana esta abierta. Y se cierra. Motion, Reclaim, Clockwise — todos en ingles. El mercado hispano esta practicamente sin atender. |
| :---- |

## **Diferenciacion vs competencia**

| Producto | Posicionamiento |
| :---- | :---- |
| Motion (EEUU) | En ingles, orientado al mercado anglosajón. Asistente ejecutivo complejo. |
| Reclaim AI | En ingles. Optimizacion automatica de calendario. Sin gestion de comunicacion. |
| Vera (Syntra) | En espanol. Secretaria pura con foco en agendar via Email \+ WhatsApp \+ Telegram. Mercado hispano. |

## **La estrategia de los empleados en cadena**

Este es el moat real de Syntra. Ningún competidor directo en español tiene este concepto articulado:

* Vera agenda la reunion.

* Vera Equipo es el primer paso hacia ese stack completo — varios empleados virtuales trabajando juntos.

* La cadena de empleados es la narrativa de producto que ningún competidor tiene"

API google, whatsapp, gmail, llamadas, telegram:

investigación y pedirla ya porq tarda de 2 a 6 semanas.

como funciona

## **El posicionamiento que vende**

| *"Vera agenda tus reuniones por ti, en cualquier canal."* Cualquier empresario lo entiende en 3 segundos. Sin explicar IA, ni agentes, ni automatizacion. |
| :---: |

Syntra AI  |  Plan Maestro Vera  |  Confidencial  |  2025