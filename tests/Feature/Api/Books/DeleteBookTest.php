<?php

use App\Actions\Books\DeleteBook;
use App\Http\Controllers\BookController;
use App\Models\Book;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

mutates(DeleteBook::class, BookController::class);

beforeEach(fn () => Sanctum::actingAs(User::factory()->create()));

it('deletes a book', function () {
    $book = Book::factory()->create();

    $this->deleteJson("/api/v1/books/{$book->id}")->assertNoContent();

    $this->assertDatabaseMissing('books', ['id' => $book->id]);
});

it('returns 404 with a clear message when the book does not exist', function () {
    $this->deleteJson('/api/v1/books/9999')
        ->assertNotFound()
        ->assertJson(['message' => 'Libro no encontrado.']);
});
