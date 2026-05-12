<?php

namespace Tests\Feature;

use App\Models\Translation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_export_returns_key_value_map_for_locale(): void
    {
        Translation::factory()->create(['locale' => 'en', 'key' => 'home.title', 'content' => 'Home']);
        Translation::factory()->create(['locale' => 'en', 'key' => 'home.greet', 'content' => 'Hello']);
        Translation::factory()->create(['locale' => 'fr', 'key' => 'home.title', 'content' => 'Accueil']);

        $response = $this->getJson('/api/translations/export/en')->assertOk();

        $data = $response->json();
        $this->assertSame('Home', $data['home.title']);
        $this->assertSame('Hello', $data['home.greet']);
        $this->assertArrayNotHasKey('home.title.fr', $data);
    }

    public function test_export_reflects_updates_immediately(): void
    {
        $t = Translation::factory()->create(['locale' => 'en', 'key' => 'home.title', 'content' => 'Home']);

        $payload = json_decode($this->getJson('/api/translations/export/en')->getContent(), true);
        $this->assertSame('Home', $payload['home.title']);

        $t->update(['content' => 'Welcome']);

        $payload = json_decode($this->getJson('/api/translations/export/en')->getContent(), true);
        $this->assertSame('Welcome', $payload['home.title']);
    }

    public function test_export_returns_empty_for_unknown_locale(): void
    {
        Translation::factory()->create(['locale' => 'en', 'key' => 'home.title']);

        $this->getJson('/api/translations/export/xx')->assertOk()->assertExactJson([]);
    }
}
