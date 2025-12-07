# 📋 PLAN DE MEJORAS - VERA

  ## 🔴 PRIORIDAD ALTA (Próxima sesión)

  ### 1. Revisar mensaje de sincronización (EN INVESTIGACIÓN)
  - **Bug detectado**: Muestra "sincronizando 3890 de 2566 emails" (números inconsistentes)
  - **Problema identificado**: Estimaba 60% de emails en inbox en lugar de contar reales
  - **Intento de solución 06/12/2025**: Falló - mostró "201 emails" (incorrecto)
  - **Estado actual**: Sistema revertido a versión funcional (con bug original)
  - **Próximo paso**: Investigar API correcta de Gmail para obtener conteo real
    - Ver: `SESION_06_DIC_2025.md` para detalles completos
  - **Archivos**: `app/Services/GmailSyncService.php` (método `getInitialEmailCount()`)

  ### 2. Evitar refresh visible en polling
  - **Problema**: Cuando la página sincroniza emails cada 2 min, se nota el refresh
  - **Solución propuesta**: Implementar actualización de emails sin recargar página usando AJAX/Fetch con JavaScript
  - **Objetivo**: Usuario no debe notar el refresco

  ---

  ## 🟡 MEJORAS PLANIFICADAS

  ### UI/UX de Emails
  - [ ] Mejorar diseño de la bandeja
  - [ ] Agregar búsqueda avanzada
  - [ ] Vista previa de emails
  - [ ] Atajos de teclado

  ### Funcionalidades de Email
  - [ ] Responder emails
  - [ ] Redactar nuevos emails
  - [ ] Etiquetas personalizadas
  - [ ] Filtros automáticos

  ### Sistema Vera (IA)
  - [ ] Respuestas automáticas sugeridas
  - [ ] Clasificación automática mejorada
  - [ ] Resúmenes de emails
  - [ ] Priorización inteligente

  ### Administración
  - [ ] Múltiples cuentas de email
  - [ ] Dashboard con estadísticas
  - [ ] Configuración de sincronización

  ---

  ## ✅ COMPLETADO

  ### 05 Diciembre 2025
  - [x] ✨ Formato de fechas estilo Gmail en español
    - Creado helper formatEmailDate()
    - Implementado formato: '5 Diciembre', '5 Diciembre 2024'
    - Emails recientes (<24h) mantienen 'hace 2h'
    - Meses en español

  ---