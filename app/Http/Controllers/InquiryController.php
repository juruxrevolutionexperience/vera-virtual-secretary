<?php

namespace App\Http\Controllers;

use App\Models\VeraActivityLog;
use App\Models\VeraConfig;
use App\Models\VeraInquiry;
use App\Services\ClientChannelService;
use App\Services\VeraMemoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Consultas sin respuesta (Fase C de Bandeja 2.0). El dueño las gestiona desde
 * la Bandeja: responde vía Vera (telegram/whatsapp) o guarda la respuesta (email,
 * donde contesta desde Gmail), y opcionalmente la convierte en FAQ del cerebro
 * de Vera para que la próxima vez responda ella sola.
 */
class InquiryController extends Controller
{
    public function respond(Request $request, VeraInquiry $inquiry): RedirectResponse
    {
        abort_if($inquiry->user_id !== auth()->id(), 403);
        abort_if($inquiry->status !== 'pending', 422);

        $data = $request->validate(
            [
                'answer'   => ['required', 'string', 'max:2000'],
                'save_faq' => ['nullable', 'boolean'],
            ],
            [
                'answer.required' => 'Escribe la respuesta antes de enviar.',
                'answer.max'      => 'La respuesta no puede pasar de 2000 caracteres.',
            ]
        );

        $config = VeraConfig::where('user_id', $inquiry->user_id)->first();
        $answer = trim($data['answer']);

        // Telegram/WhatsApp: Vera entrega la respuesta al cliente presentándola.
        // Email: el dueño responde desde Gmail; aquí solo se registra (y aprende).
        if (in_array($inquiry->channel, ['telegram', 'whatsapp'])) {
            $owner = $config?->owner_name ?: 'el equipo';
            $text  = "Ya me ha pasado {$owner} la respuesta:\n\n" . $answer;

            $sent = (new ClientChannelService())->send(
                $config,
                $inquiry->channel,
                $inquiry->contact_identifier,
                $inquiry->contact,
                $text
            );

            if ($sent === null) {
                return back()->with('error', 'Aún no tengo guardado el canal de este cliente. En cuanto vuelva a escribir, podrás responderle desde aquí.');
            }
            if (!$sent) {
                return back()->with('error', 'No se pudo enviar la respuesta. Inténtalo de nuevo en un momento.');
            }
        }

        $inquiry->update([
            'answer'      => $answer,
            'status'      => 'answered',
            'answered_at' => now(),
        ]);

        // Aprendizaje: pregunta + respuesta pasan al cerebro de Vera (FAQ) y se
        // sincronizan a ChromaDB al momento — la próxima vez contesta ella sola.
        $learned = false;
        if ($request->boolean('save_faq')) {
            try {
                (new VeraMemoryService())->addFAQFromInquiry($inquiry->user_id, $inquiry->question, $answer);
                $learned = true;
            } catch (\Throwable $e) {
                Log::warning('Consulta: fallo al guardar la FAQ', ['inquiry_id' => $inquiry->id, 'error' => $e->getMessage()]);
            }
        }

        VeraActivityLog::record([
            'user_id'              => $inquiry->user_id,
            'activity_type'        => 'response_sent',
            'title'                => 'Consulta respondida a ' . ($inquiry->contact_name ?? $inquiry->contact_identifier),
            'description'          => mb_substr($answer, 0, 200),
            'metadata'             => ['channel' => $inquiry->channel, 'contact' => $inquiry->contact_identifier, 'via' => 'bandeja_inquiry', 'saved_as_faq' => $learned],
            'counts_towards_limit' => false,
            'status'               => 'completed',
        ]);

        $msg = in_array($inquiry->channel, ['telegram', 'whatsapp'])
            ? 'Respuesta enviada al cliente vía Vera.'
            : 'Respuesta registrada (recuerda contestar al cliente desde Gmail).';
        if ($learned) {
            $msg .= ' Guardada en la información de Vera: la próxima vez responde ella sola.';
        }

        return back()->with('success', $msg);
    }

    public function dismiss(VeraInquiry $inquiry): RedirectResponse
    {
        abort_if($inquiry->user_id !== auth()->id(), 403);

        $inquiry->update(['status' => 'dismissed']);

        return back()->with('success', 'Consulta descartada.');
    }
}
