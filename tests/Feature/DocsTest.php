<?php

namespace Tests\Feature;

use App\Support\Docs;
use Illuminate\Support\Facades\File;
use Tests\FluxmonTestCase;

class DocsTest extends FluxmonTestCase
{
    public function test_guests_cannot_read_the_docs(): void
    {
        $this->get('/docs')->assertRedirect('/login');
    }

    public function test_docs_index_redirects_to_the_first_page(): void
    {
        $this->asAdmin()->get('/docs')->assertRedirect('/docs/getting-started');
    }

    public function test_every_page_renders_in_english(): void
    {
        foreach (array_keys(Docs::pages()) as $slug) {
            $this->asAdmin()->get("/docs/{$slug}")->assertOk()->assertSee('prose-docs', false);
        }
    }

    public function test_every_page_renders_in_indonesian_with_the_interface_labels(): void
    {
        \App\Models\Setting::write('locale', 'id');

        foreach (array_keys(Docs::pages()) as $slug) {
            $this->asAdmin()->get("/docs/{$slug}")->assertOk();
        }

        // Labels in the guide come from the same translations as the screen they describe.
        $this->asAdmin()->get('/docs/connect-router')->assertSee('Tambah router')->assertSee('Uji');
        $this->asAdmin()->get('/docs/getting-started')->assertSee('Mulai cepat')->assertSee('Dokumentasi');
    }

    public function test_rendered_text_is_not_mangled_by_blade(): void
    {
        $settings = $this->asAdmin()->get('/docs/settings')->getContent();
        $this->assertStringContainsString('@BotFather', $settings);
        $this->assertStringNotContainsString('@@', $settings);

        $trouble = $this->asAdmin()->get('/docs/troubleshooting')->getContent();
        $this->assertStringContainsString('you@example.com', $trouble);

        $cron = $this->asAdmin()->get('/docs/scheduler')->getContent();
        $this->assertStringContainsString('&amp;&amp; php artisan schedule:run', $cron);

        \App\Models\Setting::write('locale', 'id');
        foreach (array_keys(Docs::pages()) as $slug) {
            $html = $this->asAdmin()->get("/docs/{$slug}")->getContent();
            $this->assertDoesNotMatchRegularExpression('/\{\{|\}\}/', $html, "unrendered Blade braces on id/{$slug}");
        }
    }

    public function test_unknown_page_is_not_found(): void
    {
        $this->asAdmin()->get('/docs/nope')->assertNotFound();
        $this->asAdmin()->get('/docs/..%2F..%2F.env')->assertNotFound();
    }

    public function test_every_role_can_read_the_docs(): void
    {
        foreach (['technician', 'cashier', 'viewer'] as $role) {
            $user = \App\Models\User::factory()->create();
            $user->forceFill(['role' => $role])->save();

            $this->actingAs($user)->get('/docs/getting-started')->assertOk();
        }
    }

    public function test_every_english_page_has_an_indonesian_twin_and_the_reverse(): void
    {
        $slugs = fn (string $lang) => collect(File::files(resource_path("views/docs/{$lang}")))
            ->map(fn ($f) => str_replace('.blade.php', '', $f->getFilename()))->sort()->values()->all();

        $this->assertSame(array_keys(Docs::pages()), collect($slugs('en'))->sortBy(fn ($s) => array_search($s, array_keys(Docs::pages())))->values()->all());
        $this->assertSame($slugs('en'), $slugs('id'));
    }

    public function test_page_titles_have_indonesian_translations(): void
    {
        $id = json_decode(file_get_contents(lang_path('id.json')), true);

        foreach (Docs::pages() as $title) {
            $this->assertArrayHasKey($title, $id, "missing translation for docs page '{$title}'");
        }
    }

    public function test_docs_link_is_in_the_sidebar_and_prev_next_links_work(): void
    {
        $this->asAdmin()->get('/dashboard')->assertSee('/docs');

        $page = $this->asAdmin()->get('/docs/connect-router')->assertOk();
        $page->assertSee('/docs/getting-started')->assertSee('/docs/dashboard');
    }

    public function test_internal_links_inside_the_guide_point_to_real_pages(): void
    {
        foreach (['en', 'id'] as $lang) {
            foreach (File::files(resource_path("views/docs/{$lang}")) as $file) {
                preg_match_all("/route\('docs', '([a-z-]+)'\)/", $file->getContents(), $m);
                foreach ($m[1] as $target) {
                    $this->assertTrue(Docs::exists($target), "{$lang}/{$file->getFilename()} links to unknown page '{$target}'");
                }
            }
        }
    }
}
