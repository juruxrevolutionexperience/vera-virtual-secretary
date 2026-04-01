from groq import Groq                   # librería para llamar a Groq
import google.generativeai as genai     # librería para llamar a Gemini
import os                               # para leer las API keys del .env

groq_client = Groq(api_key=os.getenv("GROQ_API_KEY"))          # inicializa el cliente de Groq con la API key del .env
genai.configure(api_key=os.getenv("GOOGLE_API_KEY"))           # configura Gemini con la API key del .env


def call_llm(prompt, system):           # llama a Groq con fallback a Gemini si falla — nunca deja a Vera sin respuesta
    try:
        response = groq_client.chat.completions.create(
            model="llama-3.3-70b-versatile",        # modelo principal según el Plan Maestro
            messages=[
                {"role": "system", "content": system},  # instrucciones de comportamiento de Vera
                {"role": "user", "content": prompt}     # mensaje del cliente con el contexto completo
            ]
        )
        return response.choices[0].message.content      # devuelve la respuesta de Groq
    except Exception as e:                              # captura errores reales de red o API, no errores de código
        model = genai.GenerativeModel("gemini-1.5-flash")    # fallback a Gemini si Groq falla o está saturado
        response = model.generate_content(system + "\n" + prompt)  # combina system y prompt para Gemini
        return response.text                            # devuelve la respuesta de Gemini
