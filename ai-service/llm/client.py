from groq import Groq
from google import genai
import os
import json
import time
from dotenv import load_dotenv

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
