<?php

namespace App\Http\Controllers;

use App\Support\Docs;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\App;
use Illuminate\View\View;

class DocsController extends Controller
{
    public function show(?string $page = null): View|RedirectResponse
    {
        if ($page === null) {
            return redirect()->route('docs', 'getting-started');
        }

        abort_unless(Docs::exists($page), 404);

        return view('docs.show', [
            'slug' => $page,
            'pages' => Docs::pages(),
            'content' => Docs::view($page, App::getLocale()),
            'neighbours' => Docs::neighbours($page),
        ]);
    }
}
