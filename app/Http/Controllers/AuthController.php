<?php

namespace App\Http\Controllers;

use App\Actions\Auth\IssueToken;
use App\Actions\Auth\RevokeToken;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuthController extends Controller
{
    public function login(LoginRequest $request, IssueToken $action): JsonResponse
    {
        return response()->json(['token' => $action->handle($request->validated())]);
    }

    public function logout(Request $request, RevokeToken $action): Response
    {
        $action->handle($request->user());

        return response()->noContent();
    }
}
