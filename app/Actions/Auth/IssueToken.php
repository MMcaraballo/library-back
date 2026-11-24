<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Throwable;

final class IssueToken
{
    /**
     * @param  array{email: string, password: string}  $credentials
     */
    public function handle(array $credentials): string
    {
        DB::beginTransaction();

        try {
            $user = User::query()->where('email', $credentials['email'])->first();

            if (! $user || ! Hash::check($credentials['password'], $user->password)) {
                throw ValidationException::withMessages([
                    'email' => ['Las credenciales proporcionadas no son correctas.'],
                ]);
            }

            $token = $user->createToken('api')->plainTextToken;

            DB::commit();

            return $token;
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }
}
