<?php

namespace Tests\Unit;

use App\Models\Tag;
use App\Models\Translation;
use App\Services\TranslationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationServiceTest extends TestCase
{
    use RefreshDatabase;

    private TranslationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(TranslationService::class);
    }

    public function test_create_resolves_tags_by_name(): void
    {
        Tag::create(['name' => 'web']);

        $t = $this->service->create([
            'locale' => 'en',
            'key' => 'a.b',
            'content' => 'hello',
            'tags' => ['web', 'mobile'],
        ]);

        $this->assertEqualsCanonicalizing(['web', 'mobile'], $t->tags->pluck('name')->all());
        $this->assertDatabaseHas('tags', ['name' => 'mobile']);
        $this->assertEquals(2, $t->tags()->count());
    }

    public function test_update_replaces_tags_when_provided(): void
    {
        $t = Translation::factory()->create();
        $t->tags()->attach(Tag::create(['name' => 'web']));

        $updated = $this->service->update($t, ['tags' => ['mobile']]);

        $this->assertEquals(['mobile'], $updated->tags->pluck('name')->all());
    }

    public function test_update_keeps_tags_when_not_provided(): void
    {
        $t = Translation::factory()->create();
        $t->tags()->attach(Tag::create(['name' => 'web']));

        $this->service->update($t, ['content' => 'changed']);

        $this->assertEquals(['web'], $t->fresh()->tags->pluck('name')->all());
        $this->assertEquals('changed', $t->fresh()->content);
    }

    public function test_export_returns_flat_map_for_locale(): void
    {
        Translation::factory()->create(['locale' => 'en', 'key' => 'a', 'content' => 'A']);
        Translation::factory()->create(['locale' => 'en', 'key' => 'b', 'content' => 'B']);
        Translation::factory()->create(['locale' => 'fr', 'key' => 'a', 'content' => 'AA']);

        $this->assertSame(['a' => 'A', 'b' => 'B'], $this->service->export('en'));
    }
}
