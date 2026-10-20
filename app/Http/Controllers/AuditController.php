<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\View\View;

class AuditController extends Controller
{
    public function index(): View
    {
        return view('audit.index', [
            'logs' => AuditLog::with(['user', 'router'])->latest('created_at')->latest('id')->paginate(50),
        ]);
    }
}
