<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Ordering and the customer account area are for customer accounts only. */
class EnsureCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->hasRole(Role::Customer)) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'This action is only available to customer accounts.'], 403);
            }

            return redirect()->route('admin.dashboard')
                ->with('warning', 'Team accounts cannot place orders. Sign in with a customer account to order.');
        }

        return $next($request);
    }
}
