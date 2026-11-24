<?php

use App\Actions\Auth\IssueToken;
use App\Actions\Auth\RevokeToken;
use App\Http\Controllers\AuthController;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;

mutates(IssueToken::class, RevokeToken::class, LoginRequest::class, AuthController::class);

it('issues a token with valid credentials', function () {
    $user = User::factory()->create(['email' => 'a@example.com', 'password' => 'secret123']);

    $response = $this->postJson('/api/v1/login', ['email' => 'a@example.com', 'password' => 'secret123'])
        ->assertOk()
        ->assertJsonStructure(['token']);

    $this->withHeader('Authorization', 'Bearer '.$response->json('token'))
        ->getJson('/api/v1/books')->assertOk();

    expect($user->tokens()->count())->toBe(1);
});

it('rejects invalid credentials with 422', function () {
    User::factory()->create(['email' => 'a@example.com', 'password' => 'secret123']);

    $this->postJson('/api/v1/login', ['email' => 'a@example.com', 'password' => 'wrong'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('email');
});

it('validates the login payload', function () {
    $this->postJson('/api/v1/login', [])->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
});

it('revokes the current token on logout', function () {
    $user = User::factory()->create();
    $token = $user->createToken('api')->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)->postJson('/api/v1/logout')->assertNoContent();

    expect($user->tokens()->count())->toBe(0);
});

it('throttles login more strictly', function () {
    RateLimiter::clear('login');

    for ($i = 0; $i < 5; $i++) {
        $this->postJson('/api/v1/login', ['email' => 'x@example.com', 'password' => 'bad'])->assertUnprocessable();
    }

    $this->postJson('/api/v1/login', ['email' => 'x@example.com', 'password' => 'bad'])
        ->assertStatus(429)
        ->assertJsonStructure(['message']);
});
