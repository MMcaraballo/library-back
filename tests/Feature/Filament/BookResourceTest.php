<?php

use App\Filament\Resources\Books\Pages\ManageBooks;
use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Livewire\Livewire;

beforeEach(fn () => $this->actingAs(User::factory()->create()));

it('allows a user to access the panel', function () {
    $this->get('/admin')->assertOk();
});

it('lists books and filters by category', function () {
    $a = Category::factory()->create();
    $booksA = Book::factory()->count(2)->create(['category_id' => $a->id]);
    $other = Book::factory()->create();

    Livewire::test(ManageBooks::class)
        ->assertCanSeeTableRecords($booksA->merge([$other]))
        ->filterTable('category_id', $a->id)
        ->assertCanSeeTableRecords($booksA)
        ->assertCanNotSeeTableRecords([$other]);
});

it('creates a book', function () {
    $category = Category::factory()->create();

    Livewire::test(ManageBooks::class)
        ->callAction('create', [
            'title' => 'Libro nuevo', 'author' => 'Autor', 'published_year' => 2000, 'category_id' => $category->id,
        ])
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('books', ['title' => 'Libro nuevo']);
});

it('validates the book form', function () {
    Livewire::test(ManageBooks::class)
        ->callAction('create', ['title' => 'ab', 'author' => '', 'published_year' => 3000, 'category_id' => null])
        ->assertHasFormErrors(['title', 'author', 'published_year', 'category_id']);
});
