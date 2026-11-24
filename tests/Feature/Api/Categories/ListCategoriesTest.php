<?php

use App\Actions\Categories\ListCategories;
use App\Http\Controllers\CategoryController;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;

mutates(ListCategories::class, CategoryController::class, CategoryResource::class);

it('requires authentication', function () {
    $this->getJson('/api/v1/categories')->assertUnauthorized()->assertJson(['message' => 'No autenticado.']);
});

it('lists categories ordered by name without pagination', function () {
    Sanctum::actingAs(User::factory()->create());
    Category::factory()->create(['name' => 'Zoologia']);
    $arte = Category::factory()->create(['name' => 'Arte']);
    Category::factory()->create(['name' => 'Novela']);

    $response = $this->getJson('/api/v1/categories');

    $response->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonStructure(['data' => [['id', 'name']]])
        ->assertJsonMissingPath('links')
        ->assertJsonMissingPath('meta')
        ->assertJsonPath('data.0.id', $arte->id)
        ->assertJsonPath('data.0.name', 'Arte')
        ->assertJsonPath('data.1.name', 'Novela')
        ->assertJsonPath('data.2.name', 'Zoologia');

    expect(array_keys($response->json('data.0')))->toBe(['id', 'name']);
});

it('returns an empty list when there are no categories', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/categories')->assertOk()->assertExactJson(['data' => []]);
});

it('returns 429 when the rate limit is exceeded', function () {
    Sanctum::actingAs(User::factory()->create());
    RateLimiter::clear('api');

    for ($i = 0; $i < 60; $i++) {
        $this->getJson('/api/v1/categories')->assertOk();
    }

    $this->getJson('/api/v1/categories')->assertStatus(429)->assertJsonStructure(['message']);
});
