<?php

use App\Actions\Books\UpdateBook;
use App\Http\Controllers\BookController;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

mutates(UpdateBook::class, UpdateBookRequest::class, BookResource::class, BookController::class);

beforeEach(function () {
    Sanctum::actingAs(User::factory()->create());
    $this->book = Book::factory()->create(['title' => 'Original']);
});

function updatePayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Nuevo título',
        'author' => 'Nuevo autor',
        'description' => null,
        'published_year' => 2001,
        'category_id' => Category::factory()->create()->id,
    ], $overrides);
}

it('updates a book', function () {
    $payload = updatePayload();

    $response = $this->putJson("/api/v1/books/{$this->book->id}", $payload);
    $response->assertOk()
        ->assertJsonPath('data.title', 'Nuevo título')
        ->assertJsonPath('data.category.name', Category::query()->find($payload['category_id'])->name)
        ->assertJsonStructure(['data' => ['id', 'title', 'author', 'description', 'published_year', 'category_id', 'category' => ['id', 'name'], 'created_at', 'updated_at']])
        ->assertJsonPath('data.category_id', $payload['category_id'])
        ->assertJsonPath('data.category.id', $payload['category_id']);

    $this->assertDatabaseHas('books', ['id' => $this->book->id, 'title' => 'Nuevo título', 'published_year' => 2001]);
});

it('returns 404 when the book does not exist', function () {
    $this->putJson('/api/v1/books/9999', updatePayload())
        ->assertNotFound()
        ->assertJsonStructure(['message']);
});

it('validates fields', function (array $override, string $field) {
    $this->putJson("/api/v1/books/{$this->book->id}", updatePayload($override))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($field);

    expect($this->book->fresh()->title)->toBe('Original');
})->with([
    'title short' => [['title' => 'ab'], 'title'],
    'author missing' => [['author' => null], 'author'],
    'year future' => [['published_year' => 3000], 'published_year'],
    'year short' => [['published_year' => 99], 'published_year'],
    'category unknown' => [['category_id' => 9999], 'category_id'],
]);
