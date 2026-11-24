<?php

use App\Filament\Resources\Categories\Pages\ManageCategories;
use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

beforeEach(fn () => $this->actingAs(User::factory()->create()));

it('creates a category', function () {
    Livewire::test(ManageCategories::class)
        ->callAction('create', ['name' => 'Fantasía'])
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('categories', ['name' => 'Fantasía']);
});

it('requires a unique category name', function () {
    Category::factory()->create(['name' => 'Fantasía']);

    Livewire::test(ManageCategories::class)
        ->callAction('create', ['name' => 'Fantasía'])
        ->assertHasFormErrors(['name' => 'unique']);

    expect(Category::query()->count())->toBe(1);
});

it('requires a category name', function () {
    Livewire::test(ManageCategories::class)
        ->callAction('create', ['name' => ''])
        ->assertHasFormErrors(['name' => 'required']);
});

it('deletes a category without books', function () {
    $category = Category::factory()->create();

    Livewire::test(ManageCategories::class)
        ->callAction(TestAction::make(DeleteAction::class)->table($category));

    $this->assertModelMissing($category);
});

it('blocks deleting a category with books and notifies', function () {
    $category = Category::factory()->create();
    Book::factory()->create(['category_id' => $category->id]);

    Livewire::test(ManageCategories::class)
        ->callAction(TestAction::make(DeleteAction::class)->table($category))
        ->assertNotified();

    $this->assertModelExists($category);
});

it('blocks bulk deleting categories with books', function () {
    $withBooks = Category::factory()->create();
    Book::factory()->create(['category_id' => $withBooks->id]);
    $empty = Category::factory()->create();

    Livewire::test(ManageCategories::class)
        ->selectTableRecords([$withBooks->id, $empty->id])
        ->callAction(TestAction::make('delete')->table()->bulk())
        ->assertNotified();

    $this->assertModelExists($withBooks);
    $this->assertModelMissing($empty);
});
