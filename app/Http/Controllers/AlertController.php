<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function attend(Request $request, Alert $alerta): RedirectResponse
    {
        $this->authorize('attend', $alerta);

        // Idempotente: atender dos veces no deja dos registros en la bitácora.
        Audit::update($request, $alerta, ['atendida' => true], 'alerta_atendida');

        return back()->with('status', 'Alerta marcada como atendida.');
    }
}
