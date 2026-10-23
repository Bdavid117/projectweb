<?php

namespace App\Http\Controllers;

use App\Support\Notifications;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /** Pendientes del usuario para el menú de la campana; se pide bajo demanda desde el topbar. */
    public function __invoke(Request $request): JsonResponse
    {
        $items = Notifications::for($request->user());

        return response()->json([
            'count' => count($items),
            'html' => view('components.notification-list', ['items' => $items])->render(),
        ]);
    }
}
