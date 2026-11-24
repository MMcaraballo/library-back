<?php

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

final class RevokeToken
{
    public function handle(User $user): void
    {
        DB::beginTransaction();

        try {
            $user->currentAccessToken()->delete();

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            throw $e;
        }
    }
}
