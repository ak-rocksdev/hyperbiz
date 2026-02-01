<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\TwoFactorEnabledResponse as TwoFactorEnabledResponseContract;

class TwoFactorEnabledResponse implements TwoFactorEnabledResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function toResponse($request)
    {
        // For Inertia requests, return a redirect back
        // Inertia will handle the page reload properly
        return $request->wantsJson()
            ? new JsonResponse(['two_factor_enabled' => true], 200)
            : back()->with('status', 'two-factor-authentication-enabled');
    }
}
