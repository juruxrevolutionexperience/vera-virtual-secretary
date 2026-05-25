from groq import Groq
from google import genai
import os
import json
import logging
import re
import time
from dotenv import load_dotenv

logger = logging.getLogger("vera.llm")

load_dotenv()  # carga ai-service/.env

groq_client = Groq(api_key=os.getenv("GROQ_API_KEY"))              # inicializa el cliente de Groq con la API key del .env
gemini_client = genai.Client(api_key=os.getenv("GOOGLE_API_KEY"))  # inicializa el cliente de Gemini con la nueva librería

# Circuit Breaker — evita esperar el timeout de Groq cuando está caído
_CIRCUIT_FILE = "/tmp/vera_circuit.json"
_FAILURE_THRESHOLD = 3    # fallos consecutivos para abrir el circuito
_RECOVERY_TIMEOUT = 300   # segundos hasta probar Groq de nuevo (5 min)


def _read_circuit() -> dict:
    try:
        with open(_CIRCUIT_FILE) as f:
            return json.load(f)
    except Exception:
        return {"failures": 0, "opened_at": None}


def _write_circuit(state: dict):
    try:
        with open(_CIRCUIT_FILE, "w") as f:
            json.dump(state, f)
    except Exception:
        pass


def _is_circuit_open() -> bool:
    state = _read_circuit()
    if state["failures"] < _FAILURE_THRESHOLD:
        return False
    if state["opened_at"] and (time.time() - state["opened_at"]) > _RECOVERY_TIMEOUT:
        _write_circuit({"failures": 0, "opened_at": None})  # resetea — intenta Groq de nuevo
        return False
    return True


def _record_failure():
    state = _read_circuit()
    state["failures"] += 1
    if state["failures"] >= _FAILURE_THRESHOLD and not state["opened_at"]:
        state["opened_at"] = time.time()
    _write_circuit(state)


def _record_success():
    _write_circuit({"failures": 0, "opened_at": None})


_TOOLS = [{
    "type": "function",
    "function": {
        "name": "enviar_respuesta",
        "description": "Envía la respuesta al cliente",
        "parameters": {
            "type": "object",
            "properties": {
                "modo": {
                    "type": "string",
                    "enum": ["respuesta", "agenda", "transicion", "escalation", "irrelevante"],
                    "description": "respuesta=pregunta info, agenda=quiere cita, transicion=info+cita, escalation=queja/urgencia, irrelevante=notificación automática sin acción"
                },
                "texto": {
                    "type": "string",
                    "description": "Texto exacto de la respuesta para el cliente. Vacío solo si es irrelevante."
                }
            },
            "required": ["modo", "texto"]
        }
    }
}]


_VALID_MODES = {"respuesta", "agenda", "transicion", "escalation", "irrelevante"}


def _parse_groq_tool_call(response):
    """Extrae (modo, texto) de la respuesta de Groq con tool calling. Lanza excepción si la estructura no es válida."""
    choices = getattr(response, "choices", None)
    if not choices:
        raise ValueError("Groq devolvió respuesta sin 'choices'")

    message = choices[0].message
    tool_calls = getattr(message, "tool_calls", None)
    if not tool_calls:
        # LLaMA a veces devuelve content normal en vez de llamar a la tool — lo tratamos como fallo recuperable
        raw_content = getattr(message, "content", "") or ""
        raise ValueError(f"Groq no usó la tool. Content recibido: {raw_content[:200]}")

    raw_args = tool_calls[0].function.arguments
    try:
        args = json.loads(raw_args)
    except json.JSONDecodeError as e:
        raise ValueError(f"Argumentos de tool no son JSON válido: {raw_args[:200]}") from e

    modo = args.get("modo", "respuesta")
    if modo not in _VALID_MODES:
        logger.warning("Groq devolvió modo inválido '%s', cayendo a 'respuesta'", modo)
        modo = "respuesta"

    return modo, args.get("texto", "") or ""


