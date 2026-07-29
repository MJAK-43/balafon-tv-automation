<?php

namespace Tests\Feature\Taxonomy;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxonomyCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_category_and_tag_crud_flow(): void
    {
        $this->seed();
        $user = User::query()->where('email', 'admin@balafon.local')->firstOrFail();

        $category = $this->actingAs($user)->postJson('/api/v1/taxonomy/categories', [
            'name' => 'News',
        ])->assertCreated();

        $tag = $this->actingAs($user)->postJson('/api/v1/taxonomy/tags', [
            'name' => 'Prime Time',
        ])->assertCreated();

        $categoryUuid = $category->json('uuid');
        $tagUuid = $tag->json('uuid');

        $this->actingAs($user)->putJson("/api/v1/taxonomy/categories/{$categoryUuid}", [
            'name' => 'Breaking News',
        ])->assertOk()->assertJsonPath('slug', 'breaking-news');

        $this->actingAs($user)->putJson("/api/v1/taxonomy/tags/{$tagUuid}", [
            'name' => 'Prime Time Plus',
        ])->assertOk()->assertJsonPath('slug', 'prime-time-plus');

        $this->actingAs($user)->deleteJson("/api/v1/taxonomy/categories/{$categoryUuid}")
            ->assertNoContent();

        $this->actingAs($user)->deleteJson("/api/v1/taxonomy/tags/{$tagUuid}")
            ->assertNoContent();
    }
}
