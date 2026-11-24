<?php

use App\Actions\Auth\IssueToken;
use App\Actions\Auth\RevokeToken;
use App\Actions\Books\CreateBook;
use App\Actions\Books\DeleteBook;
use App\Actions\Books\ListBooks;
use App\Actions\Books\UpdateBook;
use App\Actions\Categories\ListCategories;
use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use App\Services\BookFilterService;
use App\Services\CategoryService;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Database\Events\TransactionRolledBack;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;

mutates(
    CreateBook::class,
    UpdateBook::class,
    DeleteBook::class,
    ListBooks::class,
    ListCategories::class,
    IssueToken::class,
    RevokeToken::class,
    CategoryService::class,
);

/**
 * Records transaction lifecycle events emitted while running the callback.
 *
 * @return array{begin:int, commit:int, rollback:int}
 */
function transactionEvents(Closure $callback): array
{
    $events = ['begin' => 0, 'commit' => 0, 'rollback' => 0];
    Event::listen(TransactionBeginning::class, function () use (&$events) {
        $events['begin']++;
    });
    Event::listen(TransactionCommitted::class, function () use (&$events) {
        $events['commit']++;
    });
    Event::listen(TransactionRolledBack::class, function () use (&$events) {
        $events['rollback']++;
    });

    try {
        $callback();
    } catch (Throwable) {
        // the failure is part of the scenario
    }

    return $events;
}

/**
 * @return array<string, Closure>
 */
function successScenarios(): array
{
    return [
        'create book' => fn () => app(CreateBook::class)->handle([
            'title' => 'Titulo', 'author' => 'Autor', 'published_year' => 2000, 'category_id' => Category::factory()->create()->id,
        ]),
        'update book' => fn () => app(UpdateBook::class)->handle(Book::factory()->create()->id, ['title' => 'Nuevo']),
        'delete book' => fn () => app(DeleteBook::class)->handle(Book::factory()->create()->id),
        'list books' => fn () => app(ListBooks::class)->handle([]),
        'list categories' => fn () => app(ListCategories::class)->handle(),
        'issue token' => function () {
            User::factory()->create(['email' => 'a@example.com', 'password' => 'secret123']);
            app(IssueToken::class)->handle(['email' => 'a@example.com', 'password' => 'secret123']);
        },
        'revoke token' => function () {
            $user = User::factory()->create();
            $user->withAccessToken($user->createToken('api')->accessToken);
            app(RevokeToken::class)->handle($user);
        },
        'delete category' => fn () => app(CategoryService::class)->delete(Category::factory()->create()),
    ];
}

/**
 * @return array<string, Closure>
 */
function failureScenarios(): array
{
    return [
        'create book' => fn () => app(CreateBook::class)->handle(['title' => 'x']),
        'update book' => fn () => app(UpdateBook::class)->handle(999999, []),
        'delete book' => fn () => app(DeleteBook::class)->handle(999999),
        'list books' => function () {
            app()->instance(BookFilterService::class, new class extends BookFilterService
            {
                public function apply($query, array $filters): never
                {
                    throw new RuntimeException('boom');
                }
            });

            app(ListBooks::class)->handle([]);
        },
        'list categories' => function () {
            Category::factory()->create();
            Category::retrieved(fn () => throw new RuntimeException('boom'));

            app(ListCategories::class)->handle();
        },
        'issue token' => fn () => app(IssueToken::class)->handle(['email' => 'no@example.com', 'password' => 'x']),
        'revoke token without token' => fn () => app(RevokeToken::class)->handle(User::factory()->create()),
        'delete category with books' => function () {
            $category = Category::factory()->create();
            Book::factory()->create(['category_id' => $category->id]);

            app(CategoryService::class)->delete($category);
        },
    ];
}

it('opens and commits exactly one transaction on success', function (string $name) {
    $scenario = successScenarios()[$name];
    // Prepare fixtures outside the measured window is not possible for closures, so
    // measure begin/commit as a delta: fixtures are created inside the same closure.
    $events = transactionEvents($scenario);

    expect($events['begin'])->toBeGreaterThanOrEqual(1)
        ->and($events['rollback'])->toBe(0)
        ->and($events['commit'])->toBe($events['begin']);
})->with(array_keys(successScenarios()));

it('rolls the transaction back on failure and never commits', function (string $name) {
    $events = transactionEvents(failureScenarios()[$name]);

    expect($events['rollback'])->toBe(1)
        ->and($events['commit'])->toBe($events['begin'] - 1);
})->with(array_keys(failureScenarios()));

it('reports invalid credentials as a validation error', function () {
    expect(fn () => app(IssueToken::class)->handle(['email' => 'no@example.com', 'password' => 'x']))
        ->toThrow(ValidationException::class);
});
