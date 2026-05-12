<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TranslationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create());
    }

    public function test_can_create_translation_with_tags(): void
    {
        $response = $this->postJson('/api/translations', [
            'locale' => 'en',
            'key' => 'auth.login.title',
            'content' => 'Sign in to your account',
            'tags' => ['web', 'mobile'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.locale', 'en')
            ->assertJsonPath('data.key', 'auth.login.title');

        $this->assertDatabaseHas('translations', ['key' => 'auth.login.title', 'locale' => 'en']);
        $this->assertDatabaseHas('tags', ['name' => 'web']);
        $this->assertEquals(2, Translation::first()->tags()->count());
    }

    public function test_store_validates_required_fields(): void
    {
        $this->postJson('/api/translations', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['locale', 'key', 'content']);
    }

    public function test_store_rejects_duplicate_locale_key(): void
    {
        Translation::factory()->create(['locale' => 'en', 'key' => 'home.title']);

        $this->postJson('/api/translations', [
            'locale' => 'en',
            'key' => 'home.title',
            'content' => 'Home',
        ])->assertStatus(422)->assertJsonValidationErrors('key');
    }

    public function test_can_update_translation(): void
    {
        $t = Translation::factory()->create(['locale' => 'en', 'key' => 'home.title', 'content' => 'Old']);

        $this->putJson("/api/translations/{$t->id}", [
            'content' => 'New title',
            'tags' => ['desktop'],
        ])->assertOk()->assertJsonPath('data.content', 'New title');

        $this->assertEquals('New title', $t->fresh()->content);
        $this->assertEquals(['desktop'], $t->fresh()->tags->pluck('name')->all());
    }

    public function test_can_show_translation(): void
    {
        $t = Translation::factory()->create();
        $t->tags()->attach(Tag::create(['name' => 'web']));

        $this->getJson("/api/translations/{$t->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $t->id)
            ->assertJsonPath('data.tags', ['web']);
    }

    public function test_can_delete_translation(): void
    {
        $t = Translation::factory()->create();

        $this->deleteJson("/api/translations/{$t->id}")->assertOk();
        $this->assertDatabaseMissing('translations', ['id' => $t->id]);
    }

    public function test_returns_404_for_missing_translation(): void
    {
        $this->getJson('/api/translations/99999')->assertStatus(404);
    }

    public function test_search_by_key_prefix(): void
    {
        Translation::factory()->create(['key' => 'auth.login.title', 'locale' => 'en']);
        Translation::factory()->create(['key' => 'auth.register.title', 'locale' => 'en']);
        Translation::factory()->create(['key' => 'home.title', 'locale' => 'en']);

        $res = $this->getJson('/api/translations?key=auth.')->assertOk();

        $this->assertCount(2, $res->json('data.items'));
    }

    public function test_search_by_content(): void
    {
        Translation::factory()->create(['content' => 'Welcome aboard', 'locale' => 'en']);
        Translation::factory()->create(['content' => 'Goodbye', 'locale' => 'en']);

        $res = $this->getJson('/api/translations?content=Welcome')->assertOk();

        $this->assertCount(1, $res->json('data.items'));
    }

    public function test_search_by_tags(): void
    {
        $mobile = Tag::create(['name' => 'mobile']);
        $web = Tag::create(['name' => 'web']);

        $t1 = Translation::factory()->create();
        $t1->tags()->attach([$mobile->id, $web->id]);

        $t2 = Translation::factory()->create();
        $t2->tags()->attach([$web->id]);

        $res = $this->getJson('/api/translations?tags[]=mobile')->assertOk();
        $this->assertCount(1, $res->json('data.items'));

        $res = $this->getJson('/api/translations?tags[]=web')->assertOk();
        $this->assertCount(2, $res->json('data.items'));
    }
}
