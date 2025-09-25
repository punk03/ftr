<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }
        
        $userRole = auth()->user()->role ?? 'registrar';
        
        if (!in_array($userRole, $roles)) {
            abort(403, 'Недостаточно прав доступа');
        }
        
        return $next($request);
    }
}
