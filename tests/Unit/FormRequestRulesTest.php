<?php

use App\Http\Requests\IndexBookRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Category;
use Illuminate\Support\Facades\Validator;

mutates(
    StoreBookRequest::class,
    UpdateBookRequest::class,
    IndexBookRequest::class,
    LoginRequest::class,
);

function bookPayload(array $overrides = [], array $without = []): array
{
    $payload = array_merge([
        'title' => 'Don Quijote',
        'author' => 'Cervantes',
        'description' => 'Novela',
        'published_year' => 1605,
        'category_id' => Category::query()->value('id') ?? Category::factory()->create()->id,
    ], $overrides);

    return array_diff_key($payload, array_flip($without));
}

dataset('book request classes', [
    'store' => [StoreBookRequest::class],
    'update' => [UpdateBookRequest::class],
]);

dataset('invalid book payloads', [
    'title absent' => [[], ['title'], 'title'],
    'title is an array with enough items' => [['title' => ['a', 'b', 'c']], [], 'title'],
    'title too short' => [['title' => 'ab'], [], 'title'],
    'title too long' => [['title' => str_repeat('a', 256)], [], 'title'],
    'author absent' => [[], ['author'], 'author'],
    'author is an array' => [['author' => ['a']], [], 'author'],
    'author too long' => [['author' => str_repeat('a', 256)], [], 'author'],
    'description is an array' => [['description' => ['a']], [], 'description'],
    'year absent' => [[], ['published_year'], 'published_year'],
    'year too long' => [['published_year' => 20000], [], 'published_year'],
    'year in the future' => [['published_year' => (int) date('Y') + 1], [], 'published_year'],
    'category absent' => [[], ['category_id'], 'category_id'],
    'category not an integer' => [['category_id' => 'abc'], [], 'category_id'],
    'category unknown' => [['category_id' => 999999], [], 'category_id'],
]);

it('rejects invalid book payloads', function (string $request, array $overrides, array $without, string $field) {
    $validator = Validator::make(bookPayload($overrides, $without), (new $request)->rules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->keys())->toContain($field);
})->with('book request classes')->with('invalid book payloads');

it('accepts a valid book payload, with or without description', function (string $request) {
    expect(Validator::make(bookPayload(), (new $request)->rules())->passes())->toBeTrue()
        ->and(Validator::make(bookPayload(['description' => null]), (new $request)->rules())->passes())->toBeTrue()
        ->and(Validator::make(bookPayload([], ['description']), (new $request)->rules())->passes())->toBeTrue()
        ->and(Validator::make(bookPayload(['published_year' => (int) date('Y')]), (new $request)->rules())->passes())->toBeTrue();
})->with('book request classes');

it('authorizes book requests', function (string $request) {
    expect((new $request)->authorize())->toBeTrue();
})->with('book request classes');

it('accepts a missing or null category filter and rejects invalid ones', function () {
    $category = Category::factory()->create();
    $rules = (new IndexBookRequest)->rules();

    expect(Validator::make([], $rules)->passes())->toBeTrue()
        ->and(Validator::make(['category' => null], $rules)->passes())->toBeTrue()
        ->and(Validator::make(['category' => $category->id], $rules)->passes())->toBeTrue()
        ->and(Validator::make(['category' => 'abc'], $rules)->fails())->toBeTrue()
        ->and(Validator::make(['category' => 999999], $rules)->fails())->toBeTrue()
        ->and((new IndexBookRequest)->authorize())->toBeTrue();
});

it('validates the login payload rules', function () {
    $rules = (new LoginRequest)->rules();

    expect(Validator::make(['email' => 'a@example.com', 'password' => 'secret'], $rules)->passes())->toBeTrue()
        ->and(Validator::make(['password' => 'secret'], $rules)->errors()->keys())->toContain('email')
        ->and(Validator::make(['email' => 'not-an-email', 'password' => 'secret'], $rules)->errors()->keys())->toContain('email')
        ->and(Validator::make(['email' => 'a@example.com'], $rules)->errors()->keys())->toContain('password')
        ->and(Validator::make(['email' => 'a@example.com', 'password' => ['x']], $rules)->errors()->keys())->toContain('password')
        ->and((new LoginRequest)->authorize())->toBeTrue();
});
