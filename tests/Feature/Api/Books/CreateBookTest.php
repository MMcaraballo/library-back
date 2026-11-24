<?php

use App\Actions\Books\CreateBook;
use App\Http\Controllers\BookController;
use App\Http\Requests\StoreBookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

mutates(CreateBook::class, StoreBookRequest::class, BookResource::class, BookController::class);

beforeEach(function () {
    Sanctum::actingAs(User::factory()->create());
    $this->category = Category::factory()->create();
});

function validBook(array $overrides = []): array
{
    return array_merge([
        'title' => 'Don Quijote',
        'author' => 'Cervantes',
        'description' => 'Novela clásica',
        'published_year' => 1605,
        'category_id' => Category::query()->value('id'),
    ], $overrides);
}

it('creates a book', function () {
    $response = $this->postJson('/api/v1/books', validBook());
    $response->assertCreated()
        ->assertJsonPath('data.title', 'Don Quijote')
        ->assertJsonPath('data.category.name', $this->category->name)
        ->assertJsonStructure(['data' => ['id', 'title', 'author', 'description', 'published_year', 'category_id', 'category' => ['id', 'name'], 'created_at', 'updated_at']])
        ->assertJsonPath('data.category_id', $this->category->id)
        ->assertJsonPath('data.category.id', $this->category->id);

    $this->assertDatabaseHas('books', ['title' => 'Don Quijote', 'published_year' => 1605]);
});

it('creates a book without description', function () {
    $this->postJson('/api/v1/books', validBook(['description' => null]))->assertCreated();
});

it('validates fields', function (array $override, string $field) {
    $this->postJson('/api/v1/books', validBook($override))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);

    expect(Book::query()->count())->toBe(0);
})->with([
    'title missing' => [['title' => null], 'title'],
    'title too short' => [['title' => 'ab'], 'title'],
    'title too long' => [['title' => str_repeat('a', 256)], 'title'],
    'author missing' => [['author' => null], 'author'],
    'author too long' => [['author' => str_repeat('a', 256)], 'author'],
    'description not string' => [['description' => ['x']], 'description'],
    'year missing' => [['published_year' => null], 'published_year'],
    'year not integer' => [['published_year' => 'abcd'], 'published_year'],
    'year three digits' => [['published_year' => 999], 'published_year'],
    'year in the future' => [['published_year' => 3000], 'published_year'],
    'category missing' => [['category_id' => null], 'category_id'],
    'category unknown' => [['category_id' => 9999], 'category_id'],
]);
