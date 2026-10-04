<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    public const CATEGORIES = ['facturacion', 'tecnico', 'cuenta', 'envio', 'otro'];
    public const PRIORITIES = ['baja', 'media', 'alta'];

    protected $fillable = ['subject', 'body', 'customer_email'];

    protected function casts(): array
    {
        return ['analyzed_at' => 'datetime'];
    }
}
