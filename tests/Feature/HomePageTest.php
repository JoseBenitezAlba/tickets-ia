<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_shows_tickets_screen(): void
    {
        $this->get('/')->assertOk()->assertSee('Tickets IA')->assertSee('Analizar con IA');
    }
}
