<?php

use App\Actions\Books\ListBooks;
use App\Http\Controllers\BookController;
use App\Http\Requests\IndexBookRequest;
use App\Http\Resources\BookCollection;
use App\Http\Resources\BookResource;
use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use App\Services\BookFilterService;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;

mutates(ListBooks::class, IndexBookRequest::class, BookCollection::class, BookResource::class, BookController::class, BookFilterService::class);

it('requires authentication', function () {
    $this->getJson('/api/v1/books')->assertUnauthorized()->assertJsonStructure(['message']);
});

it('lists books paginated with category', function () {
    Sanctum::actingAs(User::factory()->create());
    Book::factory()->count(51)->create();

    $response = $this->getJson('/api/v1/books');
    $response->assertOk()
        ->assertJsonCount(50, 'data')
        ->assertJsonStructure([
            'data' => [['id', 'title', 'author', 'description', 'published_year', 'category_id', 'category' => ['id', 'name']]],
            'links', 'meta',
        ])
        ->assertJsonPath('meta.per_page', 50)
        ->assertJsonPath('meta.total', 51)
        ->assertJsonPath('meta.current_page', 1);
});

it('returns the remaining book on the second page', function () {
    Sanctum::actingAs(User::factory()->create());
    Book::factory()->count(51)->create();

    $this->getJson('/api/v1/books?page=2')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.per_page', 50)
        ->assertJsonPath('meta.current_page', 2);
});

it('ignores a client supplied per_page', function () {
    Sanctum::actingAs(User::factory()->create());
    Book::factory()->count(51)->create();

    $this->getJson('/api/v1/books?per_page=5')
        ->assertOk()
        ->assertJsonCount(50, 'data')
        ->assertJsonPath('meta.per_page', 50);
});

it('filters books by category', function () {
    Sanctum::actingAs(User::factory()->create());
    $a = Category::factory()->create();
    $b = Category::factory()->create();
    Book::factory()->count(2)->create(['category_id' => $a->id]);
    Book::factory()->count(3)->create(['category_id' => $b->id]);

    $this->getJson('/api/v1/books?category='.$a->id)
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.category_id', $a->id)
        ->assertJsonPath('data.0.category.id', $a->id)
        ->assertJsonPath('data.0.category.name', $a->name);
});

it('rejects a non-existent category filter', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/books?category=9999')->assertUnprocessable()->assertJsonValidationErrors('category');
});

it('rejects a non-integer category filter', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/v1/books?category=abc')->assertUnprocessable()->assertJsonValidationErrors('category');
});

it('returns 429 when the rate limit is exceeded', function () {
    Sanctum::actingAs(User::factory()->create());
    RateLimiter::clear('api');

    for ($i = 0; $i < 60; $i++) {
        $this->getJson('/api/v1/books')->assertOk();
    }

    $this->getJson('/api/v1/books')->assertStatus(429)->assertJsonStructure(['message']);
});
