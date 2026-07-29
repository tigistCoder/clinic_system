<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): \Symfony\Component\HttpFoundation\Response  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // 1. ተጠቃሚው Login ማድረጉን ማረጋገጥ
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // 2. ተጠቃሚው Active መሆኑን ማረጋገጥ
        if ($user->status !== 'active') {
            Auth::logout();
            return redirect()->route('login')->withErrors(['username' => 'አካውንትዎ ታግዷል (Inactive)። እባክዎ አድሚኑን ያናግሩ።']);
        }

        // 3. የተፈቀደለት Role መሆኑን ማረጋገጥ
        if (in_array($user->role, $roles)) {
            return $next($request);
        }

        // የተከለከለ ከሆነ Access Denied ማድረግ
        abort(403, 'Unauthorized access: You do not have permission to access this page.');
    }
}