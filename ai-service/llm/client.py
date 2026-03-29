from groq import Groq                   # librería para llamar a Groq
import google.generativeai as genai     # librería para llamar a Gemini
groq_client = Groq()                    # inicializa el cliente de Groq
genai.configure()                       # configura Gemini con GOOGLE_API_KEY del .env
def call_llm(prompt, system):           # llama a Groq, si falla prueba Gemini
    try:
        response = groq_client.chat.completions.create(
            model="llama3-8b-8192",             # modelo de Groq
            messages=[
                {"role": "system", "content": system},  # instrucciones de Vera
                {"role": "user", "content": prompt}     # mensaje del cliente
            ]
        )
        return response.choices[0].message.content      # devuelve la respuesta de Groq
    except:
        model = genai.GenerativeModel("gemini-pro")     # selecciona el modelo Gemini
        response = model.generate_content(system + prompt)  # genera la respuesta
        return response.text                            # devuelve la respuesta de Gemini