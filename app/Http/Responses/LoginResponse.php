<?php

namespace App\Http\Responses;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

/**
 * Custom Fortify {@see LoginResponseContract} implementation: instead of
 * Fortify's default redirect, sends each user to the home route for their
 * role (admin dashboard, tenant portal, or receiver portal) via
 * {@see User::homeRouteName()}.
 */
class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): Response
    {
        $user = $request->user();
        $route = $user instanceof User
            ? $user->homeRouteName()
            : UserRole::Admin->homeRoute();

        return $request->wantsJson()
            ? new JsonResponse(['two_factor' => false], 200)
            : redirect()->intended(route($route));
    }
}
