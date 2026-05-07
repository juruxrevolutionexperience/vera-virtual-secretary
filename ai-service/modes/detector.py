"""DEPRECATED — la detección de modo ahora se hace en una sola llamada con tool calling
en llm.client.call_llm_with_tools(). Este módulo se mantiene por compatibilidad pero
no se usa en el flujo principal. Eliminar cuando se confirme que nada externo lo invoca."""

from llm.client import call_llm         # para detectar el modo en mensajes ambiguos donde las palabras clave no son suficientes


def detect_mode(message, history):      # detecta si el cliente quiere información, agendar o está en transición entre los dos
    # El LLM siempre decide — las keywords no tienen contexto y generan falsos positivos
    # Coste estimado: <1€/mes por cliente incluso en casos de alto volumen
    history_text = "\n".join([f"{m['role']}: {m['content']}" for m in history[-3:]]) if history else ""

    # Si el mensaje tiene asunto en la primera línea (separado por línea en blanco), quitarlo
    clean_message = message
    lines = message.split('\n')
    for i, line in enumerate(lines):
        if line.strip() == '' and i > 0:
            clean_message = '\n'.join(lines[i+1:]).strip()
            break

    prompt = f"""Eres un clasificador de intenciones. Tu tarea es entender qué quiere el cliente, no buscar palabras clave.

Hay cuatro opciones:

AGENDA — el cliente quiere que alguien le reserve tiempo. No importa cómo lo diga: con cortesía, con rodeos, preguntando por disponibilidad, mencionando una fecha o no. Si el objetivo final es quedar, es agenda.
RESPUESTA — el cliente quiere información sobre el negocio: servicios, precios, horarios, equipo, ubicación. No quiere quedar, solo saber.
TRANSICION — el cliente quiere las dos cosas a la vez: información Y también quedar.
IRRELEVANTE — el mensaje no va dirigido al negocio ni requiere respuesta: notificaciones automáticas de sistemas (GitHub, Stripe, bancos, apps), confirmaciones automáticas de compra/envío, newsletters, alertas técnicas, emails entre cuentas del propio dueño, spam, links sin solicitud, archivos sin contexto.

Pregúntate primero: ¿es este mensaje de un cliente real dirigido al negocio? Si no, es irrelevante.
Si sí, pregúntate: ¿quiere reservar tiempo? Si sí (aunque sea parcialmente), es agenda o transicion.

Historial reciente:
{history_text}

Mensaje: {clean_message}

Responde solo con una palabra: respuesta, agenda, transicion o irrelevante."""

    mode = call_llm(prompt, system="Eres un clasificador de intenciones. Responde solo con una palabra.", temperature=0).strip().lower()
    if mode not in ("respuesta", "agenda", "transicion", "irrelevante"):
        mode = "respuesta"  # fallback seguro si el LLM devuelve algo inesperado

    # Fallback Python — si el LLM dijo "respuesta" pero el mensaje tiene intención clara de agenda, corregir
    # Solo aplicar si no es irrelevante
    if mode == "respuesta":
        agenda_keywords = ["cita", "quedar", "hueco", "agendar", "concertar", "reservar", "me viene bien", "me vienen bien"]
        if any(kw in clean_message.lower() for kw in agenda_keywords):
            mode = "agenda"

    return mode
