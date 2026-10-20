<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ConfigBackup;
use App\Models\Router;
use App\Services\Config\BackupService;
use App\Services\Config\DiffService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class BackupController extends Controller
{
    public function index(Router $router): View
    {
        return view('backups.index', [
            'router' => $router,
            'backups' => ConfigBackup::where('router_id', $router->id)->with('user:id,name')
                ->select('id', 'router_id', 'user_id', 'reason', 'hash', 'size', 'created_at')
                ->latest('id')->paginate(25),
        ]);
    }

    public function store(Request $request, Router $router, BackupService $backups): RedirectResponse
    {
        try {
            $result = $backups->create($router, 'manual', $request->user()->id);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        AuditLog::record('backup.created', $result['changed'] ? "#{$result['backup']->id}" : 'unchanged', $router);

        return $result['changed']
            ? redirect()->route('r.backups.show', [$router, $result['backup']])->with('status', __('Backup saved.'))
            : back()->with('status', __('The configuration has not changed since the last backup.'));
    }

    /** One backup, shown as a diff against the previous version (or any other with ?against=). */
    public function show(Request $request, Router $router, ConfigBackup $backup, DiffService $diff): View
    {
        abort_unless($backup->router_id === $router->id, 404);

        $against = $request->query('against')
            ? ConfigBackup::where('router_id', $router->id)->findOrFail($request->query('against'))
            : ConfigBackup::where('router_id', $router->id)->where('id', '<', $backup->id)->latest('id')->first();

        return view('backups.show', [
            'router' => $router,
            'backup' => $backup,
            'against' => $against,
            'changes' => $against ? $diff->changes($against->content, $backup->content) : [],
            'others' => ConfigBackup::where('router_id', $router->id)->where('id', '!=', $backup->id)->latest('id')->limit(50)->get(['id', 'created_at']),
        ]);
    }

    public function download(Router $router, ConfigBackup $backup): StreamedResponse
    {
        abort_unless($backup->router_id === $router->id, 404);

        $name = preg_replace('/[^A-Za-z0-9_-]+/', '-', $router->name).'-'.$backup->created_at->format('Ymd-His').'.rsc';

        return response()->streamDownload(fn () => print($backup->content), $name, ['Content-Type' => 'text/plain']);
    }

    public function destroy(Router $router, ConfigBackup $backup): RedirectResponse
    {
        abort_unless($backup->router_id === $router->id, 404);
        $backup->delete();
        AuditLog::record('backup.deleted', "#{$backup->id}", $router);

        return redirect()->route('r.backups', $router)->with('status', __('Backup deleted.'));
    }

    /** After a router is reinstalled its SSH host key legitimately changes. */
    public function resetFingerprint(Router $router): RedirectResponse
    {
        $router->forceFill(['ssh_fingerprint' => null])->save();
        AuditLog::record('router.ssh_key_reset', null, $router);

        return back()->with('status', __('Pinned SSH host key cleared. The next backup will pin the router\'s current key.'));
    }
}
