<?php

use App\Models\Book;
use App\Models\Category;
use App\Services\BookFilterService;

mutates(BookFilterService::class);

it('filters by category', function () {
    $a = Category::factory()->create();
    Book::factory()->count(2)->create(['category_id' => $a->id]);
    Book::factory()->create();

    $result = (new BookFilterService)->apply(Book::query(), ['category' => $a->id])->get();

    expect($result)->toHaveCount(2);
});

it('does not filter when category is absent', function () {
    Book::factory()->count(3)->create();

    expect((new BookFilterService)->apply(Book::query(), [])->get())->toHaveCount(3);
});

it('does not filter when category is an empty string', function () {
    Book::factory()->count(3)->create();

    expect((new BookFilterService)->apply(Book::query(), ['category' => ''])->get())->toHaveCount(3);
});

it('casts the category filter to integer', function () {
    Book::factory()->count(2)->create();

    expect((new BookFilterService)->apply(Book::query(), ['category' => 'abc'])->get())->toHaveCount(0);
});
