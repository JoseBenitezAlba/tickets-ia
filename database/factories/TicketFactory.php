<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'subject' => fake()->sentence(5),
            'body' => fake()->paragraph(3),
            'customer_email' => fake()->safeEmail(),
        ];
    }
}
