<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TicketAnalyzer
{
    /**
     * Pide a Groq un resumen, categoría, prioridad y borrador de respuesta.
     *
     * @return array{summary: string, category: string, priority: string, suggested_reply: string}
     */
    public function analyze(Ticket $ticket): array
    {
        $key = config('services.groq.key');

        if (! $key) {
            throw new RuntimeException('Falta GROQ_API_KEY en el entorno.');
        }

        $response = Http::withToken($key)
            ->timeout(30)
            ->retry(2, 500, throw: false)
            ->post(config('services.groq.url'), [
                'model' => config('services.groq.model'),
                'temperature' => 0.2,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => $this->systemPrompt()],
                    ['role' => 'user', 'content' => "Asunto: {$ticket->subject}\n\n{$ticket->body}"],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Groq respondió con error '.$response->status().'.');
        }

        $content = $response->json('choices.0.message.content');

        return $this->parse((string) $content);
    }

    public function parse(string $content): array
    {
        $data = json_decode($content, true);

        if (! is_array($data)) {
            throw new RuntimeException('La respuesta de la IA no es JSON válido.');
        }

        $category = strtolower((string) ($data['category'] ?? 'otro'));
        $priority = strtolower((string) ($data['priority'] ?? 'media'));

        return [
            'summary' => trim((string) ($data['summary'] ?? '')),
            'category' => in_array($category, Ticket::CATEGORIES, true) ? $category : 'otro',
            'priority' => in_array($priority, Ticket::PRIORITIES, true) ? $priority : 'media',
            'suggested_reply' => trim((string) ($data['suggested_reply'] ?? '')),
        ];
    }

    private function systemPrompt(): string
    {
        $cats = implode(', ', Ticket::CATEGORIES);
        $pris = implode(', ', Ticket::PRIORITIES);

        return <<<PROMPT
Eres un asistente de soporte. Analiza el ticket del cliente y responde SOLO con un JSON con estas claves:
- "summary": resumen en una o dos frases, en español.
- "category": una de [{$cats}].
- "priority": una de [{$pris}]. "alta" si hay pérdida de dinero, servicio caído o urgencia clara.
- "suggested_reply": borrador de respuesta breve, educado, en español.
No inventes datos que no estén en el ticket.
PROMPT;
    }
}
