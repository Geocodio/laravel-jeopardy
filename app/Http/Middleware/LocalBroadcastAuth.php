<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class LocalBroadcastAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // For local development, create a guest user if not authenticated
        if (app()->environment('local') && !Auth::check()) {
            $guestUser = new User();
            $guestUser->id = 'guest_' . session()->getId();
            $guestUser->name = 'Guest Player';
            $guestUser->email = 'guest@local.test';
            
            Auth::setUser($guestUser);
        }
        
        return $next($request);
    }
}
