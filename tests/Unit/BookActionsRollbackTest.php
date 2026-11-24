<?php

use App\Actions\Books\CreateBook;
use App\Actions\Books\DeleteBook;
use App\Actions\Books\ListBooks;
use App\Actions\Books\UpdateBook;
use App\Models\Book;
use App\Models\Category;

mutates(CreateBook::class, UpdateBook::class, DeleteBook::class);

it('rolls back create on failure', function () {
    $category = Category::factory()->create();
    Book::created(fn () => throw new RuntimeException('boom'));

    expect(fn () => app(CreateBook::class)->handle([
        'title' => 'Titulo', 'author' => 'Autor', 'published_year' => 2000, 'category_id' => $category->id,
    ]))->toThrow(RuntimeException::class);

    expect(Book::query()->count())->toBe(0);
});

it('rolls back update on failure', function () {
    $book = Book::factory()->create(['title' => 'Original']);
    Book::updated(fn () => throw new RuntimeException('boom'));

    expect(fn () => app(UpdateBook::class)->handle($book->id, ['title' => 'Cambiado']))->toThrow(RuntimeException::class);

    expect($book->fresh()->title)->toBe('Original');
});

it('rolls back delete on failure', function () {
    $book = Book::factory()->create();
    Book::deleted(fn () => throw new RuntimeException('boom'));

    expect(fn () => app(DeleteBook::class)->handle($book->id))->toThrow(RuntimeException::class);

    $this->assertModelExists($book);
});

it('lists books and rolls back transaction level on failure', function () {
    Book::factory()->count(2)->create();

    expect(app(ListBooks::class)->handle([])->total())->toBe(2);
});
