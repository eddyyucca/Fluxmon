<?php

namespace Tests\Feature;

use App\Models\AlertEvent;
use App\Models\Setting;
use App\Models\User;
use App\Services\Alerting\AlertService;
use App\Support\Fmt;
use Illuminate\Support\Facades\Http;
use Tests\FluxmonTestCase;

class LanguageTest extends FluxmonTestCase
{
    public function test_guest_can_switch_language_on_the_sign_in_page(): void
    {
        $this->get('/login')->assertSee('Sign in')->assertSee('aria-label="Bahasa Indonesia"', false);

        $this->post('/locale', ['locale' => 'id'])->assertRedirect()->assertSessionHas('locale', 'id')->assertCookie('fluxmon_locale');

        $this->get('/login')->assertSee('Masuk')->assertDontSee('Sign in');
    }

    public function test_choice_is_remembered_by_cookie_when_the_session_is_gone(): void
    {
        $this->withCookie('fluxmon_locale', 'id')->get('/login')->assertSee('Masuk');
    }

    public function test_signed_in_choice_is_saved_to_the_account_and_follows_the_user(): void
    {
        $this->asAdmin()->post('/locale', ['locale' => 'id'])->assertRedirect();
        $this->assertSame('id', $this->admin->fresh()->locale);

        // A brand new browser with no cookie or session still gets Indonesian after signing in.
        $this->flushSession();
        $this->actingAs($this->admin->fresh())->get('/dashboard')->assertSee('Dasbor');

        // Another account is not affected.
        $other = User::factory()->create();
        $this->actingAs($other)->get('/dashboard')->assertSee('Dashboard')->assertDontSee('Dasbor');
    }

    public function test_unknown_language_is_rejected(): void
    {
        $this->post('/locale', ['locale' => 'xx'])->assertSessionHasErrors('locale');
        $this->post('/locale', [])->assertSessionHasErrors('locale');
    }

    public function test_order_of_precedence(): void
    {
        // Nothing chosen: English.
        $this->asAdmin()->get('/dashboard')->assertSee('Dashboard');

        // Admin default applies to people who chose nothing.
        Setting::write('locale', 'id');
        $this->asAdmin()->get('/dashboard')->assertSee('Dasbor');

        // The browser's choice beats the default...
        $this->asAdmin()->withSession(['locale' => 'en'])->get('/dashboard')->assertSee('Dashboard')->assertDontSee('Dasbor');

        // ...and the account's own choice beats the browser's.
        $this->admin->forceFill(['locale' => 'id'])->save();
        $this->actingAs($this->admin->fresh())->withSession(['locale' => 'en'])->get('/dashboard')->assertSee('Dasbor');
    }

    public function test_html_lang_attribute_follows_the_language(): void
    {
        $this->asAdmin()->withSession(['locale' => 'id'])->get('/dashboard')->assertSee('<html lang="id">', false);
        $this->asAdmin()->withSession(['locale' => 'en'])->get('/dashboard')->assertSee('<html lang="en">', false);
    }

    public function test_switcher_is_on_every_screen_and_marks_the_current_language(): void
    {
        $page = $this->asAdmin()->withSession(['locale' => 'id'])->get('/dashboard');

        $page->assertSee('name="locale" value="en"', false)->assertSee('name="locale" value="id"', false)->assertSee('aria-current="true"', false);
    }

    public function test_admin_setting_is_now_the_default_language(): void
    {
        $this->asAdmin()->put('/settings/locale', ['locale' => 'id'])->assertSessionHas('status');
        $this->assertSame('id', Setting::read('locale'));

        $this->asAdmin()->get('/settings')->assertSee('Bahasa bawaan');
    }

    public function test_validation_messages_and_field_names_are_translated(): void
    {
        $this->asAdmin()->withSession(['locale' => 'id'])->post('/routers', ['name' => ''])
            ->assertSessionHasErrors(['name' => 'Nama wajib diisi.', 'host' => 'Host wajib diisi.', 'port' => 'Port API wajib diisi.']);

        $this->asAdmin()->withSession(['locale' => 'en'])->post('/routers', ['name' => ''])
            ->assertSessionHasErrors(['name' => 'The name field is required.']);
    }

