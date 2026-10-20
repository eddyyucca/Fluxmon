<?php

namespace Tests\Feature;

use App\Models\ConfigBackup;
use App\Models\Router;
use App\Services\Config\BackupService;
use App\Services\Config\DiffService;
use Tests\FluxmonTestCase;

class BackupsTest extends FluxmonTestCase
{
    private function backup(string $reason = 'manual'): array
    {
        return app(BackupService::class)->create($this->router, $reason);
    }

    public function test_backup_is_stored_and_unchanged_config_is_not_duplicated(): void
    {
        $first = $this->backup();
        $second = $this->backup('scheduled');

        $this->assertTrue($first['changed']);
        $this->assertFalse($second['changed']);
        $this->assertSame($first['backup']->id, $second['backup']->id);
        $this->assertSame(1, ConfigBackup::count());
        $this->assertSame(strlen($this->exporter->config), $first['backup']->size);

        $this->exporter->config .= "/ip dns set servers=1.1.1.1\n";
        $this->assertTrue($this->backup()['changed']);
        $this->assertSame(2, ConfigBackup::count());
    }

    public function test_only_the_newest_backups_are_kept(): void
    {
        config(['fluxmon.backups.keep' => 3]);

        foreach (range(1, 5) as $i) {
            $this->exporter->config = "/system identity set name=v{$i}\n";
            $this->backup();
        }

        $this->assertSame(3, ConfigBackup::count());
        $this->assertStringContainsString('v5', ConfigBackup::latest('id')->first()->content);
        $this->assertSame(0, ConfigBackup::where('content', 'like', '%v1%')->count());
    }

    public function test_export_failure_surfaces_a_clear_error_and_stores_nothing(): void
    {
        $this->exporter->fail = true;

        $this->asAdmin()->post("/r/{$this->router->id}/backups")->assertSessionHas('error', 'SSH login failed');

        $this->assertSame(0, ConfigBackup::count());
    }

    public function test_manual_backup_download_and_diff_views(): void
    {
        $id = $this->router->id;
        $this->asAdmin()->post("/r/{$id}/backups")->assertRedirect();
        $this->asAdmin()->post("/r/{$id}/backups")->assertSessionHas('status'); // unchanged

        $this->exporter->config = "/ip address\nadd address=10.0.0.1/24 interface=ether1\n";
        $this->asAdmin()->post("/r/{$id}/backups");
        [$old, $new] = ConfigBackup::orderBy('id')->get()->all();

        $page = $this->asAdmin()->get("/r/{$id}/backups/{$new->id}")->assertOk();
        $page->assertSee('10.0.0.1/24')->assertSee('192.168.88.1/24');

        $this->asAdmin()->get("/r/{$id}/backups/{$old->id}")->assertOk()->assertSee('oldest backup');

        $download = $this->asAdmin()->get("/r/{$id}/backups/{$new->id}/download");
        $download->assertOk()->assertHeader('content-type', 'text/plain; charset=UTF-8');
        $this->assertStringContainsString('10.0.0.1/24', $download->streamedContent());

        $this->asAdmin()->get("/r/{$id}/backups")->assertOk()->assertSee('manual');
    }

    public function test_backup_of_another_router_cannot_be_opened_through_this_one(): void
    {
        $this->backup();
        $other = Router::create(['name' => 'Other', 'host' => '1.1.1.1', 'port' => 8728, 'username' => 'a', 'password' => 'b', 'currency' => 'Rp', 'traffic_interface' => 'ether1', 'timeout' => 5]);
        $backup = ConfigBackup::first();

        $this->asAdmin()->get("/r/{$other->id}/backups/{$backup->id}")->assertNotFound();
        $this->asAdmin()->get("/r/{$other->id}/backups/{$backup->id}/download")->assertNotFound();
        $this->asAdmin()->delete("/r/{$other->id}/backups/{$backup->id}")->assertNotFound();
        $this->assertSame(1, ConfigBackup::count());
    }

    public function test_scheduled_command_backs_up_every_router_and_reports_failures(): void
    {
        $this->artisan('fluxmon:backup')->assertSuccessful();
        $this->assertSame(1, ConfigBackup::count());

        $this->exporter->fail = true;
        $this->artisan('fluxmon:backup')->assertFailed();
    }

    public function test_fingerprint_can_be_cleared_by_admins_only(): void
    {
        $this->router->forceFill(['ssh_fingerprint' => str_repeat('a', 64)])->save();

        $this->asAdmin()->post("/r/{$this->router->id}/backups/reset-fingerprint")->assertSessionHas('status');

        $this->assertNull($this->router->fresh()->ssh_fingerprint);
    }

    public function test_diff_service_marks_added_and_removed_lines(): void
    {
        $diff = new DiffService();
        $old = "a\nb\nc\nd\ne\n";
        $new = "a\nB\nc\nd\ne\nf\n";

        $changes = collect($diff->lines($old, $new));
        $this->assertSame(['b'], $changes->where('type', '-')->pluck('text')->all());
        $this->assertSame(['B', 'f'], $changes->where('type', '+')->pluck('text')->all());
        $this->assertSame(['a', 'c', 'd', 'e'], $changes->where('type', ' ')->pluck('text')->all());
    }

    public function test_diff_service_shows_context_and_gaps_between_distant_changes(): void
    {
        $lines = range(1, 30);
        $old = implode("\n", $lines)."\n";
        $new = implode("\n", array_replace($lines, [2 => 'X', 26 => 'Y']))."\n";

        $out = (new DiffService())->changes($old, $new, 1);

        $this->assertContains('…', array_column($out, 'type'));
        $this->assertLessThan(15, count($out));
        $this->assertSame([], (new DiffService())->changes($old, $old));
    }

    public function test_diff_service_handles_empty_sides(): void
    {
        $diff = new DiffService();

        $this->assertSame(['x', 'y'], collect($diff->lines('', "x\ny\n"))->where('type', '+')->pluck('text')->all());
        $this->assertSame(['x'], collect($diff->lines("x\n", ''))->where('type', '-')->pluck('text')->all());
        $this->assertSame([], $diff->lines('', ''));
    }
}