def _parse_gemini_json(text):
    """Extrae el primer objeto JSON {modo, texto} del texto. Devuelve None si no encuentra uno válido."""
    json_match = re.search(r'\{[^{}]*"modo"[^{}]*"texto"[^{}]*\}', text, re.DOTALL)
    if not json_match:
        json_match = re.search(r'\{.*\}', text, re.DOTALL)
    if not json_match:
        return None
    try:
        parsed = json.loads(json_match.group())
        modo = parsed.get("modo", "respuesta")
        if modo not in _VALID_MODES:
            modo = "respuesta"
        return modo, parsed.get("texto", "") or ""
    except (json.JSONDecodeError, AttributeError):
        return None


def _extract_tokens(response) -> int:
    """Total de tokens de la respuesta (Groq o Gemini). Devuelve 0 si no se puede — nunca lanza."""
    try:
        usage = getattr(response, "usage", None)            # Groq (OpenAI-compatible)
        if usage is not None:
            total = getattr(usage, "total_tokens", None)
            if total is not None:
                return int(total)
        meta = getattr(response, "usage_metadata", None)    # Gemini (google-genai)
        if meta is not None:
            total = getattr(meta, "total_token_count", None)
            if total is not None:
                return int(total)
    except Exception:
        pass
    return 0


def call_llm_with_tools(prompt, system, temperature=0.7):
    """Una sola llamada LLM que devuelve (modo, texto, tokens). Elimina la llamada extra de detección de modo."""
    if not _is_circuit_open():
        try:
            response = groq_client.chat.completions.create(
                model="llama-3.3-70b-versatile",
                temperature=temperature,
                tools=_TOOLS,
                tool_choice={"type": "function", "function": {"name": "enviar_respuesta"}},
                messages=[
                    {"role": "system", "content": system},
                    {"role": "user", "content": prompt}
                ]
            )
            modo, texto = _parse_groq_tool_call(response)
            _record_success()
            return modo, texto, _extract_tokens(response)
        except Exception as e:
            logger.warning("Groq tool calling falló, cayendo a Gemini: %s", e)
            _record_failure()

    # Gemini fallback — sin tool calling nativo, le pedimos JSON en el prompt
    fb_prompt = (
        system + "\n\n" + prompt +
        '\n\nResponde EXCLUSIVAMENTE con un JSON válido en este formato exacto, sin texto antes ni después:\n'
        '{"modo": "respuesta|agenda|transicion|escalation|irrelevante", "texto": "tu respuesta aquí"}'
    )
    try:
        response = gemini_client.models.generate_content(model="gemini-2.5-flash-lite", contents=fb_prompt)
        tokens = _extract_tokens(response)
        parsed = _parse_gemini_json(response.text or "")
        if parsed:
            return parsed[0], parsed[1], tokens
        logger.warning("Gemini no devolvió JSON parseable. Texto crudo: %s", (response.text or "")[:200])
        return "respuesta", response.text or "", tokens
    except Exception as e:
        logger.error("Gemini fallback también falló: %s", e)
        return "respuesta", "Déjame consultarlo y te respondo en breve.", 0


def call_llm(prompt, system, temperature=0.7, model="llama-3.3-70b-versatile"):   # llama a Groq con fallback a Gemini si falla — nunca deja a Vera sin respuesta
    if not _is_circuit_open():
        try:
            response = groq_client.chat.completions.create(
                model=model,        # modelo principal según el Plan Maestro
                temperature=temperature,
                messages=[
                    {"role": "system", "content": system},  # instrucciones de comportamiento de Vera
                    {"role": "user", "content": prompt}     # mensaje del cliente con el contexto completo
                ]
            )
            _record_success()
            return response.choices[0].message.content      # devuelve la respuesta de Groq
        except Exception:
            _record_failure()                               # suma fallo — abre circuito al llegar a 3

    # Gemini como fallback — circuito abierto o Groq falló
    response = gemini_client.models.generate_content(
        model="gemini-2.5-flash-lite",
        contents=system + "\n" + prompt             # combina system y prompt para Gemini
    )
    return response.text                            # devuelve la respuesta de Gemini
