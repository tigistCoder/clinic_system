<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request with role-based permission protection.
     * Supports role alias mappings: 'lab' <-> 'lab_tech', 'pharmacy' <-> 'pharmacist'.
     * Admin role automatically passes all permission checks.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // 1. Verify Authentication
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // 2. Verify User Account Status
        if (isset($user->status) && $user->status !== 'active') {
            Auth::logout();
            return redirect()->route('login')->withErrors(['username' => 'Your account is inactive. Please contact system administrator.']);
        }

        // 3. Admin has full system access to all pages & actions
        if ($user->role === 'admin') {
            return $next($request);
        }

        // 4. Map allowed roles and aliases
        $allowedRoles = [];
        foreach ($roles as $r) {
            $allowedRoles[] = $r;
            if ($r === 'lab_tech') $allowedRoles[] = 'lab';
            if ($r === 'lab') $allowedRoles[] = 'lab_tech';
            if ($r === 'pharmacist') $allowedRoles[] = 'pharmacy';
            if ($r === 'pharmacy') $allowedRoles[] = 'pharmacist';
        }

        if (in_array($user->role, $allowedRoles, true)) {
            return $next($request);
        }

        // 5. Deny access if role does not match
        abort(403, 'Unauthorized action. Your role (' . ucfirst($user->role) . ') does not have permission to access this resource.');
    }
}