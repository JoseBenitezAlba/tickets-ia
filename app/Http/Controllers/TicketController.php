<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Services\TicketAnalyzer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class TicketController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tickets = Ticket::query()
            ->when($request->query('category'), fn ($q, $v) => $q->where('category', $v))
            ->when($request->query('priority'), fn ($q, $v) => $q->where('priority', $v))
            ->latest('id')
            ->paginate(15);

        return response()->json($tickets);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            'customer_email' => ['nullable', 'email', 'max:255'],
        ]);

        return response()->json(Ticket::create($data), 201);
    }

    public function show(Ticket $ticket): JsonResponse
    {
        return response()->json($ticket);
    }

    public function analyze(Ticket $ticket, TicketAnalyzer $analyzer): JsonResponse
    {
        try {
            $result = $analyzer->analyze($ticket);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => 'No se pudo analizar el ticket con la IA.'], 502);
        }

        $ticket->forceFill($result + ['analyzed_at' => now()])->save();

        return response()->json($ticket);
    }
}
