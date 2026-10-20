<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ProfileMeta;
use App\Models\Router;
use App\Models\Voucher;
use App\Models\VoucherBatch;
use App\Services\VoucherGenerator;
use App\Support\RouterOs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class VoucherController extends Controller
{
    public function index(Router $router): View
    {
        $error = null;
        $profiles = [];

        try {
            $profiles = array_column($this->gateway($router)->profiles(), 'name');
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }

        $batches = VoucherBatch::where('router_id', $router->id)
            ->withCount([
                'vouchers as unused_count' => fn (Builder $q) => $q->where('status', Voucher::UNUSED),
                'vouchers as used_count' => fn (Builder $q) => $q->where('status', Voucher::USED),
                'vouchers as expired_count' => fn (Builder $q) => $q->where('status', Voucher::EXPIRED),
            ])
            ->withSum(['vouchers as revenue' => fn (Builder $q) => $q->whereNotNull('first_used_at')], 'price')
            ->latest()->paginate(15);

        return view('vouchers.index', [
            'router' => $router,
            'profiles' => $profiles,
            'batches' => $batches,
            'prices' => ProfileMeta::where('router_id', $router->id)->pluck('price', 'name'),
            'charsets' => array_keys(VoucherGenerator::CHARSETS),
            'error' => $error,
        ]);
    }

    public function generate(Request $request, Router $router, VoucherGenerator $generator): RedirectResponse
    {
        $data = $request->validate([
            'profile' => ['required', 'string', 'max:100'],
            'quantity' => ['required', 'integer', 'between:1,500'],
            'length' => ['required', 'integer', 'between:3,16'],
            'prefix' => ['nullable', 'string', 'max:8', 'regex:/^[A-Za-z0-9_-]*$/'],
            'charset' => ['required', Rule::in(array_keys(VoucherGenerator::CHARSETS))],
            'mode' => ['required', Rule::in(['same', 'pair'])],
            'time_limit' => ['nullable', 'integer', 'min:1'],
            'time_limit_unit' => ['nullable', Rule::in(['m', 'h', 'd'])],
            'data_limit_mb' => ['nullable', 'integer', 'min:1'],
            'price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $data['time_limit_minutes'] = RouterOs::toMinutes($data['time_limit'] ?? null, $data['time_limit_unit'] ?? null);

        set_time_limit(300);

        try {
            $result = $generator->generate($router, $data, $request->user()->id);
        } catch (Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        if (! $result['batch']) {
            return back()->withInput()->with('error', $result['error'] ?? __('No vouchers were created.'));
        }

        AuditLog::record('voucher.generated', "{$result['created']} × {$data['profile']}", $router);

        $redirect = redirect()->route('r.vouchers.show', [$router, $result['batch']])
            ->with('status', __(':count vouchers created.', ['count' => $result['created']]));

        return $result['error']
            ? $redirect->with('error', __('Stopped early after :count: :error', ['count' => $result['created'], 'error' => $result['error']]))
            : $redirect;
    }

    public function show(Router $router, VoucherBatch $batch): View
    {
        abort_unless($batch->router_id === $router->id, 404);

        return view('vouchers.show', [
            'router' => $router,
            'batch' => $batch,
            'vouchers' => $batch->vouchers()->orderBy('id')->paginate(100),
            'counts' => $batch->vouchers()->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status'),
        ]);
    }

    public function print(Request $request, Router $router, VoucherBatch $batch): View
    {
        abort_unless($batch->router_id === $router->id, 404);

        $opts = $request->validate([
            'size' => ['nullable', Rule::in(['normal', 'small'])],
            'qr' => ['nullable', 'boolean'],
            'unused_only' => ['nullable', 'boolean'],
        ]);

        $vouchers = $batch->vouchers()
            ->when($request->boolean('unused_only', true), fn (Builder $q) => $q->where('status', Voucher::UNUSED))
            ->orderBy('id')->get();

        return view('vouchers.print', [
            'router' => $router,
            'batch' => $batch,
            'vouchers' => $vouchers,
            'size' => $opts['size'] ?? 'normal',
            'qr' => $request->boolean('qr', true),
        ]);
    }

    public function csv(Router $router, VoucherBatch $batch): StreamedResponse
    {
        abort_unless($batch->router_id === $router->id, 404);

        return response()->streamDownload(function () use ($batch) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['username', 'password', 'profile', 'price', 'status']);
            foreach ($batch->vouchers()->orderBy('id')->cursor() as $v) {
                fputcsv($out, [$v->username, $v->password, $v->profile, $v->price, $v->status]);
            }
            fclose($out);
        }, "vouchers-batch-{$batch->id}.csv", ['Content-Type' => 'text/csv']);
    }

    /** Remove a batch's unused vouchers from the router; sold history stays for reports. */
    public function destroy(Router $router, VoucherBatch $batch): RedirectResponse
    {
        abort_unless($batch->router_id === $router->id, 404);

        return $this->attempt(function () use ($router, $batch) {
            $unused = $batch->vouchers()->where('status', Voucher::UNUSED)->pluck('username')->flip();
            $gw = $this->gateway($router);

            foreach ($gw->hotspotUsers() as $user) {
                if ($unused->has($user['name'])) {
                    $gw->removeHotspotUser($user['id']);
                }
            }

            $batch->vouchers()->where('status', Voucher::UNUSED)->delete();
            if (! $batch->vouchers()->exists()) {
                $batch->delete();
            }
            AuditLog::record('voucher.batch_deleted', "batch #{$batch->id}", $router);
        }, __('Batch removed.'));
    }
}