    public function test_sign_in_failure_is_translated(): void
    {
        $this->withSession(['locale' => 'id'])->post('/login', ['email' => $this->admin->email, 'password' => 'wrong'])
            ->assertSessionHasErrors(['email' => 'Email atau password tidak cocok dengan data kami.']);
    }

    public function test_dates_and_money_follow_the_language(): void
    {
        $this->assertSame('Rp 1,234,567', Fmt::money(1234567));

        app()->setLocale('id');
        $this->assertSame('Rp 1.234.567', Fmt::money(1234567));
        app()->setLocale('en');

        $url = '/reports?range=custom&from=2026-10-01&to=2026-10-02';
        $this->asAdmin()->withSession(['locale' => 'en'])->get($url)->assertSee('Oct');
        $this->asAdmin()->withSession(['locale' => 'id'])->get($url)->assertSee('Okt')->assertDontSee('Oct');
    }

    public function test_background_alerts_are_written_in_the_default_language(): void
    {
        Http::fake(['*' => Http::response(['ok' => true])]);
        Setting::write('webhook_url', 'https://hooks.example.com/x');
        Setting::write('locale', 'id');
        $this->mikrotik->offline = true;

        $this->artisan('fluxmon:sync')->assertSuccessful();

        $this->assertStringContainsString('tidak bisa dijangkau', AlertEvent::first()->title);
        Http::assertSent(fn ($r) => str_contains($r['title'], 'tidak bisa dijangkau'));
    }

    public function test_background_alerts_stay_in_english_by_default(): void
    {
        Http::fake(['*' => Http::response(['ok' => true])]);
        $this->mikrotik->offline = true;

        $this->artisan('fluxmon:sync')->assertSuccessful();

        $this->assertStringContainsString('is unreachable', AlertEvent::first()->title);
    }

    public function test_daily_report_is_in_the_default_language(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);
        Setting::writeSecret('telegram_token', 't');
        Setting::write('telegram_chat_id', '1');
        Setting::write('telegram_daily_report', '1');
        Setting::write('locale', 'id');

        $this->artisan('fluxmon:daily-report')->assertSuccessful();

        Http::assertSent(fn ($r) => str_contains($r['text'], 'Laporan harian Fluxmon') && str_contains($r['text'], 'Pendapatan'));
    }

    public function test_every_translation_keeps_the_placeholders_of_its_source(): void
    {
        $id = json_decode(file_get_contents(lang_path('id.json')), true);

        foreach ($id as $source => $translation) {
            $this->assertNotSame('', trim($translation), "empty translation for '{$source}'");

            preg_match_all('/:[a-z_]+/i', $source, $a);
            preg_match_all('/:[a-z_]+/i', $translation, $b);
            sort($a[0]);
            sort($b[0]);
            $this->assertSame($a[0], $b[0], "placeholders differ in '{$source}'");
        }
    }

    public function test_every_translatable_string_in_the_app_has_an_indonesian_translation(): void
    {
        $id = json_decode(file_get_contents(lang_path('id.json')), true);
        $missing = [];

        foreach (['app', 'resources/views'] as $dir) {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(base_path($dir))) as $file) {
                if (! $file->isFile() || ! str_ends_with($file, '.php')) {
                    continue;
                }
                $source = file_get_contents($file);
                preg_match_all('/__\(\s*\'((?:[^\'\\\\]|\\\\.)*)\'/', $source, $single);
                preg_match_all('/__\(\s*"((?:[^"\\\\]|\\\\.)*)"/', $source, $double);

                foreach (array_merge($single[1], $double[1]) as $string) {
                    $string = stripcslashes($string);
                    // "auth.password" style keys live in lang/id/*.php, not in the JSON file.
                    if (! isset($id[$string]) && ! preg_match('/^[a-z_]+\.[a-z_.]+$/', $string)) {
                        $missing[$string] = true;
                    }
                }
            }
        }

        $this->assertSame([], array_keys($missing), 'untranslated strings: '.implode(' | ', array_keys($missing)));
    }

    public function test_pagination_and_password_messages_exist_in_indonesian(): void
    {
        app()->setLocale('id');

        $this->assertSame('Email atau password tidak cocok dengan data kami.', __('auth.failed'));
        $this->assertSame('&laquo; Sebelumnya', __('pagination.previous'));
        $this->assertSame('Password salah.', __('validation.current_password'));
        $this->assertStringContainsString('Kami sudah mengirim', __('passwords.sent'));
    }
}
