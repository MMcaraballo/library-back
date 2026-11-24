<?php

use App\Exceptions\CategoryHasBooksException;
use App\Models\Book;
use App\Models\Category;
use App\Services\CategoryService;

mutates(CategoryService::class, CategoryHasBooksException::class);

it('deletes a category without books', function () {
    $category = Category::factory()->create();

    (new CategoryService)->delete($category);

    $this->assertModelMissing($category);
});

it('refuses to delete a category with books', function () {
    $category = Category::factory()->create();
    Book::factory()->create(['category_id' => $category->id]);

    expect(fn () => (new CategoryService)->delete($category))->toThrow(CategoryHasBooksException::class);

    $this->assertModelExists($category);
});

it('rolls back when deletion fails', function () {
    $category = Category::factory()->create();
    Category::deleted(fn () => throw new RuntimeException('boom'));

    expect(fn () => (new CategoryService)->delete($category))->toThrow(RuntimeException::class);

    $this->assertModelExists($category);
});

it('maps CategoryHasBooksException to 409', function () {
    $response = (new CategoryHasBooksException)->render(request());

    expect($response->getStatusCode())->toBe(409);
});

it('exposes a default message and a 409 payload', function () {
    $exception = new CategoryHasBooksException;
    $response = $exception->render(request());

    expect($exception->getMessage())->toBe('No se puede eliminar la categoría porque tiene libros asociados.')
        ->and($response->getData(true))->toBe(['message' => $exception->getMessage()])
        ->and((new CategoryHasBooksException('custom'))->getMessage())->toBe('custom');
});
