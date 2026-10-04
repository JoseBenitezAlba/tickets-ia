<?php

use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::get('/tickets', [TicketController::class, 'index']);
Route::post('/tickets', [TicketController::class, 'store']);
Route::get('/tickets/{ticket}', [TicketController::class, 'show']);
Route::post('/tickets/{ticket}/analyze', [TicketController::class, 'analyze'])->middleware('throttle:10,1');
