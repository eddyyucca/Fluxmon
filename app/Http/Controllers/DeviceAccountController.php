<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Router;
use App\Models\RouterAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/** Management accounts of one device. Only superadmins and administrators reach this. */
class DeviceAccountController extends Controller
{
    public function index(Router $router): View
    {
        return view('devices.accounts', [
            'router' => $router,
            'accounts' => RouterAccount::where('router_id', $router->id)->orderBy('name')->get(),
            'roles' => RouterAccount::ROLES,
        ]);
    }

    public function store(Request $request, Router $router): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('router_accounts', 'username')->where('router_id', $router->id)],
            'password' => ['required', Password::min(8)],
            'role' => ['required', Rule::in(RouterAccount::ROLES)],
        ]);

        $account = RouterAccount::create($data + ['router_id' => $router->id, 'active' => true]);
        AuditLog::record('device_account.created', "{$account->username} ({$account->role})", $router);

        return back()->with('status', __('Device account :name created.', ['name' => $account->name]));
    }

    public function update(Request $request, Router $router, RouterAccount $account): RedirectResponse
    {
        abort_unless($account->router_id === $router->id, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'role' => ['required', Rule::in(RouterAccount::ROLES)],
            'active' => ['boolean'],
            'password' => ['nullable', Password::min(8)],
        ]);

        $account->fill([
            'name' => $data['name'],
            'role' => $data['role'],
            'active' => $request->boolean('active'),
        ]);
        if (! empty($data['password'])) {
            $account->password = $data['password']; // hashed by the cast
        }
        $account->save();
        AuditLog::record('device_account.updated', "{$account->username} ({$account->role})", $router);

        return back()->with('status', __('Device account updated.'));
    }

    public function destroy(Router $router, RouterAccount $account): RedirectResponse
    {
        abort_unless($account->router_id === $router->id, 404);
        AuditLog::record('device_account.deleted', $account->username, $router);
        $account->delete();

        return back()->with('status', __('Device account removed.'));
    }
}
