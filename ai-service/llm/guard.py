"""Guard anti prompt-injection — capa ligera previa al LLM, sin coste extra.

Filosofía: NUNCA bloquear el mensaje (un falso positivo dejaría a un cliente
real sin respuesta). En su lugar:
  1. sanitize_untrusted() neutraliza los delimitadores <<< >>> / [[[ ]]] para
     que ningún texto no confiable pueda romper el marcado del prompt.
  2. detect_injection() detecta patrones típicos de manipulación; si los hay,
     el prompt_builder añade un refuerzo de la regla de SEGURIDAD y loguea el
     intento SIN contenido del mensaje (RGPD): solo user_id + etiquetas.
"""
import re

# Secuencias que romperían los delimitadores del prompt. Se sustituyen por
# equivalentes visuales inofensivos para no perder legibilidad del mensaje.
_SUSTITUCIONES = [
    (re.compile(r"<{3,}"), "«"),
    (re.compile(r">{3,}"), "»"),
    (re.compile(r"\[{3,}"), "["),
    (re.compile(r"\]{3,}"), "]"),
]

# Patrones de manipulación conocidos (ES/EN). Etiqueta → regex.
# Diseñados para minimizar falsos positivos en mensajes reales de clientes
# de un negocio: exigen verbo de manipulación + objeto (instrucciones/rol/prompt).
_PATRONES = [
    ("ignorar_instrucciones", re.compile(
        r"\b(ignora|ignorar|olvida|olvidar|omite|omitir|descarta|descartar|"
        r"ignore|forget|disregard|bypass|override)\w*\b"
        r"[^.\n]{0,40}?"
        r"\b(instrucci\w+|instruction\w*|reglas?|rules?|normas?|prompts?|directrices)\b",
        re.IGNORECASE)),
    ("cambio_rol", re.compile(
        r"\b(a partir de ahora|desde (este momento|ahora))\s+(eres|ser[áa]s|te comportar[áa]s|act[úu]a)|"
        r"\beres ahora\b|\bahora eres\b|"
        r"\bact[úu]a como si fueras\b|\bhazte pasar por\b|\bfinge (ser|que eres)\b|"
        r"\byou are now\b|\bact as if you (are|were)\b|\bpretend (to be|you are)\b|"
        r"\b(nuevo rol|cambia de rol|new persona|roleplay as)\b",
        re.IGNORECASE)),
    ("extraccion_prompt", re.compile(
        r"\b(revela|rev[eé]lame|muestra|mu[eé]strame|ense[ñn]a|ense[ñn]ame|repite|rep[íi]teme|"
        r"imprime|dime|escribe|copia|reveal|show|print|repeat|output|display)\w*\b"
        r"[^.\n]{0,40}?"
        r"(system\s*prompt|prompt del sistema|instrucciones (del sistema|internas|anteriores|iniciales|de arriba)|"
        r"configuraci[óo]n interna|reglas internas|initial instructions|system message)",
        re.IGNORECASE)),
    ("modo_jailbreak", re.compile(
        r"\bjailbreak\b|\bmodo (desarrollador|dios|libre|sin restricciones)\b|"
        r"\bdeveloper mode\b|\bdan mode\b|\bdo anything now\b|"
        r"\b(responde|habla|contesta|act[úu]a|comp[óo]rtate|eres|ser[áa]s)\s+sin (filtros|restricciones|censura)\b|"
        r"\bsin censura\b|\bwithout (limits|restrictions|filters)\b",
        re.IGNORECASE)),
    ("ruptura_delimitadores", re.compile(r"<{3,}|>{3,}|\[{3,}|\]{3,}")),
]


def sanitize_untrusted(text):
    """Neutraliza secuencias que romperían los delimitadores del prompt.

    Aplicar a TODO texto no confiable antes de incrustarlo en el prompt:
    mensaje del cliente, historial y perfil auto-generado del cliente.
    """
    if not text:
        return text
    for patron, reemplazo in _SUSTITUCIONES:
        text = patron.sub(reemplazo, text)
    return text


def detect_injection(text):
    """Devuelve las etiquetas de los patrones de manipulación encontrados.

    Llamar ANTES de sanitize_untrusted() — la detección de ruptura de
    delimitadores necesita ver el texto original. Lista vacía = mensaje limpio.
    """
    if not text:
        return []
    return [etiqueta for etiqueta, patron in _PATRONES if patron.search(text)]
