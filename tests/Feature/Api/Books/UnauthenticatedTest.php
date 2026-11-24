<?php

use App\Models\Book;

it('returns 401 on every book endpoint without a token', function (string $method, string $uri) {
    $this->json($method, $uri)->assertUnauthorized()->assertJson(['message' => 'No autenticado.']);
})->with([
    ['GET', '/api/v1/books'],
    ['GET', '/api/v1/categories'],
    ['POST', '/api/v1/books'],
    ['PUT', '/api/v1/books/1'],
    ['DELETE', '/api/v1/books/1'],
    ['POST', '/api/v1/logout'],
]);

it('does not touch data when unauthenticated', function () {
    $book = Book::factory()->create();

    $this->deleteJson("/api/v1/books/{$book->id}")->assertUnauthorized();

    $this->assertDatabaseHas('books', ['id' => $book->id]);
});
