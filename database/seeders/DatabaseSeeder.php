<?php

namespace Database\Seeders;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        Ticket::create([
            'subject' => 'Me cobraron dos veces la suscripción',
            'body' => 'Hola, este mes me han cargado 29,99 € dos veces en la tarjeta. Necesito que me devolváis uno de los cargos cuanto antes.',
            'customer_email' => 'laura@example.com',
        ]);
        Ticket::create([
            'subject' => 'La app se cierra al abrir mi perfil',
            'body' => 'Desde la última actualización, cada vez que entro en Perfil la aplicación se cierra sola. Uso Android 14.',
            'customer_email' => 'carlos@example.com',
        ]);
    }
}
