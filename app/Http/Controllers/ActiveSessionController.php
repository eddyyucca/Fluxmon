<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Router;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class ActiveSessionController extends Controller
{
    public function index(Router $router): View
    {
        return view('hotspot.active', compact('router'));
    }

    public function data(Router $router): JsonResponse
    {
        try {
            return response()->json(['ok' => true, 'sessions' => $this->gateway($router)->hotspotActive()]);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()]);
        }
    }

    public function kick(Request $request, Router $router): RedirectResponse|JsonResponse
    {
        $id = $request->validate(['id' => ['required', 'string', 'max:32']])['id'];

        try {
            $this->gateway($router)->kickActive($id);
            AuditLog::record('session.kicked', $request->input('user'), $router);

            return response()->json(['ok' => true]);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()]);
        }
    }
}
