<?php

namespace Tests\Feature;

use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    private function fakeGroq(array $payload): void
    {
        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [['message' => ['content' => json_encode($payload)]]],
            ]),
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.groq.key' => 'test-key']);
    }

    public function test_can_create_ticket(): void
    {
        $this->postJson('/api/tickets', ['subject' => 'No puedo entrar', 'body' => 'Me da error al iniciar sesión'])
            ->assertCreated()->assertJsonPath('subject', 'No puedo entrar');
    }

    public function test_create_validates_input(): void
    {
        $this->postJson('/api/tickets', ['customer_email' => 'nope'])
            ->assertUnprocessable()->assertJsonValidationErrors(['subject', 'body', 'customer_email']);
    }

    public function test_analyze_stores_ai_result(): void
    {
        $this->fakeGroq([
            'summary' => 'Cobro duplicado en la factura de septiembre.',
            'category' => 'facturacion',
            'priority' => 'alta',
            'suggested_reply' => 'Lamentamos el error, lo revisamos hoy.',
        ]);
        $ticket = Ticket::factory()->create();

        $this->postJson("/api/tickets/{$ticket->id}/analyze")
            ->assertOk()
            ->assertJsonPath('category', 'facturacion')
            ->assertJsonPath('priority', 'alta');

        $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'category' => 'facturacion']);
        Http::assertSent(fn ($r) => $r->hasHeader('Authorization', 'Bearer test-key'));
    }

    public function test_invalid_ai_values_fall_back_to_safe_defaults(): void
    {
        $this->fakeGroq(['summary' => 'x', 'category' => 'magia', 'priority' => 'urgentisima', 'suggested_reply' => 'y']);
        $ticket = Ticket::factory()->create();

        $this->postJson("/api/tickets/{$ticket->id}/analyze")
            ->assertOk()->assertJsonPath('category', 'otro')->assertJsonPath('priority', 'media');
    }

    public function test_analyze_returns_502_when_groq_fails(): void
    {
        Http::fake(['api.groq.com/*' => Http::response(['error' => 'boom'], 500)]);
        $ticket = Ticket::factory()->create();

        $this->postJson("/api/tickets/{$ticket->id}/analyze")->assertStatus(502);
        $this->assertNull($ticket->fresh()->analyzed_at);
    }

    public function test_analyze_returns_502_on_non_json_answer(): void
    {
        Http::fake(['api.groq.com/*' => Http::response(['choices' => [['message' => ['content' => 'hola']]]])]);
        $ticket = Ticket::factory()->create();

        $this->postJson("/api/tickets/{$ticket->id}/analyze")->assertStatus(502);
    }

    public function test_analyze_without_key_returns_502_and_calls_nothing(): void
    {
        config(['services.groq.key' => null]);
        Http::fake();
        $ticket = Ticket::factory()->create();

        $this->postJson("/api/tickets/{$ticket->id}/analyze")->assertStatus(502);
        Http::assertNothingSent();
    }

    public function test_index_filters_by_category_and_priority(): void
    {
        Ticket::factory()->create(['category' => 'tecnico', 'priority' => 'alta']);
        Ticket::factory()->create(['category' => 'envio', 'priority' => 'baja']);

        $this->getJson('/api/tickets?category=tecnico')->assertJsonCount(1, 'data');
        $this->getJson('/api/tickets?priority=baja')->assertJsonCount(1, 'data');
    }

    public function test_unknown_ticket_is_404(): void
    {
        $this->getJson('/api/tickets/999')->assertNotFound();
    }
}
