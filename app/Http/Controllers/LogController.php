<?php

namespace App\Http\Controllers;

use App\Models\Router;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class LogController extends Controller
{
    public function index(Router $router): View
    {
        return view('logs.index', compact('router'));
    }

    /** Router log, newest first, optionally filtered by topic and text. */
    public function data(Request $request, Router $router): JsonResponse
    {
        $f = $request->validate([
            'topic' => ['nullable', 'string', 'max:40'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $all = collect($this->gateway($router)->logs(1000));

            $lines = $all
                ->when($f['topic'] ?? null, fn ($c, $t) => $c->filter(fn ($l) => in_array($t, explode(',', $l['topics']), true)))
                ->when($f['q'] ?? null, fn ($c, $q) => $c->filter(fn ($l) => stripos($l['message'], $q) !== false))
                ->reverse()->take(300)->values();

            $topics = $all->flatMap(fn ($l) => explode(',', $l['topics']))->filter()->unique()->sort()->values();

            return response()->json(['ok' => true, 'lines' => $lines, 'topics' => $topics]);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()]);
        }
    }
}
